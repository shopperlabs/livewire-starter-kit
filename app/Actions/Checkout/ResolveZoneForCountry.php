<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use Illuminate\Support\Facades\Cache;
use Shopper\Core\Models\Zone;

final class ResolveZoneForCountry
{
    public function handle(int $countryId): ?Zone
    {
        $zoneId = Cache::remember(
            "zone.country.{$countryId}",
            7200,
            fn (): ?int => Zone::query()
                ->whereHas('countries', fn ($q) => $q->where('id', $countryId))
                ->where('is_enabled', true)
                ->value('id'),
        );

        return $zoneId === null ? null : Zone::query()->find($zoneId);
    }
}
