@blaze

@props([
    'product',
])

@php
    $thumbnailCollection = config('shopper.media.storage.thumbnail_collection');
    $image = $product->getFirstMediaUrl($thumbnailCollection);
    $fallback = shopper_fallback_url();
    $price = $product->getFormattedPrice();
@endphp

<div {{ $attributes->twMerge(['class' => 'group relative']) }}>
    <div class="relative aspect-square overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
        <img
            src="{{ $image ?: $fallback }}"
            alt="{{ $product->name }}"
            class="size-full object-cover object-center transition duration-300 group-hover:scale-105"
            loading="lazy"
        />
        @if ($price?->percentage && $price->percentage > 0)
            <span class="absolute top-3 left-3 inline-flex items-center rounded-full bg-red-500 px-2.5 py-0.5 text-xs font-medium text-white">
                -{{ $price->percentage }}%
            </span>
        @endif
    </div>

    <h3 class="mt-3 text-sm text-zinc-700 dark:text-zinc-300 group-hover:text-zinc-900 dark:group-hover:text-white transition">
        <x-link :href="route('shop.product', $product)">
            <span class="absolute inset-0"></span>
            {{ $product->name }}
        </x-link>
    </h3>

    @if ($product->brand_id && $product->relationLoaded('brand'))
        <p class="mt-0.5 text-xs text-zinc-500">{{ $product->brand->name }}</p>
    @endif

    @if (! $product->isVariant() && $product->prices->isNotEmpty())
        <x-price-display :$price class="mt-1" />
    @endif
</div>
