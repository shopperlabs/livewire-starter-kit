<?php

declare(strict_types=1);

namespace App\Livewire\Pages;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CategoryIndex extends Component
{
    #[Computed]
    public function categories(): Collection
    {
        return Cache::remember('categories.index', 7200, fn (): Collection => Category::query()
            ->scopes('enabled')
            ->whereNull('parent_id')
            ->with('media')
            ->withCount(['products' => fn ($query) => $query->whereNull('sh_products.deleted_at')])
            ->orderBy('position')
            ->get());
    }

    public function render(): View
    {
        return view('pages.shop.categories')
            ->title(__('Categories'));
    }
}
