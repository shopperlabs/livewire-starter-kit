<?php

declare(strict_types=1);

namespace App\Traits;

use App\Actions\ZoneSessionManager;
use App\DTO\PriceData;
use Shopper\Core\Helpers\Price;
use Shopper\Core\Pricing\PricingContext;

trait HasProductPricing
{
    /**
     * The price the cart will charge: resolved for the current currency, zone
     * and customer by the price resolver, so a pricing addon applies here too.
     */
    public function getFormattedPrice(): ?PriceData
    {
        $currencyCode = current_currency();

        if (! $this->relationLoaded('prices')) {
            $this->load(['prices' => fn ($q) => $q->whereRelation('currency', 'code', $currencyCode)->with('currency')]);
        }

        $price = $this->resolvePrice(new PricingContext(
            currencyCode: $currencyCode,
            customerId: auth()->id(),
            zoneId: ZoneSessionManager::getSession()?->zoneId,
        ));

        if (! $price) {
            return null;
        }

        return new PriceData(
            amount: Price::from($price->amount, $currencyCode),
            compare: $price->compareAmount ? Price::from($price->compareAmount, $currencyCode) : null,
            percentage: $price->compareAmount > 0
                ? round((($price->compareAmount - $price->amount) / $price->compareAmount) * 100)
                : null,
        );
    }
}
