<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use App\Exceptions\CheckoutException;
use Shopper\Cart\Actions\CreateOrderFromCartAction;
use Shopper\Cart\CartManager;
use Shopper\Cart\Exceptions\CartCompletedException;
use Shopper\Cart\Exceptions\DiscountLimitReachedException;
use Shopper\Cart\Exceptions\InsufficientStockException;
use Shopper\Cart\Exceptions\PriceChangedException;
use Shopper\Cart\Models\Cart;
use Shopper\Cart\Pipelines\CartPipelineContext;
use Shopper\Core\Exceptions\CampaignBudgetExceededException;
use Shopper\Core\Models\Contracts\Order;
use Shopper\Payment\Actions\SettlePayment;
use Shopper\Payment\Actions\SyncPaymentWithProvider;
use Shopper\Payment\Enum\TransactionStatus;
use Shopper\Payment\Enum\TransactionType;
use Shopper\Payment\Models\PaymentTransaction;
use Throwable;

final readonly class CompleteCheckout
{
    public function __construct(
        private FetchDeliveryRates $deliveryRates,
        private CartManager $cartManager,
        private CreateOrderFromCartAction $createOrderFromCart,
        private SettlePayment $settlePayment,
        private SyncPaymentWithProvider $syncPayment,
    ) {}

    /**
     * Turn the cart into an order. Addresses, delivery option, payment method
     * and payment session all live on the cart, so this is idempotent: a cart
     * that already completed returns its order.
     *
     * @throws CheckoutException
     * @throws PriceChangedException
     * @throws InsufficientStockException
     * @throws DiscountLimitReachedException
     * @throws CampaignBudgetExceededException
     */
    public function handle(Cart $cart): Order
    {
        if ($cart->isCompleted()) {
            return $cart->order()->firstOrFail();
        }

        $cart->load(['zone.carriers', 'zone.shippingOptions', 'lines.purchasable', 'addresses.country', 'customer', 'paymentMethod']);

        if ($cart->lines->isEmpty()) {
            throw new CheckoutException(__('Your cart is empty.'));
        }

        if (! $cart->payment_method_id) {
            throw new CheckoutException(__('Select a payment method first.'));
        }

        $this->ensurePaymentMethodAvailable($cart);

        $this->ensureEmail($cart);
        $this->refreshShipping($cart);

        try {
            $order = $this->createOrderFromCart->execute(
                $cart,
                fn (CartPipelineContext $context) => $this->guardPaymentSession($cart, $context->total),
                fn (Order $order) => $this->recordInitiatedPayment($cart, $order),
            );
        } catch (CartCompletedException) {
            return $cart->refresh()->order()->firstOrFail();
        }

        $this->settle($cart, $order);

        return $order;
    }

    /**
     * The zone can change after the method was chosen, and an admin can
     * disable a method at any time.
     */
    private function ensurePaymentMethodAvailable(Cart $cart): void
    {
        $available = $cart->zone?->paymentMethods()
            ->whereKey($cart->payment_method_id)
            ->where('is_enabled', true)
            ->exists();

        if (! $available || ! in_array($cart->paymentMethod?->driver ?? 'manual', FetchPaymentMethods::SUPPORTED_DRIVERS, true)) {
            throw new CheckoutException(__('The selected payment method is not available, please choose another one.'));
        }
    }

    private function ensureEmail(Cart $cart): void
    {
        if ($cart->email !== null) {
            return;
        }

        $email = $cart->customer?->getAttribute('email');

        if (! is_string($email)) {
            throw new CheckoutException(__('An email address is required to place the order.'));
        }

        $this->cartManager->setEmail($cart, $email);
    }

    /**
     * Re-quote the chosen delivery option right before it is frozen: a vanished
     * option or a price increase is refused, a price drop is taken silently.
     */
    private function refreshShipping(Cart $cart): void
    {
        if (! $this->deliveryRates->requiresShipping($cart)) {
            return;
        }

        if (! $cart->shipping_option_id) {
            throw new CheckoutException(__('Select a delivery method first.'));
        }

        $option = collect($this->deliveryRates->handle($cart)['options'])
            ->firstWhere('id', $cart->shipping_option_id);

        if (! $option) {
            throw new CheckoutException(__('The selected delivery method is no longer available.'));
        }

        $quoted = (int) $option['amount'];
        $frozen = $cart->shipping_amount;

        if ($frozen !== null && $quoted > $frozen) {
            throw new CheckoutException(__('The delivery price changed, please choose your delivery method again.'));
        }

        if ($quoted !== $frozen) {
            $this->cartManager->setShippingMethod($cart, $cart->shipping_option_id, $quoted);
        }
    }

    /**
     * Runs under the cart lock, right before the totals are frozen: the amount
     * the provider was asked for must be the amount of the order.
     */
    private function guardPaymentSession(Cart $cart, int $total): void
    {
        $session = $cart->payment_session;

        if (! $session || ! isset($session['amount'])) {
            if (($cart->paymentMethod?->driver ?? 'manual') !== 'manual') {
                throw new CheckoutException(__('The payment session has expired, please start the payment again.'));
            }

            return;
        }

        if ($session['amount'] !== $total || ($session['currency'] ?? $cart->currency_code) !== $cart->currency_code) {
            throw new CheckoutException(__('Your cart changed since the payment started, please start the payment again.'));
        }
    }

    /**
     * Runs inside the order transaction, so the payment reference commits or
     * rolls back together with the order.
     */
    private function recordInitiatedPayment(Cart $cart, Order $order): void
    {
        $session = $cart->payment_session;

        if (! $session || ! isset($session['reference'])) {
            return;
        }

        PaymentTransaction::query()->create([
            'order_id' => $order->id,
            'payment_method_id' => $cart->payment_method_id,
            'driver' => $session['driver'] ?? 'manual',
            'type' => TransactionType::Initiate,
            'status' => TransactionStatus::Pending,
            'amount' => $session['amount'] ?? $order->price_amount,
            'currency_code' => $order->currency_code,
            'reference' => $session['reference'],
        ]);
    }

    /**
     * Apply the provider events that arrived before the order existed, then ask
     * the provider for the current state so the confirmation page shows the real
     * payment status without waiting for a webhook.
     */
    private function settle(Cart $cart, Order $order): void
    {
        $reference = $cart->payment_session['reference'] ?? null;

        if (! is_string($reference)) {
            return;
        }

        try {
            $this->settlePayment->execute($reference);
            // Pull the provider state now so the confirmation page is accurate.
            // Anything missed is caught by the shopper:payments:reconcile
            // schedule, which needs a running scheduler and queue worker.
            $this->syncPayment->execute($order, $reference);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
