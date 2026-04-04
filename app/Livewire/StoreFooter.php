<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;

class StoreFooter extends Component
{
    /** @return Collection<int, Category> */
    #[Computed]
    public function categories(): Collection
    {
        return Cache::remember(
            'footer.categories.'.app()->getLocale(),
            7200,
            fn (): Collection => Category::query()
                ->scopes('enabled')
                ->whereNull('parent_id')
                ->orderBy('position')
                ->take(6)
                ->get(['id', 'name', 'slug']),
        );
    }

    public function render(): View
    {
        return view('livewire.store-footer');
    }
}
