@blaze

@props([
    'item',
    'currencyCode',
])

@php
    $thumbnailCollection = config('shopper.media.storage.thumbnail_collection');
    $purchasable = $item->product;
    $product = $purchasable instanceof \App\Models\ProductVariant
        ? $purchasable->product
        : $purchasable;
    $image = $purchasable?->getFirstMediaUrl($thumbnailCollection) ?: $product?->getFirstMediaUrl($thumbnailCollection);
    $fallback = shopper_fallback_url();
@endphp

<div {{ $attributes->twMerge(['class' => 'flex gap-4']) }}>
    <div class="size-24 shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800 ring-1 ring-zinc-200 dark:ring-zinc-700">
        <img
            src="{{ $image ?: $fallback }}"
            alt="{{ $item->name }}"
            class="size-full object-cover object-center"
            loading="lazy"
        />
    </div>
    <div class="flex-1 min-w-0">
        @if ($product)
            <x-link :href="route('shop.product', $product)" class="text-sm font-medium text-zinc-900 dark:text-white hover:underline font-heading line-clamp-2">
                {{ $item->name }}
            </x-link>
        @else
            <p class="text-sm font-medium text-zinc-900 dark:text-white font-heading line-clamp-2">{{ $item->name }}</p>
        @endif

        @if ($item->sku)
            <p class="mt-0.5 text-xs text-zinc-500">{{ __('SKU: :sku', ['sku' => $item->sku]) }}</p>
        @endif
        <p class="mt-1 text-sm text-zinc-500">
            {{ __('Qty: :qty', ['qty' => $item->quantity]) }} &middot; {{ shopper_money_format($item->unit_price_amount, $currencyCode) }}
        </p>
    </div>
    <p class="text-sm font-medium text-zinc-900 dark:text-white shrink-0">
        {{ shopper_money_format($item->unit_price_amount * $item->quantity, $currencyCode) }}
    </p>
</div>
