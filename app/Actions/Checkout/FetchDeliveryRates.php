<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use Shopper\Core\Models\Carrier;
use Shopper\Core\Models\Country;
use Shopper\Core\Models\Zone;
use Shopper\Shipping\DataTransferObjects\Address as ShippingAddress;
use Shopper\Shipping\DataTransferObjects\Package;
use Shopper\Shipping\Services\CarrierRateService;
use Throwable;

final class FetchDeliveryRates
{
    /**
     * @param  array<string, mixed>  $shippingAddress
     * @param  array<int, Package>  $packages
     * @return array<int, array<string, mixed>>
     */
    public function handle(array $shippingAddress, array $packages): array
    {
        $countryId = $shippingAddress['country_id'] ?? null;

        if (! $countryId) {
            return [];
        }

        $zone = resolve(ResolveZoneForCountry::class)->handle($countryId);

        if (! $zone) {
            return [];
        }

        $service = resolve(CarrierRateService::class);

        try {
            $rates = $service->getRatesForZone(
                zone: $zone,
                from: $this->buildOriginAddress(),
                to: $this->buildDestinationAddress($shippingAddress),
                packages: $packages,
            );
        } catch (Throwable $e) {
            report($e);

            return [];
        }

        return $this->formatRates($rates, $zone, $service);
    }

    private function buildOriginAddress(): ShippingAddress
    {
        return once(function (): ShippingAddress {
            $countryId = shopper_setting('country_id');
            $country = $countryId ? Country::query()->find($countryId) : null;

            return new ShippingAddress(
                firstName: shopper_setting('name') ?? '',
                lastName: '',
                street: shopper_setting('street_address') ?? '',
                city: shopper_setting('city') ?? '',
                postalCode: shopper_setting('postal_code') ?? '',
                state: shopper_setting('state') ?? '',
                country: $country?->cca2 ?? '',
                phone: shopper_setting('phone_number'),
            );
        });
    }

    /**
     * @param  array<string, mixed>  $shippingAddress
     */
    private function buildDestinationAddress(array $shippingAddress): ShippingAddress
    {
        $country = Country::query()->find($shippingAddress['country_id'] ?? null);

        return new ShippingAddress(
            firstName: $shippingAddress['first_name'] ?? '',
            lastName: $shippingAddress['last_name'] ?? '',
            street: $shippingAddress['street_address'] ?? '',
            city: $shippingAddress['city'] ?? '',
            postalCode: $shippingAddress['postal_code'] ?? '',
            state: $shippingAddress['state'] ?? '',
            country: $country?->cca2 ?? '',
            street2: $shippingAddress['street_address_plus'] ?? null,
            phone: $shippingAddress['phone_number'] ?? null,
            email: auth()->user()?->email,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function formatRates(mixed $rates, Zone $zone, CarrierRateService $service): array
    {
        $carriers = $zone->carriers()
            ->where('is_enabled', true)
            ->get()
            ->keyBy(fn (Carrier $carrier): string => $carrier->slug ?? $carrier->name);

        $carrierOptions = $zone->shippingOptions()
            ->where('is_enabled', true)
            ->get()
            ->keyBy('id');

        return $rates->map(function ($rate) use ($carriers, $carrierOptions, $service): array {
            $carrier = $carriers->get($rate->carrierCode);
            $option = is_int($rate->serviceCode) ? $carrierOptions->get($rate->serviceCode) : null;

            return [
                'service_code' => $rate->serviceCode,
                'service_name' => $rate->serviceName,
                'amount' => $rate->amount,
                'currency' => $rate->currency,
                'carrier_code' => $rate->carrierCode,
                'estimated_days' => $rate->estimatedDays,
                'description' => $option?->description,
                'carrier_name' => $carrier?->name ?? $rate->carrierCode,
                'carrier_logo' => $carrier ? $service->getLogoUrl($carrier) : null,
            ];
        })->values()->all();
    }
}
