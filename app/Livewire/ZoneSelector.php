<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Checkout\CreatePaymentSession;
use App\Actions\GetCountriesByZone;
use App\Actions\ZoneSessionManager;
use App\DTO\CountryByZoneData;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Shopper\Cart\CartManager;
use Shopper\Cart\CartSessionManager;
use Shopper\Cart\Exceptions\MissingPriceException;
use Shopper\Cart\Models\Cart;
use Shopper\Cart\Models\CartLine;

final class ZoneSelector extends Component
{
    public bool $showModal = false;

    public function mount(): void
    {
        if (! ZoneSessionManager::checkSession()) {
            $countries = $this->countries;

            if ($countries->count() === 1) {
                ZoneSessionManager::setSession($countries->first());
            } else {
                $this->showModal = $countries->isNotEmpty();
            }
        }
    }

    /**
     * @return Collection<int, CountryByZoneData>
     */
    #[Computed]
    public function countries(): Collection
    {
        return resolve(GetCountriesByZone::class)->handle();
    }

    public function selectZone(int $countryId): void
    {
        /** @var CountryByZoneData $selectedZone */
        $selectedZone = $this->countries->firstWhere('countryId', $countryId);

        if (! $selectedZone) {
            return;
        }

        if ($selectedZone->countryId !== ZoneSessionManager::getSession()?->countryId) {
            ZoneSessionManager::setSession($selectedZone);

            $cart = resolve(CartSessionManager::class)->current();

            if ($cart) {
                $this->moveCart($cart, $selectedZone);
            }
        }

        $this->showModal = false;

        $this->redirect(request()->header('Referer', route('home')), navigate: true);
    }

    public function openSelector(): void
    {
        $this->showModal = true;
    }

    public function render(): View
    {
        return view('livewire.zone-selector');
    }

    /**
     * Carry the cart into the new zone: lines without a price in the new
     * currency are dropped, the rest is re-priced by the cart manager, which
     * also resets the delivery choice and the payment session. The payment
     * method belongs to the old zone, so it is reset too.
     */
    private function moveCart(Cart $cart, CountryByZoneData $zone): void
    {
        $manager = resolve(CartManager::class);
        $cart->load('lines.purchasable');

        $dropped = $cart->lines->filter(
            fn (CartLine $line): bool => $line->purchasable?->getPrice($zone->currencyCode) === null,
        );

        foreach ($dropped as $line) {
            $manager->remove($cart, $line->id);
        }

        $previousSession = $cart->payment_session;
        $cart->update(['zone_id' => $zone->zoneId, 'payment_method_id' => null]);

        try {
            $manager->changeCurrency($cart->refresh(), $zone->currencyCode);
        } catch (MissingPriceException $exception) {
            report($exception);
            $manager->clear($cart);
        }

        if ($cart->refresh()->payment_session === null) {
            resolve(CreatePaymentSession::class)->cancel($previousSession);
        }

        if ($dropped->isNotEmpty()) {
            $this->dispatch('notify', type: 'error', message: __(':count item(s) not available in :currency were removed from your cart.', [
                'count' => $dropped->count(),
                'currency' => $zone->currencyCode,
            ]));
        }
    }
}
