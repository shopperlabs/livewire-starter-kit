<?php

declare(strict_types=1);

namespace App\Livewire\Home;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Shopper\Core\Models\Collection;

class FeaturedCollections extends Component
{
    public const int CACHE_TTL = 7200;

    /** @return EloquentCollection<int, Collection> */
    #[Computed]
    public function collections(): EloquentCollection
    {
        return Cache::remember(
            key: 'home.collections',
            ttl: self::CACHE_TTL,
            callback: fn (): EloquentCollection => Collection::query()
                ->has('products')
                ->withCount('products')
                ->with('media')
                ->orderByDesc('products_count')
                ->limit(3)
                ->get(),
        );
    }

    public function render(): View
    {
        return view('pages.home.featured-collections');
    }
}
