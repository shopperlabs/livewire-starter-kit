<?php

declare(strict_types=1);

namespace App\Livewire\Pages;

use App\Actions\Cart\AddToCart;
use App\Actions\Product\BuildVariantOptions;
use App\Actions\Product\ResolveVariantAvailability;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Shopper\Cart\Exceptions\InsufficientStockException;

class ProductShow extends Component
{
    #[Locked]
    public Product $product;

    public ?ProductVariant $selectedVariant = null;

    public int $quantity = 1;

    /** @var array<int, int> */
    public array $selectedOptions = [];

    /** @var array<int, array<int, bool>> */
    public array $availabilityMatrix = [];

    /** @var array<int, array{id: int, name: string, slug: string, type: string, values: array}> */
    #[Locked]
    public array $productOptions = [];

    /** @var array<string, int> */
    #[Locked]
    public array $variantIndex = [];

    /** @var array<int, array{id: int, values: array, stock: int, allow_backorder: bool}> */
    #[Locked]
    public array $variantMap = [];

    public bool $hasStructuredAttributes = false;

    public function mount(): void
    {
        abort_unless($this->product->isPublished(), 404);

        $currencyCode = current_currency();
        $priceConstraint = fn ($q) => $q->whereRelation('currency', 'code', $currencyCode);

        $this->product->load([
            'brand',
            'media',
            'prices' => $priceConstraint,
            'relatedProducts.brand',
            'relatedProducts.media',
            'relatedProducts.prices' => $priceConstraint,
            'variants.media',
            'variants.values.attribute',
            'variants.prices' => $priceConstraint,
        ]);

        if ($this->product->canUseVariants() && $this->product->variants->isNotEmpty()) {
            ProductVariant::loadCurrentStock($this->product->variants); // @phpstan-ignore argument.type

            $options = resolve(BuildVariantOptions::class)->handle($this->product);

            $this->productOptions = $options['productOptions'];
            $this->variantIndex = $options['variantIndex'];
            $this->variantMap = $options['variantMap'];
            $this->hasStructuredAttributes = $options['hasStructuredAttributes'];
            $this->availabilityMatrix = $options['availabilityMatrix'];
        }
    }

    public function selectOption(int $attributeId, int $valueId): void
    {
        $this->selectedOptions[$attributeId] = $valueId;

        if ($this->hasStructuredAttributes) {
            $this->availabilityMatrix = resolve(ResolveVariantAvailability::class)
                ->handle($this->variantMap, $this->selectedOptions, $this->productOptions);
        }

        $this->resolveSelectedVariant();
    }

    public function addToCart(): void
    {
        $this->quantity = max(1, min($this->quantity, 10));

        if ($this->product->canUseVariants() && $this->product->variants->isNotEmpty() && ! $this->selectedVariant) {
            $this->dispatch('notify', type: 'error', message: __('Please select all options.'));

            return;
        }

        try {
            resolve(AddToCart::class)->handle($this->product, $this->selectedVariant);
            $this->dispatch('cart-updated');
            $this->dispatch('notify', type: 'success', message: __('Product added to cart!'));
        } catch (InsufficientStockException) {
            $this->dispatch('notify', type: 'error', message: __('Insufficient stock available.'));
        }
    }

    public function render(): View
    {
        return view('pages.shop.show')
            ->title($this->product->name);
    }

    private function resolveSelectedVariant(): void
    {
        if (! $this->hasStructuredAttributes || count($this->selectedOptions) !== count($this->productOptions)) {
            $this->selectedVariant = null;

            return;
        }

        $selectedValues = array_values($this->selectedOptions);
        sort($selectedValues);
        $key = implode('-', $selectedValues);

        $variantId = $this->variantIndex[$key] ?? null;

        if (! $variantId) {
            $this->selectedVariant = null;

            return;
        }

        $currencyCode = current_currency();

        $this->selectedVariant = ProductVariant::with([
            'media',
            'prices' => fn ($q) => $q->whereRelation('currency', 'code', $currencyCode),
        ])->find($variantId);
    }
}
