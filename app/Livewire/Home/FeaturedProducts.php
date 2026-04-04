<?php

declare(strict_types=1);

namespace App\Livewire\Home;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;

class FeaturedProducts extends Component
{
    public const int CACHE_TTL = 7200;

    /** @return Collection<int, Product> */
    #[Computed]
    public function products(): Collection
    {
        $currencyCode = current_currency();

        return Cache::remember(
            key: "home.featured_products.{$currencyCode}",
            ttl: self::CACHE_TTL,
            callback: fn (): Collection => Product::query()
                ->select('id', 'name', 'slug', 'brand_id')
                ->with(['brand', 'media'])
                ->withCurrentPrices()
                ->where('featured', true)
                ->scopes('publish')
                ->limit(8)
                ->get(),
        );
    }

    public function render(): View
    {
        return view('pages.home.featured-products');
    }
}
