<?php

declare(strict_types=1);

namespace App\Actions\Cart;

use App\Models\Product;
use App\Models\ProductVariant;
use Shopper\Cart\CartManager;
use Shopper\Cart\Models\CartLine;

final readonly class AddToCart
{
    public function __construct(
        private CartManager $cartManager,
    ) {}

    public function handle(Product $product, ?ProductVariant $variant = null, int $quantity = 1): CartLine
    {
        $purchasable = $variant ?? $product;

        return $this->cartManager->add(cartSession(), $purchasable, $quantity);
    }
}
