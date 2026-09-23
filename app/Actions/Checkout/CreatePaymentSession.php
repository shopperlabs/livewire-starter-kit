<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use App\Exceptions\CheckoutException;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Shopper\Cart\CartManager;
use Shopper\Cart\Exceptions\CartCompletedException;
use Shopper\Cart\Models\Cart;
use Shopper\Payment\Contracts\PaymentDriver;
use Shopper\Payment\DataTransferObjects\PaymentResult;
use Shopper\Payment\Exceptions\PaymentException;
use Shopper\Payment\PaymentManager;
use Throwable;

final readonly class CreatePaymentSession
{
    public function __construct(
        private PaymentManager $paymentManager,
        private CartManager $cartManager,
    ) {}

    /**
     * Open a provider payment for the cart total, or resume the one already
     * pinned on the cart when nothing changed. Reference, amount and currency
     * are stored on the cart so the order can verify them before it exists.
     *
     * @throws CheckoutException
     * @throws CartCompletedException
     */
    public function handle(Cart $cart): PaymentResult
    {
        $method = $cart->paymentMethod;

        if (! $method) {
            throw new CheckoutException(__('Select a payment method first.'));
        }

        $driverCode = $method->driver ?? 'manual';

        if (! $this->paymentManager->isConfigured($driverCode)) {
            report(PaymentException::notConfigured($driverCode));

            throw new CheckoutException(__(':method is not available right now.', ['method' => $method->title]));
        }

        $driver = $this->paymentManager->driver($driverCode);

        try {
            return Cache::lock("cart:payment-session:{$cart->public_id}", 90)->block(3, fn (): PaymentResult => $this->pin($cart, $driver, $driverCode));
        } catch (LockTimeoutException) {
            throw new CheckoutException(__('A payment is already being prepared for this cart, please try again in a moment.'));
        }
    }

    /**
     * Cancel a session the cart no longer points to, so a customer cannot pay
     * an intent that will never become an order.
     *
     * @param  array<string, mixed>|null  $session
     */
    public function cancel(?array $session): void
    {
        if (! $session || ! isset($session['reference'])) {
            return;
        }

        try {
            $this->paymentManager->driver($session['driver'] ?? 'manual')->cancelPayment($session['reference']);
        } catch (Throwable $exception) {
            // An intent left open at the provider expires on its own.
            report($exception);
        }
    }

    private function pin(Cart $cart, PaymentDriver $driver, string $driverCode): PaymentResult
    {
        $cart->unsetRelations()->refresh();

        if ($cart->isCompleted()) {
            throw new CartCompletedException;
        }

        $amount = $this->cartManager->calculate($cart)->total;

        if ($amount <= 0) {
            throw new CheckoutException(__('There is nothing to pay for this cart.'));
        }

        return $this->resume($cart, $driver, $driverCode, $amount)
            ?? $this->open($cart, $driver, $driverCode, $amount);
    }

    private function resume(Cart $cart, PaymentDriver $driver, string $driverCode, int $amount): ?PaymentResult
    {
        $session = $cart->payment_session;

        if (
            ! $session
            || ($session['driver'] ?? null) !== $driverCode
            || ($session['amount'] ?? null) !== $amount
            || ($session['currency'] ?? $cart->currency_code) !== $cart->currency_code
            || ! isset($session['reference'])
        ) {
            return null;
        }

        try {
            $result = $driver->retrievePayment($session['reference']);
        } catch (PaymentException) {
            return null;
        }

        return $result->success ? $result : null;
    }

    private function open(Cart $cart, PaymentDriver $driver, string $driverCode, int $amount): PaymentResult
    {
        $previous = $cart->payment_session;

        try {
            $result = $driver->initiatePayment(
                amount: $amount,
                currency: $cart->currency_code,
                context: [
                    'idempotency_key' => "cart_{$cart->public_id}_".Str::ulid(),
                    'metadata' => ['cart_id' => (string) $cart->public_id],
                ],
            );
        } catch (PaymentException $exception) {
            report($exception);

            throw new CheckoutException(__('The payment provider is unavailable, please try again in a moment.'));
        }

        $this->cartManager->setPaymentSession($cart, [
            'driver' => $driverCode,
            'reference' => $result->reference,
            'amount' => $amount,
            'currency' => $cart->currency_code,
        ]);

        $this->cancel($previous);

        return $result;
    }
}
