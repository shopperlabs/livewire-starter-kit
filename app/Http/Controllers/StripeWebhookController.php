<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Order;
use Shopper\Payment\Facades\Payment;
use Shopper\Payment\Models\PaymentTransaction;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $driver = Payment::driver('stripe');

        $result = $driver->handleWebhook(
            payload: ['_raw_body' => $request->getContent()],
            headers: [
                'stripe-signature' => $request->header('Stripe-Signature', ''),
            ],
        );

        if ($result->isIgnored()) {
            return response()->json(['status' => 'ignored']);
        }

        $transaction = PaymentTransaction::query()
            ->where('reference', $result->reference)
            ->first();

        if (! $transaction) {
            return response()->json(['status' => 'no_transaction'], 404);
        }

        $order = Order::query()->find($transaction->order_id);

        if (! $order) {
            return response()->json(['status' => 'no_order'], 404);
        }

        $targetState = match ($result->action) {
            'authorized' => ['payment_status' => PaymentStatus::Authorized],
            'captured' => ['payment_status' => PaymentStatus::Paid],
            'failed', 'canceled' => ['status' => OrderStatus::Cancelled, 'payment_status' => PaymentStatus::Voided],
            'refunded' => ['status' => OrderStatus::Cancelled, 'payment_status' => PaymentStatus::Refunded],
            default => null,
        };

        if (! $targetState) {
            return response()->json(['status' => 'unhandled_action']);
        }

        if (
            isset($targetState['payment_status'])
            && $order->payment_status === $targetState['payment_status']
        ) {
            return response()->json(['status' => 'already_processed']);
        }

        $order->update($targetState);

        return response()->json(['status' => 'handled']);
    }
}
