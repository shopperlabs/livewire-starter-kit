@blaze(fold: true)

<div {{ $attributes->twMerge(['class' => 'grid grid-cols-2 gap-6 sm:grid-cols-4']) }}>
    <div class="flex flex-col items-center text-center">
        <div class="flex size-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
            <x-flux::icon.truck variant="outline" class="size-6 text-zinc-600 dark:text-zinc-400" />
        </div>
        <h3 class="mt-3 text-sm font-medium text-zinc-900 dark:text-white font-heading">{{ __('Free Shipping') }}</h3>
        <p class="mt-1 text-xs text-zinc-500">{{ __('On orders over $50') }}</p>
    </div>

    <div class="flex flex-col items-center text-center">
        <div class="flex size-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
            <x-flux::icon.shield-check variant="outline" class="size-6 text-zinc-600 dark:text-zinc-400" />
        </div>
        <h3 class="mt-3 text-sm font-medium text-zinc-900 dark:text-white font-heading">{{ __('Secure Payment') }}</h3>
        <p class="mt-1 text-xs text-zinc-500">{{ __('100% secure checkout') }}</p>
    </div>

    <div class="flex flex-col items-center text-center">
        <div class="flex size-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
            <x-flux::icon.arrow-path variant="outline" class="size-6 text-zinc-600 dark:text-zinc-400" />
        </div>
        <h3 class="mt-3 text-sm font-medium text-zinc-900 dark:text-white font-heading">{{ __('Easy Returns') }}</h3>
        <p class="mt-1 text-xs text-zinc-500">{{ __('30-day return policy') }}</p>
    </div>

    <div class="flex flex-col items-center text-center">
        <div class="flex size-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
            <x-flux::icon.chat-bubble-left-right variant="outline" class="size-6 text-zinc-600 dark:text-zinc-400" />
        </div>
        <h3 class="mt-3 text-sm font-medium text-zinc-900 dark:text-white font-heading">{{ __('24/7 Support') }}</h3>
        <p class="mt-1 text-xs text-zinc-500">{{ __('Dedicated support') }}</p>
    </div>
</div>
