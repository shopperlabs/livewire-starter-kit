<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Checkout\CompleteCheckout;
use App\Exceptions\CheckoutException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Shopper\Cart\CartManager;
use Shopper\Cart\CartSessionManager;
use Shopper\Cart\Exceptions\DiscountLimitReachedException;
use Shopper\Cart\Exceptions\InsufficientStockException;
use Shopper\Cart\Exceptions\PriceChangedException;
use Shopper\Cart\Models\Cart;
use Shopper\Cart\Models\Contracts\Cart as CartContract;
use Shopper\Core\Exceptions\CampaignBudgetExceededException;
use Shopper\Payment\DataTransferObjects\PaymentResult;
use Shopper\Payment\Exceptions\PaymentException;
use Shopper\Payment\Facades\Payment;
use Shopper\Payment\Models\PaymentTransaction;
use Throwable;

final class StripeReturnController
{
    public const array PAID_STATUSES = ['authorized', 'captured', 'processing'];

    public function __invoke(Request $request): RedirectResponse
    {
        $intentId = (string) $request->query('payment_intent', '');

        if ($intentId === '') {
            return $this->fail('payment', __('Invalid payment session.'));
        }

        if ($orderId = $this->completedOrderId($intentId)) {
            return redirect()->route('shop.checkout.success', ['order' => $orderId]);
        }

        $cart = $this->findCart($intentId);

        if (! $cart) {
            return $this->fail('payment', __('Invalid payment session.'));
        }

        try {
            $payment = Payment::driver('stripe')->retrievePayment($intentId);
        } catch (PaymentException $exception) {
            report($exception);
            $payment = null;
        }

        if (! $payment?->success || ! in_array($payment->status, self::PAID_STATUSES, true)) {
            return $this->fail('payment', __('Payment was not completed. Please try again.'));
        }

        $isSessionCart = resolve(CartSessionManager::class)->current()?->is($cart) ?? false;

        try {
            $order = resolve(CompleteCheckout::class)->handle($cart);
        } catch (CheckoutException|PriceChangedException|InsufficientStockException|DiscountLimitReachedException|CampaignBudgetExceededException $exception) {
            $this->release($cart, $payment);

            return redirect()->route('shop.cart')->withErrors(['order' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            report($exception);
            $this->release($cart, $payment);

            return redirect()->route('shop.cart')->withErrors(['order' => __('Order creation failed after payment. Your payment has been released.')]);
        }

        if ($isSessionCart) {
            resolve(CartSessionManager::class)->forget();
        }

        return redirect()->route('shop.checkout.success', ['order' => $order->id]);
    }

    /**
     * A refreshed return URL after success: the order already exists.
     */
    private function completedOrderId(string $intentId): ?int
    {
        $orderId = PaymentTransaction::query()
            ->where('reference', $intentId)
            ->whereHas('order', fn ($query) => $query->where('customer_id', Auth::id()))
            ->value('order_id');

        return $orderId ? (int) $orderId : null;
    }

    /**
     * The session cart first; when the return opens in another browser or the
     * session expired, the customer's open cart pinned to this intent.
     */
    private function findCart(string $intentId): ?Cart
    {
        $cart = resolve(CartSessionManager::class)->current();

        if ($cart && ($cart->payment_session['reference'] ?? null) === $intentId) {
            return $cart;
        }

        return resolve(CartContract::class)::query()
            ->where('customer_id', Auth::id())
            ->whereNull('completed_at')
            ->where('payment_session->reference', $intentId)
            ->first();
    }

    /**
     * The order could not be created: give the money back and drop the
     * session so the next attempt opens a fresh intent.
     */
    private function release(Cart $cart, PaymentResult $payment): void
    {
        $driver = Payment::driver('stripe');

        try {
            match ($payment->status) {
                'authorized' => $driver->cancelPayment((string) $payment->reference),
                'captured' => $driver->refundPayment((string) $payment->reference, (int) $payment->amount, 'requested_by_customer'),
                default => report(new CheckoutException("Payment {$payment->reference} still processing after a failed checkout.")),
            };
        } catch (Throwable $exception) {
            report($exception);
        }

        resolve(CartManager::class)->setPaymentSession($cart, null);
    }

    private function fail(string $key, string $message): RedirectResponse
    {
        return redirect()->route('shop.checkout')->withErrors([$key => $message]);
    }
}
