<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use Shopper\Core\Models\PaymentMethod;
use Shopper\Payment\Services\PaymentProcessingService;

final class FetchPaymentMethods
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function handle(int $countryId): array
    {
        $zone = resolve(ResolveZoneForCountry::class)->handle($countryId);

        if (! $zone) {
            return [];
        }

        $service = resolve(PaymentProcessingService::class);

        return $service->getMethodsForZone($zone)
            ->map(fn (PaymentMethod $method): array => [
                'id' => $method->id,
                'title' => $method->title,
                'slug' => $method->slug,
                'description' => $method->description,
                'logo' => $service->getLogoUrl($method),
            ])
            ->values()
            ->all();
    }
}
