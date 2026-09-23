<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use Illuminate\Support\Collection;
use Shopper\Cart\Models\Cart;
use Shopper\Cart\Models\CartAddress;
use Shopper\Cart\Models\CartLine;
use Shopper\Core\Enum\ProductType;
use Shopper\Core\Models\Carrier;
use Shopper\Core\Models\Contracts\Inventory;
use Shopper\Core\Models\Product;
use Shopper\Shipping\DataTransferObjects\Address;
use Shopper\Shipping\DataTransferObjects\ShippingRate;
use Shopper\Shipping\Services\CarrierRateService;

final readonly class FetchDeliveryRates
{
    public function __construct(
        private CarrierRateService $rateService,
        private BuildShippingPackages $packages,
    ) {}

    /**
     * Quote every carrier of the cart zone for the cart's shipping address.
     * Option ids are "{carrier}:{service}", the format the cart stores and
     * the order resolves back to a carrier option.
     *
     * @return array{options: array<int, array<string, mixed>>, warnings: array<int, string>}
     */
    public function handle(Cart $cart): array
    {
        $cart->loadMissing(['zone.carriers', 'zone.shippingOptions', 'lines.purchasable', 'addresses.country']);

        $zone = $cart->zone;
        $lines = $this->shippableLines($cart);

        if (! $zone || $lines->isEmpty()) {
            return ['options' => [], 'warnings' => []];
        }

        $warnings = [];
        $destination = $this->destination($cart->shippingAddress());
        $origin = $destination ? $this->origin() : null;

        if ($destination && ! $origin) {
            $warnings[] = __('No shipping origin is configured, only flat rates are available.');
        }

        $result = $this->rateService->getZoneRates(
            zone: $zone,
            from: $origin,
            to: $destination,
            packages: $this->packages->handle($lines),
        );

        foreach ($result->failedCarriers as $carrier) {
            $warnings[] = __(':carrier is temporarily unavailable.', ['carrier' => $carrier]);
        }

        $carriers = $zone->carriers->keyBy(fn (Carrier $carrier): string => $carrier->slug ?? $carrier->name);
        $descriptions = $zone->shippingOptions->pluck('description', 'public_id');

        $options = $result->rates
            ->filter(fn (ShippingRate $rate): bool => strcasecmp($rate->currency, $cart->currency_code) === 0)
            ->map(function (ShippingRate $rate) use ($carriers, $descriptions): array {
                $carrier = $carriers->get($rate->carrierCode);

                return [
                    'id' => "{$rate->carrierCode}:{$rate->serviceCode}",
                    'name' => $rate->serviceName,
                    'amount' => $rate->amount,
                    'currency' => $rate->currency,
                    'estimated_days' => $rate->estimatedDays,
                    'description' => $descriptions->get($rate->serviceCode),
                    'carrier_name' => $carrier?->name ?? $rate->carrierCode,
                    'carrier_logo' => $carrier ? $this->rateService->getLogoUrl($carrier) : null,
                ];
            })
            ->values()
            ->all();

        return ['options' => $options, 'warnings' => $warnings];
    }

    public function requiresShipping(Cart $cart): bool
    {
        $cart->loadMissing('lines.purchasable');

        return $this->shippableLines($cart)->isNotEmpty();
    }

    /**
     * @return Collection<int, CartLine>
     */
    private function shippableLines(Cart $cart): Collection
    {
        return $cart->lines
            ->filter(function (CartLine $line): bool {
                $purchasable = $line->purchasable;

                if ($purchasable instanceof Product) {
                    return ! in_array($purchasable->type, [ProductType::Virtual, ProductType::External], true);
                }

                return $purchasable !== null;
            })
            ->values();
    }

    private function destination(?CartAddress $address): ?Address
    {
        $country = $address?->country?->cca2;

        if (! $address || ! $country) {
            return null;
        }

        return new Address(
            firstName: $address->first_name ?? '',
            lastName: $address->last_name ?? '',
            street: $address->address_1 ?? '',
            city: $address->city ?? '',
            postalCode: $address->postal_code ?? '',
            state: $address->state ?? '',
            country: $country,
            company: $address->company,
            street2: $address->address_2,
            phone: $address->phone,
            email: auth()->user()?->email,
        );
    }

    /**
     * The default inventory is the warehouse carrier rates are quoted from.
     */
    private function origin(): ?Address
    {
        $inventory = resolve(Inventory::class)::query()
            ->with('country')
            ->orderByDesc('is_default')
            ->orderBy('priority')
            ->first();

        $country = $inventory?->country?->cca2;

        if (! $inventory || ! $country || ! $inventory->street_address) {
            return null;
        }

        return new Address(
            firstName: '',
            lastName: $inventory->name,
            street: $inventory->street_address,
            city: $inventory->city,
            postalCode: $inventory->postal_code,
            state: '',
            country: $country,
            street2: $inventory->street_address_plus,
            phone: $inventory->phone_number,
            email: $inventory->email,
        );
    }
}
