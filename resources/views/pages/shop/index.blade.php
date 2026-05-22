<div>
    <x-container class="py-8 sm:py-12">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl">{{ __('Shop') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Browse our entire collection') }}</flux:text>
            </div>

            <flux:select wire:model.live="sort" class="w-auto">
                <flux:select.option value="latest">{{ __('Newest') }}</flux:select.option>
                <flux:select.option value="name">{{ __('Name') }}</flux:select.option>
            </flux:select>
        </div>

        <div class="mt-8 lg:grid lg:grid-cols-4 lg:gap-x-8">
            <aside class="hidden lg:block">
                <flux:field class="mb-6">
                    <flux:label>{{ __('Search') }}</flux:label>
                    <flux:input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search products...') }}" icon="magnifying-glass" />
                </flux:field>

                <div>
                    <flux:heading size="sm">{{ __('Categories') }}</flux:heading>
                    <ul role="list" class="mt-3 space-y-2">
                        <li>
                            <button
                                type="button"
                                wire:click="$set('category', null)"
                                @class([
                                    'text-sm transition',
                                    'font-medium text-zinc-900 dark:text-white' => !$category,
                                    'text-zinc-500 hover:text-zinc-900 dark:hover:text-white' => $category,
                                ])
                            >
                                {{ __('All') }}
                            </button>
                        </li>
                        @foreach($this->categories as $cat)
                            <li>
                                <button
                                    type="button"
                                    wire:click="$set('category', {{ $cat->id }})"
                                    @class([
                                        'text-sm transition',
                                        'font-medium text-zinc-900 dark:text-white' => $category === $cat->id,
                                        'text-zinc-500 hover:text-zinc-900 dark:hover:text-white' => $category !== $cat->id,
                                    ])
                                >
                                    {{ $cat->name }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </aside>

            <div class="lg:col-span-3">
                <div class="mb-6 lg:hidden">
                    <flux:input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search products...') }}" icon="magnifying-glass" />
                </div>

                @if($this->products->isEmpty())
                    <div class="flex flex-col items-center justify-center py-16 text-center">
                        <flux:icon.magnifying-glass variant="outline" class="size-12 text-zinc-300 dark:text-zinc-600" />
                        <flux:heading size="sm" class="mt-4">{{ __('No products found') }}</flux:heading>
                        <flux:text size="sm" class="mt-1">{{ __('Try adjusting your search or filters.') }}</flux:text>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-2 lg:grid-cols-3 xl:gap-x-6">
                        @foreach($this->products as $product)
                            <x-product-card :$product />
                        @endforeach
                    </div>

                    <div class="mt-8">
                        {{ $this->products->links() }}
                    </div>
                @endif
            </div>
        </div>
    </x-container>
</div>
