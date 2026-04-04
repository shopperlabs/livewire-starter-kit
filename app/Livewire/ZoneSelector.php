<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\GetCountriesByZone;
use App\Actions\ZoneSessionManager;
use App\CheckoutSession;
use App\DTO\CountryByZoneData;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Shopper\Cart\CartSessionManager;

class ZoneSelector extends Component
{
    public bool $showModal = false;

    public function mount(): void
    {
        if (! ZoneSessionManager::checkSession()) {
            $countries = $this->countries;

            if ($countries->count() === 1) {
                $this->autoSelectZone($countries->first());
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
            $oldCurrency = current_currency();

            ZoneSessionManager::setSession($selectedZone);

            session()->forget(CheckoutSession::KEY);

            $cart = resolve(CartSessionManager::class)->current();

            if ($cart) {
                $cart->update([
                    'zone_id' => $selectedZone->zoneId,
                    'currency_code' => $selectedZone->currencyCode,
                ]);
            }

            Cache::forget("home_featured_products_{$oldCurrency}");
            Cache::forget("home_featured_products_{$selectedZone->currencyCode}");
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

    private function autoSelectZone(CountryByZoneData $zone): void
    {
        ZoneSessionManager::setSession($zone);
    }
}
