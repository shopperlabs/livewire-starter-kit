<?php

declare(strict_types=1);

namespace App\Livewire\Home;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ShopByCategory extends Component
{
    public const int CACHE_TTL = 7200;

    /** @return Collection<int, Category> */
    #[Computed]
    public function categories(): Collection
    {
        return Cache::remember(
            key: 'home.categories',
            ttl: self::CACHE_TTL,
            callback: fn (): Collection => Category::query()
                ->whereNull('parent_id')
                ->where('is_enabled', true)
                ->has('products')
                ->withCount(['products' => fn ($query) => $query->whereNull('sh_products.deleted_at')])
                ->orderByDesc('products_count')
                ->with('media')
                ->limit(8)
                ->get(),
        );
    }

    public function render(): View
    {
        return view('pages.home.shop-by-category');
    }
}
