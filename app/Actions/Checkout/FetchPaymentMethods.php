<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use Shopper\Core\Models\PaymentMethod;
use Shopper\Core\Models\Zone;
use Shopper\Payment\Services\PaymentProcessingService;

final readonly class FetchPaymentMethods
{
    public function __construct(
        private PaymentProcessingService $payments,
    ) {}

    /**
     * Drivers this storefront knows how to check out with. Add a driver here
     * once its payment page and return flow exist.
     */
    public const array SUPPORTED_DRIVERS = ['manual', 'stripe'];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function handle(Zone $zone): array
    {
        return $this->payments->getMethodsForZone($zone)
            ->filter(fn (PaymentMethod $method): bool => in_array($method->driver ?? 'manual', self::SUPPORTED_DRIVERS, true))
            ->map(fn (PaymentMethod $method): array => [
                'id' => $method->id,
                'title' => $method->title,
                'slug' => $method->slug,
                'driver' => $method->driver ?? 'manual',
                'description' => $method->description,
                'logo' => $this->payments->getLogoUrl($method),
            ])
            ->values()
            ->all();
    }
}
