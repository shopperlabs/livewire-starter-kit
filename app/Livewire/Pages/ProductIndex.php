<?php

declare(strict_types=1);

namespace App\Livewire\Pages;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ProductIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $sort = 'latest';

    #[Url]
    public ?int $category = null;

    #[Url]
    public string $search = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'category', 'sort'])) {
            $this->resetPage();
        }
    }

    /**
     * @return EloquentCollection<int, Category>
     */
    #[Computed]
    public function categories(): EloquentCollection
    {
        return Cache::remember(
            'shop.sidebar_categories',
            7200,
            fn (): EloquentCollection => Category::query()
                ->scopes('enabled')
                ->whereNull('parent_id')
                ->orderBy('position')
                ->get(['id', 'name', 'slug'])
        );
    }

    /** @return LengthAwarePaginator<int, Product> */
    #[Computed]
    public function products(): LengthAwarePaginator
    {
        $query = Product::query()
            ->scopes('publish')
            ->with(['media', 'brand'])
            ->withCurrentPrices();

        if ($this->search !== '') {
            $escaped = str_replace(['%', '_'], ['\%', '\_'], $this->search);
            $query->where('name', 'like', "%{$escaped}%");
        }

        if ($this->category) {
            $query->whereHas('categories', fn ($q) => $q->where('id', $this->category));
        }

        $query = match ($this->sort) {
            'name' => $query->orderBy('name'),
            default => $query->latest(),
        };

        return $query->paginate(12);
    }

    public function render(): View
    {
        return view('pages.shop.index')
            ->title(__('Shop'));
    }
}
