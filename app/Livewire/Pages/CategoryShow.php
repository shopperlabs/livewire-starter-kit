<?php

declare(strict_types=1);

namespace App\Livewire\Pages;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CategoryShow extends Component
{
    use WithPagination;

    #[Locked]
    public Category $category;

    #[Url]
    public string $sort = 'latest';

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    /** @return LengthAwarePaginator<int, Product> */
    #[Computed]
    public function products(): LengthAwarePaginator
    {
        $query = Product::query()
            ->scopes('publish')
            ->whereHas('categories', fn ($q) => $q->where('id', $this->category->id))
            ->with(['media', 'brand'])
            ->withCurrentPrices();

        $query = match ($this->sort) {
            'name' => $query->orderBy('name'),
            default => $query->latest(),
        };

        return $query->paginate(12);
    }

    public function render(): View
    {
        return view('pages.shop.category')
            ->title($this->category->name);
    }
}
