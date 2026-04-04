@blaze

@props([
    'price' => null,
    'size' => 'sm',
])

@php
    $textSize = match($size) {
        'lg' => 'text-2xl',
        'md' => 'text-lg',
        default => 'text-sm',
    };
@endphp

<div {{ $attributes->twMerge(['class' => $textSize]) }}>
    @if ($price)
        <p class="flex items-center gap-2">
            <span class="font-semibold text-zinc-900 dark:text-white">{{ $price->amount->formatted }}</span>

            @if ($taxLabel = current_tax_label())
                <span class="text-xs text-zinc-500">{{ $taxLabel }}</span>
            @endif
        </p>

        @if ($price->percentage && $price->percentage > 0)
            <p class="flex items-center gap-1.5 mt-0.5 sm:mt-0 sm:inline-flex">
                <span class="sr-only">{{ __('Original :') }}</span>
                <span class="text-zinc-400 line-through">{{ $price->compare->formatted }}</span>
                <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">
                    -{{ $price->percentage }}%
                </span>
            </p>
        @endif
    @else
        <p class="font-semibold text-zinc-900 dark:text-white">
            {{ __('Price unavailable') }}
        </p>
    @endif
</div>
