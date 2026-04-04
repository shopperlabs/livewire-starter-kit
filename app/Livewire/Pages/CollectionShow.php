<?php

declare(strict_types=1);

namespace App\Livewire\Pages;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;
use Shopper\Core\Models\Collection;

class CollectionShow extends Component
{
    use WithPagination;

    #[Locked]
    public Collection $collection;

    /** @return LengthAwarePaginator<int, \App\Models\Product> */
    #[Computed]
    public function products(): LengthAwarePaginator
    {
        $currencyCode = current_currency();
        $priceConstraint = fn ($q) => $q->whereRelation('currency', 'code', $currencyCode);

        return $this->collection->productsQuery()
            ->scopes('publish')
            ->with([
                'media',
                'brand',
                'prices' => $priceConstraint,
            ])
            ->latest()
            ->paginate(12);
    }

    public function render(): View
    {
        return view('pages.shop.collection')
            ->title($this->collection->name);
    }
}
