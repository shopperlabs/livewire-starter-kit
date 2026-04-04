@blaze

@props([
    'message' => null,
])

@if ($message)
    <div
        x-data="{ dismissed: false }"
        x-show="!dismissed"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        {{ $attributes->twMerge(['class' => 'relative bg-zinc-900 dark:bg-white']) }}
    >
        <x-container>
            <div class="flex items-center justify-center py-2">
                <p class="text-center text-xs font-medium text-white dark:text-zinc-900 sm:text-sm">
                    {{ $message }}
                </p>
                <button
                    type="button"
                    @click="dismissed = true"
                    class="absolute right-4 text-white/60 hover:text-white dark:text-zinc-400 dark:hover:text-zinc-900"
                >
                    <span class="sr-only">{{ __('Dismiss') }}</span>
                    <x-flux::icon.x-mark variant="micro" class="size-4" aria-hidden="true" />
                </button>
            </div>
        </x-container>
    </div>
@endif
