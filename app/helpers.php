<?php

declare(strict_types=1);

use App\Actions\ZoneSessionManager;
use App\DTO\CountryByZoneData;
use App\Models\Channel;
use Illuminate\Support\Facades\Cache;
use Shopper\Cart\CartSessionManager;
use Shopper\Cart\Models\Cart;
use Shopper\Core\Models\TaxZone;

if (! function_exists('cartSession')) {
    function cartSession(): Cart
    {
        $session = resolve(CartSessionManager::class);
        $cart = $session->current();

        if (! $cart) {
            $zone = ZoneSessionManager::getSession();
            $defaultChannel = Channel::query()->scopes('default')->first();

            $cart = $session->create([
                'currency_code' => current_currency(),
                'channel_id' => $defaultChannel?->id,
                'zone_id' => $zone?->zoneId,
                'customer_id' => auth()->id(),
            ]);
        }

        return $cart;
    }
}

if (! function_exists('current_currency')) {
    function current_currency(): string
    {
        return ZoneSessionManager::checkSession()
            ? ZoneSessionManager::getSession()->currencyCode
            : shopper_currency();
    }
}

if (! function_exists('current_tax_label')) {
    function current_tax_label(): string
    {
        $zone = ZoneSessionManager::getSession();

        if (! $zone instanceof CountryByZoneData) {
            return '';
        }

        $inclusive = Cache::remember(
            key: "tax_inclusive.{$zone->countryCode}",
            ttl: 3600,
            callback: fn (): bool => (bool) TaxZone::query()
                ->whereHas('country', fn ($q) => $q->where('cca2', $zone->countryCode))
                ->whereNull('province_code')
                ->value('is_tax_inclusive'),
        );

        return $inclusive ? __('TTC') : __('HT');
    }
}
