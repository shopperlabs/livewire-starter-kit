<?php

declare(strict_types=1);

namespace App\Livewire\Pages;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Shopper\Core\Models\Order;

final class StripePayment extends Component
{
    #[Locked]
    public Order $order;

    #[Locked]
    public string $clientSecret;

    #[Locked]
    public string $publishableKey;

    #[Locked]
    public string $returnUrl;

    public function mount(string $number): void
    {
        $this->order = Order::query()
            ->where('number', $number)
            ->where('customer_id', auth()->id())
            ->firstOrFail();

        $stripePayment = session()->pull('stripe_payment');

        if (! $stripePayment) {
            $this->redirect(route('shop.checkout.success', ['order' => $this->order->id]), navigate: true);

            return;
        }

        $this->clientSecret = $stripePayment['client_secret'];
        $this->publishableKey = $stripePayment['publishable_key'];
        $this->returnUrl = route('shop.checkout.success', ['order' => $this->order->id]);
    }

    public function render(): View
    {
        return view('pages.shop.stripe-payment')
            ->layout('layouts.store')
            ->title(__('Complete your payment'));
    }
}
