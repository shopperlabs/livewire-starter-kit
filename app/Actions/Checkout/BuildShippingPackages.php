<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use Shopper\Cart\Models\CartLine;
use Shopper\Shipping\DataTransferObjects\Package;

final class BuildShippingPackages
{
    /**
     * @return array<int, Package>
     */
    public function handle(): array
    {
        $cart = cartSession();
        $cart->load('lines.purchasable');

        $packages = [];

        /** @var CartLine $line */
        foreach ($cart->lines as $line) {
            $model = $line->purchasable;

            for ($i = 0; $i < $line->quantity; $i++) {
                $packages[] = new Package(
                    length: (float) ($model->depth_value ?? 10.0),
                    width: (float) ($model->width_value ?? 10.0),
                    height: (float) ($model->height_value ?? 10.0),
                    weight: (float) ($model->weight_value ?? 1.0),
                );
            }
        }

        return $packages ?: [new Package(length: 10.0, width: 10.0, height: 10.0, weight: 1.0)];
    }
}
