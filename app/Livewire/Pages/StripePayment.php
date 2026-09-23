<?php

declare(strict_types=1);

namespace App\Livewire\Pages;

use App\Actions\Checkout\CreatePaymentSession;
use App\Exceptions\CheckoutException;
use App\Http\Controllers\StripeReturnController;
use Illuminate\Contracts\View\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Shopper\Cart\CartSessionManager;
use Shopper\Cart\Exceptions\CartCompletedException;
use Throwable;

final class StripePayment extends Component
{
    #[Locked]
    public string $clientSecret;

    #[Locked]
    public string $publishableKey;

    #[Locked]
    public string $returnUrl;

    #[Locked]
    public int $amount;

    #[Locked]
    public string $currency;

    /**
     * Resume the intent pinned on the cart, or open a new one when the cart
     * changed since, so the customer always pays the current total.
     */
    public function mount(): void
    {
        $cart = resolve(CartSessionManager::class)->current();

        if (! $cart || $cart->paymentMethod?->driver !== 'stripe') {
            $this->redirectRoute('shop.checkout', navigate: true);

            return;
        }

        try {
            $payment = resolve(CreatePaymentSession::class)->handle($cart);
        } catch (CartCompletedException) {
            $this->redirectRoute('shop.checkout.success', ['order' => $cart->refresh()->order_id], navigate: true);

            return;
        } catch (CheckoutException $exception) {
            session()->flash('errors', (new ViewErrorBag)->put('default', new MessageBag(['payment' => $exception->getMessage()])));
            $this->redirectRoute('shop.checkout', navigate: true);

            return;
        } catch (Throwable $exception) {
            report($exception);
            $this->redirectRoute('shop.checkout', navigate: true);

            return;
        }

        if (in_array($payment->status, StripeReturnController::PAID_STATUSES, true)) {
            $this->redirectRoute('shop.checkout.stripe-return', ['payment_intent' => $payment->reference]);

            return;
        }

        if (! $payment->clientSecret) {
            $this->redirectRoute('shop.checkout', navigate: true);

            return;
        }

        $this->clientSecret = $payment->clientSecret;
        $this->publishableKey = (string) config('shopper.stripe.publishable_key');
        $this->returnUrl = route('shop.checkout.stripe-return');
        $this->amount = (int) $cart->refresh()->payment_session['amount'];
        $this->currency = $cart->currency_code;
    }

    public function render(): View
    {
        return view('pages.shop.stripe-payment')
            ->title(__('Complete your payment'));
    }
}
