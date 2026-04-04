@php
    $thumbnailCollection = config('shopper.media.storage.thumbnail_collection');
    $galleryCollection = config('shopper.media.storage.collection_name');
    $images = $product->getMedia($galleryCollection);
    $thumbnail = $product->getFirstMediaUrl($thumbnailCollection);
    $fallback = shopper_fallback_url();
@endphp

<div>
    <x-container class="py-8 sm:py-12">
        <nav class="flex items-center gap-2 text-sm text-zinc-500 mb-8">
            <x-link :href="route('home')" class="hover:text-zinc-900 dark:hover:text-white transition">{{ __('Home') }}</x-link>
            <span>/</span>
            <x-link :href="route('shop.index')" class="hover:text-zinc-900 dark:hover:text-white transition">{{ __('Shop') }}</x-link>
            <span>/</span>
            <span class="text-zinc-900 dark:text-white">{{ $product->name }}</span>
        </nav>

        <div class="lg:grid lg:grid-cols-2 lg:gap-x-12">
            <div x-data="{ activeImage: '{{ $thumbnail ?: ($images->first()?->getUrl() ?? $fallback) }}' }">
                <div class="aspect-square overflow-hidden rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                    <img :src="activeImage" alt="{{ $product->name }}" class="size-full object-cover object-center" />
                </div>

                @if($images->count() > 1)
                    <div class="mt-4 grid grid-cols-4 gap-3">
                        @if($thumbnail)
                            <button
                                type="button"
                                @click="activeImage = '{{ $thumbnail }}'"
                                class="aspect-square overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800 ring-2 ring-transparent focus:ring-zinc-900 dark:focus:ring-white"
                            >
                                <img src="{{ $thumbnail }}" alt="" class="size-full object-cover object-center" />
                            </button>
                        @endif
                        @foreach($images->take(3) as $image)
                            <button
                                type="button"
                                @click="activeImage = '{{ $image->getUrl() }}'"
                                class="aspect-square overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800 ring-2 ring-transparent focus:ring-zinc-900 dark:focus:ring-white"
                            >
                                <img src="{{ $image->getUrl() }}" alt="" class="size-full object-cover object-center" />
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="mt-8 lg:mt-0">
                <h1 class="text-3xl font-bold text-zinc-900 dark:text-white font-heading">{{ $product->name }}</h1>

                @php
                    $displayPrice = $selectedVariant?->getFormattedPrice() ?? $product->getFormattedPrice();
                @endphp
                <div class="mt-4">
                    <x-price-display :price="$displayPrice" size="lg" />
                </div>

                @if($hasStructuredAttributes && count($productOptions) > 0)
                    <div class="mt-6 space-y-5">
                        @foreach($productOptions as $option)
                            <div>
                                <h3 class="text-sm font-medium text-zinc-900 dark:text-white">{{ $option['name'] }}</h3>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach($option['values'] as $value)
                                        @php
                                            $isSelected = ($selectedOptions[$option['id']] ?? null) === $value['id'];
                                            $isAvailable = $availabilityMatrix[$option['id']][$value['id']] ?? true;
                                        @endphp

                                        @if($option['type'] === 'colorpicker')
                                            <button
                                                type="button"
                                                wire:click="selectOption({{ $option['id'] }}, {{ $value['id'] }})"
                                                @class([
                                                    'size-8 rounded-full border-2 transition',
                                                    'border-zinc-900 dark:border-white ring-2 ring-zinc-900 dark:ring-white ring-offset-2' => $isSelected,
                                                    'border-zinc-300 dark:border-zinc-600 hover:border-zinc-500' => !$isSelected && $isAvailable,
                                                    'border-zinc-200 dark:border-zinc-700 opacity-30 cursor-not-allowed' => !$isAvailable,
                                                ])
                                                style="background-color: {{ $value['key'] }}"
                                                @disabled(!$isAvailable)
                                                title="{{ $value['value'] }}"
                                            >
                                                <span class="sr-only">{{ $value['value'] }}</span>
                                            </button>
                                        @else
                                            <button
                                                type="button"
                                                wire:click="selectOption({{ $option['id'] }}, {{ $value['id'] }})"
                                                @class([
                                                    'rounded-lg border px-4 py-2 text-sm font-medium transition',
                                                    'border-zinc-900 bg-zinc-900 text-white dark:border-white dark:bg-white dark:text-zinc-900' => $isSelected,
                                                    'border-zinc-300 text-zinc-900 hover:border-zinc-500 dark:border-zinc-600 dark:text-white dark:hover:border-zinc-400' => !$isSelected && $isAvailable,
                                                    'border-zinc-200 text-zinc-300 dark:border-zinc-700 dark:text-zinc-600 cursor-not-allowed' => !$isAvailable,
                                                ])
                                                @disabled(!$isAvailable)
                                            >
                                                {{ $value['value'] }}
                                            </button>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="mt-8 flex items-center gap-4">
                    <div class="flex items-center rounded-lg border border-zinc-300 dark:border-zinc-600">
                        <button
                            type="button"
                            wire:click="$set('quantity', Math.max(1, $wire.quantity - 1))"
                            class="px-3 py-2 text-zinc-500 hover:text-zinc-900 dark:hover:text-white transition"
                            @disabled($quantity <= 1)
                        >
                            <x-flux::icon.minus variant="micro" class="size-4" />
                        </button>
                        <span class="min-w-[2rem] text-center text-sm font-medium text-zinc-900 dark:text-white">{{ $quantity }}</span>
                        <button
                            type="button"
                            wire:click="$set('quantity', $wire.quantity + 1)"
                            class="px-3 py-2 text-zinc-500 hover:text-zinc-900 dark:hover:text-white transition"
                        >
                            <x-flux::icon.plus variant="micro" class="size-4" />
                        </button>
                    </div>

                    <flux:button variant="primary" wire:click="addToCart" wire:loading.attr="disabled" class="flex-1">
                        <span wire:loading.remove wire:target="addToCart">{{ __('Add to Cart') }}</span>
                        <span wire:loading wire:target="addToCart">{{ __('Adding...') }}</span>
                    </flux:button>
                </div>

                @if($product->description)
                    <div class="mt-8 border-t border-zinc-200 dark:border-zinc-700 pt-8">
                        <h3 class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('Description') }}</h3>
                        <div class="mt-3 prose prose-sm prose-zinc dark:prose-invert max-w-none">
                            {!! str($product->description)->sanitizeHtml() !!}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if($product->relatedProducts->isNotEmpty())
            <section class="mt-16 border-t border-zinc-200 dark:border-zinc-700 pt-12">
                <h2 class="text-2xl font-bold text-zinc-900 dark:text-white font-heading">{{ __('Related Products') }}</h2>
                <div class="mt-6 grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4 xl:gap-x-6">
                    @foreach($product->relatedProducts as $relatedProduct)
                        <x-product-card :product="$relatedProduct" />
                    @endforeach
                </div>
            </section>
        @endif
    </x-container>
</div>
