<div>
    <x-container class="py-8 sm:py-12">
        <div class="text-center max-w-xl mx-auto">
            <flux:heading size="xl">{{ __('Search') }}</flux:heading>
            <div class="mt-6">
                <flux:input
                    type="search"
                    wire:model.live.debounce.300ms="query"
                    placeholder="{{ __('Search for products...') }}"
                    autofocus
                    icon="magnifying-glass"
                />
            </div>
        </div>

        <div class="mt-10">
            @if($this->products === null)
                <flux:text class="text-center">{{ __('Type at least 2 characters to search.') }}</flux:text>
            @elseif($this->products->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    <flux:icon.magnifying-glass variant="outline" class="size-12 text-zinc-300 dark:text-zinc-600" />
                    <flux:heading size="sm" class="mt-4">{{ __('No results found') }}</flux:heading>
                    <flux:text size="sm" class="mt-1">{{ __('Try a different search term.') }}</flux:text>
                </div>
            @else
                <flux:text size="sm" class="mb-6">
                    {{ trans_choice(':count result|:count results', $this->products->total(), ['count' => $this->products->total()]) }}
                    {{ __('for') }} "<span class="font-medium text-zinc-900 dark:text-white">{{ $query }}</span>"
                </flux:text>

                <div class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4 xl:gap-x-6">
                    @foreach($this->products as $product)
                        <x-product-card :$product />
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $this->products->links() }}
                </div>
            @endif
        </div>
    </x-container>
</div>
