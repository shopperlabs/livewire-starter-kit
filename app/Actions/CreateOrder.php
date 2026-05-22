<?php

declare(strict_types=1);

namespace App\Actions;

use App\CheckoutSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Shopper\Cart\Actions\CreateOrderFromCartAction;
use Shopper\Core\Models\Order;

final class CreateOrder
{
    public function handle(): Order
    {
        $checkout = session()->get(CheckoutSession::KEY);

        abort_unless(
            $checkout
            && data_get($checkout, 'shipping_option')
            && data_get($checkout, 'payment'),
            422,
            __('Checkout session is incomplete or expired.'),
        );

        $cart = cartSession();

        $lock = Cache::lock('checkout.create-order.'.$cart->id, 30);

        abort_unless($lock->get(), 409, __('A checkout is already in progress.'));

        try {
            return DB::transaction(function () use ($cart, $checkout): Order {
                $order = resolve(CreateOrderFromCartAction::class)->execute($cart);

                $shippingPrice = (int) data_get($checkout, 'shipping_option.0.price', 0);

                $order->update([
                    'shipping_option_id' => data_get($checkout, 'shipping_option.0.id'),
                    'payment_method_id' => data_get($checkout, 'payment.0.id'),
                    'price_amount' => $order->price_amount + $shippingPrice,
                ]);

                return $order;
            });
        } finally {
            $lock->release();
        }
    }
}
