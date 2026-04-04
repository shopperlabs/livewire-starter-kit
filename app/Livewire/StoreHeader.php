<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Shopper\Core\Models\Category;

class StoreHeader extends Component
{
    /** @return Collection<int, Category> */
    #[Computed]
    public function categories(): Collection
    {
        return Cache::remember(
            'nav.categories.'.app()->getLocale(),
            7200,
            fn () => Category::query()
                ->scopes('enabled')
                ->whereNull('parent_id')
                ->orderBy('position')
                ->take(4)
                ->get(['id', 'name', 'slug']),
        );
    }

    public function render(): View
    {
        return view('livewire.store-header');
    }
}
