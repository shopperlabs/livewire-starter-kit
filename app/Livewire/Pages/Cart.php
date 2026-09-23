<?php

declare(strict_types=1);

namespace App\Livewire\Pages;

use App\Models\Product;
use App\Models\ProductVariant;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Shopper\Cart\CartManager;
use Shopper\Cart\CartSessionManager;
use Shopper\Cart\Discounts\DiscountValidator;
use Shopper\Cart\Discounts\PromotionResolver;
use Shopper\Cart\Exceptions\InsufficientStockException;
use Shopper\Cart\Exceptions\InvalidDiscountException;
use Shopper\Cart\Models\Cart as CartModel;
use Shopper\Cart\Pipelines\CartPipelineContext;
use Shopper\Core\Models\Discount;

final class Cart extends Component
{
    use WithRateLimiting;

    public string $couponCode = '';

    #[Computed]
    public function cart(): ?CartModel
    {
        $cart = resolve(CartSessionManager::class)->current();
        $cart?->load([
            'lines.purchasable' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                ProductVariant::class => ['media', 'product'],
                Product::class => ['media'],
            ]),
            'promotions',
        ]);

        return $cart;
    }

    #[Computed]
    public function cartContext(): ?CartPipelineContext
    {
        if (! $this->cart) {
            return null;
        }

        return resolve(CartManager::class)->totals($this->cart);
    }

    public function updateQuantity(int $lineId, int $quantity): void
    {
        if ($quantity < 1 || ! $this->cart) {
            return;
        }

        try {
            resolve(CartManager::class)->update($this->cart, $lineId, ['quantity' => $quantity]);
        } catch (InsufficientStockException) {
            $this->dispatch('notify', type: 'error', message: __('Not enough stock for this quantity.'));

            return;
        }

        $this->refreshCart();
    }

    public function removeLine(int $lineId): void
    {
        if (! $this->cart) {
            return;
        }

        resolve(CartManager::class)->remove($this->cart, $lineId);

        $this->refreshCart();
    }

    public function clearCart(): void
    {
        if (! $this->cart) {
            return;
        }

        resolve(CartManager::class)->clear($this->cart);

        $this->refreshCart();
    }

    /**
     * A code is only kept when it really applies to the cart: the promotion
     * engine decides, and a code that would not discount anything is rolled back.
     * One message for unknown and non-applicable codes, so codes cannot be
     * enumerated.
     */
    public function applyCoupon(): void
    {
        $this->resetErrorBag('couponCode');

        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException) {
            $this->addError('couponCode', __('Too many attempts. Please try again in a minute.'));

            return;
        }

        $this->validate(['couponCode' => 'required|string|max:50']);

        $cart = $this->cart;
        $code = trim($this->couponCode);
        $discount = Discount::query()->where('code', $code)->first();

        if (! $cart || ! $discount instanceof Discount || ! $discount->is_active) {
            $this->addError('couponCode', __('This code is not valid for your cart.'));

            return;
        }

        $manager = resolve(CartManager::class);

        try {
            DB::transaction(function () use ($cart, $code, $discount, $manager): void {
                $manager->applyCoupon($cart, $code);
                $context = $manager->calculate($cart);

                $applies = resolve(DiscountValidator::class)->validate($discount, $context)->valid
                    && resolve(PromotionResolver::class)->wouldApply($discount, $context);

                if (! $applies) {
                    throw new InvalidDiscountException(__('This code is not valid for your cart.'));
                }
            });
        } catch (InvalidDiscountException $exception) {
            $this->addError('couponCode', $exception->getMessage());

            return;
        }

        $this->couponCode = '';
        $this->refreshCart();
    }

    public function removeCoupon(string $code): void
    {
        if (! $this->cart) {
            return;
        }

        resolve(CartManager::class)->removeCoupon($this->cart, $code);

        $this->refreshCart();
    }

    public function render(): View
    {
        return view('pages.shop.cart')
            ->title(__('Cart'));
    }

    private function refreshCart(): void
    {
        unset($this->cart, $this->cartContext);
        $this->dispatch('cart-updated');
    }
}
