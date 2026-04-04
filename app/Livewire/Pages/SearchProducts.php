<?php

declare(strict_types=1);

namespace App\Livewire\Pages;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SearchProducts extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $query = '';

    public function updatedQuery(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function products(): ?LengthAwarePaginator
    {
        if (mb_strlen($this->query) < 2) {
            return null;
        }

        $escaped = str_replace(['%', '_'], ['\%', '\_'], $this->query);

        return Product::query()
            ->scopes('publish')
            ->where(fn ($q) => $q
                ->where('name', 'like', "%{$escaped}%")
                ->orWhere('description', 'like', "%{$escaped}%")
                ->orWhere('sku', 'like', "%{$escaped}%"))
            ->with(['media', 'brand'])
            ->withCurrentPrices()
            ->latest()
            ->paginate(12);
    }

    public function render(): View
    {
        return view('pages.shop.search')
            ->title(__('Search'));
    }
}
