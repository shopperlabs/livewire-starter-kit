<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasProductPricing;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Shopper\Models\Product as Model;

final class Product extends Model
{
    use HasProductPricing;

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function withCurrentPrices(Builder $query): Builder
    {
        return $query->with([
            'prices' => fn ($q) => $q->whereRelation('currency', 'code', current_currency()),
        ]);
    }
}
