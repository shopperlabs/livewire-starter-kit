<?php

declare(strict_types=1);

namespace App\Livewire\Pages;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Shopper\Cart\CartManager;
use Shopper\Cart\CartSessionManager;
use Shopper\Cart\Models\Cart as CartModel;
use Shopper\Cart\Pipelines\CartPipelineContext;

class Cart extends Component
{
    #[Computed]
    #[On('cart-updated')]
    public function cart(): ?CartModel
    {
        $cart = resolve(CartSessionManager::class)->current();

        $cart?->load(['lines.purchasable.media']);

        return $cart;
    }

    #[Computed]
    public function cartContext(): ?CartPipelineContext
    {
        if (! $this->cart) {
            return null;
        }

        return resolve(CartManager::class)->calculate($this->cart);
    }

    public function updateQuantity(int $lineId, int $quantity): void
    {
        if ($quantity < 1 || ! $this->cart) {
            return;
        }

        resolve(CartManager::class)->update($this->cart, $lineId, ['quantity' => $quantity]);

        unset($this->cart, $this->cartContext);
        $this->dispatch('cart-updated');
    }

    public function removeLine(int $lineId): void
    {
        if (! $this->cart) {
            return;
        }

        resolve(CartManager::class)->remove($this->cart, $lineId);

        unset($this->cart, $this->cartContext);
        $this->dispatch('cart-updated');
    }

    public function clearCart(): void
    {
        if (! $this->cart) {
            return;
        }

        resolve(CartManager::class)->clear($this->cart);

        unset($this->cart, $this->cartContext);
        $this->dispatch('cart-updated');
    }

    public function render(): View
    {
        return view('pages.shop.cart')
            ->title(__('Cart'));
    }
}
