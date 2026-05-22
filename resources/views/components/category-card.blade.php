@blaze

@props([
    'category',
])

@php
    $thumbnailCollection = config('shopper.media.storage.thumbnail_collection');
    $image = $category->getFirstMediaUrl($thumbnailCollection);
    $fallback = shopper_fallback_url();
@endphp

<x-link
    :href="route('shop.category', $category)"
    {{ $attributes->twMerge(['class' => 'group relative flex flex-col items-center']) }}
>
    <div class="relative size-24 overflow-hidden rounded-full bg-zinc-100 ring-2 ring-transparent transition group-hover:ring-zinc-900 dark:bg-zinc-800 dark:group-hover:ring-white">
        <img
            src="{{ $image ?: $fallback }}"
            alt="{{ $category->name }}"
            class="size-full object-cover object-center"
            loading="lazy"
        />
    </div>
    <span class="mt-3 text-center text-sm font-medium text-zinc-900 transition group-hover:text-zinc-600 dark:text-white dark:group-hover:text-zinc-300">
        {{ $category->name }}
    </span>
</x-link>
