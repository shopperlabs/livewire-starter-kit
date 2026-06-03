<?php

declare(strict_types=1);

namespace App\Livewire\Pages;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class StripePayment extends Component
{
    #[Locked]
    public string $clientSecret;

    #[Locked]
    public string $publishableKey;

    #[Locked]
    public string $returnUrl;

    public function mount(): void
    {
        $stripePayment = session()->get('stripe_payment');

        if (! $stripePayment) {
            $this->redirect(route('shop.checkout'), navigate: true);

            return;
        }

        $this->clientSecret = $stripePayment['client_secret'];
        $this->publishableKey = $stripePayment['publishable_key'];
        $this->returnUrl = route('shop.checkout.stripe-return');
    }

    public function render(): View
    {
        return view('pages.shop.stripe-payment')
            ->layout('layouts.store')
            ->title(__('Complete your payment'));
    }
}
