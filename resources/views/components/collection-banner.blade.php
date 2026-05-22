@blaze

@props([
    'collection',
    'reverse' => false,
])

@php
    $thumbnailCollection = config('shopper.media.storage.thumbnail_collection');
    $image = $collection->getFirstMediaUrl($thumbnailCollection);
    $fallback = shopper_fallback_url();
@endphp

<x-link
    :href="route('shop.collection', $collection)"
    {{ $attributes->twMerge(['class' => 'group relative block overflow-hidden rounded-xl bg-zinc-100 dark:bg-zinc-800']) }}
>
    <div class="aspect-video sm:aspect-3/2">
        <img
            src="{{ $image ?: $fallback }}"
            alt="{{ $collection->name }}"
            class="size-full object-cover object-center transition duration-500 group-hover:scale-105"
            loading="lazy"
        />
        <div class="absolute inset-0 bg-linear-to-t from-zinc-900/80 via-zinc-900/40 to-transparent"></div>
    </div>

    <div class="absolute inset-x-0 bottom-0 p-6">
        <h3 class="font-heading text-lg font-semibold text-white">{{ $collection->name }}</h3>

        @if ($collection->description)
            <p class="mt-0.5 line-clamp-2 text-sm text-zinc-200">{{ strip_tags($collection->description) }}</p>
        @endif

        <span class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-white transition-all group-hover:gap-2.5">
            {{ __('Shop now') }}
            <x-flux::icon.arrow-right variant="micro" class="size-4" aria-hidden="true" />
        </span>
    </div>
</x-link>
