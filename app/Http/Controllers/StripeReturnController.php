<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateOrder;
use App\CheckoutSession;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Shopper\Cart\CartSessionManager;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Order;
use Shopper\Payment\Enum\TransactionStatus;
use Shopper\Payment\Enum\TransactionType;
use Shopper\Payment\Models\PaymentTransaction;
use Stripe\StripeClient;
use Throwable;

final class StripeReturnController
{
    /**
     * Stripe redirects here after confirmPayment. Verify the intent
     * succeeded, then create the order. Idempotent against retries: the
     * intent id both gates the order creation lock and deduplicates the
     * payment transaction.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $intentId = (string) $request->query('payment_intent', '');
        $redirectStatus = (string) $request->query('redirect_status', '');
        $sessionIntentId = session()->get('stripe_intent_id');

        if ($intentId === '' || $intentId !== $sessionIntentId) {
            return redirect()->route('shop.checkout')
                ->withErrors(['payment' => __('Invalid payment session.')]);
        }

        $lock = Cache::lock('stripe.return.'.$intentId, 15);

        try {
            $lock->block(10);
        } catch (LockTimeoutException) {
            return redirect()->route('shop.checkout')
                ->withErrors(['payment' => __('Your payment is still being processed. Please wait a moment.')]);
        }

        try {
            return $this->process($intentId, $redirectStatus);
        } finally {
            $lock->release();
        }
    }

    private function process(string $intentId, string $redirectStatus): RedirectResponse
    {
        $existingTransaction = PaymentTransaction::query()
            ->where('reference', $intentId)
            ->first();

        if ($existingTransaction) {
            session()->forget(['stripe_payment', 'stripe_intent_id', CheckoutSession::KEY]);

            return redirect()->route('shop.checkout.success', ['order' => $existingTransaction->order_id]);
        }

        $intentStatus = $this->fetchStripeIntent($intentId);

        if (! in_array($intentStatus, ['succeeded', 'requires_capture', 'processing'], true)) {
            session()->forget(['stripe_payment', 'stripe_intent_id']);

            return redirect()->route('shop.checkout')
                ->withErrors(['payment' => __('Payment was not completed. Please try again.').' ('.($redirectStatus !== '' ? $redirectStatus : 'unknown').')']);
        }

        try {
            $order = DB::transaction(function () use ($intentId, $intentStatus): Order {
                $order = resolve(CreateOrder::class)->handle();
                $this->attachStripeIntentToOrder($order, $intentId, $intentStatus);

                return $order;
            });
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('shop.cart')->withErrors(['order' => __('Order creation failed after payment.')]);
        }

        session()->forget(['stripe_payment', 'stripe_intent_id', CheckoutSession::KEY]);
        resolve(CartSessionManager::class)->forget();

        return redirect()->route('shop.checkout.success', ['order' => $order->id]);
    }

    private function attachStripeIntentToOrder(Order $order, string $intentId, ?string $status): void
    {
        $secret = (string) config('shopper.payment.drivers.stripe.credentials.secret_key');

        if ($secret !== '') {
            try {
                (new StripeClient($secret))->paymentIntents->update($intentId, [
                    'metadata' => ['order_id' => $order->id, 'order_number' => $order->number],
                ]);
            } catch (Throwable) {
                // Non-blocking
            }
        }

        $order->update([
            'payment_status' => match ($status) {
                'succeeded' => PaymentStatus::Paid,
                'requires_capture' => PaymentStatus::Authorized,
                default => PaymentStatus::Pending,
            },
        ]);

        PaymentTransaction::query()->updateOrCreate(
            [
                'order_id' => $order->id,
                'reference' => $intentId,
            ],
            [
                'payment_method_id' => $order->payment_method_id,
                'driver' => 'stripe',
                'type' => match ($status) {
                    'succeeded' => TransactionType::Capture,
                    'requires_capture' => TransactionType::Authorize,
                    default => TransactionType::Initiate,
                },
                'amount' => $order->price_amount,
                'currency_code' => $order->currency_code,
                'status' => $status === 'succeeded' ? TransactionStatus::Success : TransactionStatus::Pending,
                'metadata' => ['stripe_status' => $status],
            ],
        );
    }

    private function fetchStripeIntent(string $intentId): ?string
    {
        $secret = (string) config('shopper.payment.drivers.stripe.credentials.secret_key');

        if ($secret === '') {
            return null;
        }

        try {
            return (new StripeClient($secret))
                ->paymentIntents
                ->retrieve($intentId)
                ->status;
        } catch (Throwable) {
            return null;
        }
    }
}
