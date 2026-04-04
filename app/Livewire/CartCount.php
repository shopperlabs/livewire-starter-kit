<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Shopper\Cart\CartSessionManager;

class CartCount extends Component
{
    public int $count = 0;

    public function mount(): void
    {
        $this->refreshCount();
    }

    #[On('cart-updated')]
    public function refreshCount(): void
    {
        $cart = resolve(CartSessionManager::class)->current();

        $this->count = (int) ($cart?->lines->sum('quantity') ?? 0);
    }

    public function render(): View
    {
        return view('livewire.cart-count');
    }
}
