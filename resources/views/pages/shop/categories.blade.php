<div>
    <x-container class="py-8 sm:py-12">
        <div class="text-center">
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white font-heading">{{ __('Categories') }}</h1>
            <p class="mt-2 text-sm text-zinc-500">{{ __('Browse products by category') }}</p>
        </div>

        @if($this->categories->isEmpty())
            <div class="mt-16 flex flex-col items-center justify-center text-center">
                <h3 class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('No categories found') }}</h3>
            </div>
        @else
            <div class="mt-10 grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4">
                @foreach($this->categories as $category)
                    @php
                        $thumbnailCollection = config('shopper.media.storage.thumbnail_collection');
                        $image = $category->getFirstMediaUrl($thumbnailCollection);
                        $fallback = shopper_fallback_url();
                    @endphp

                    <a
                        href="{{ route('shop.category', $category) }}"
                        wire:navigate
                        class="group relative overflow-hidden rounded-2xl bg-zinc-100 dark:bg-zinc-800"
                    >
                        <div class="aspect-[4/3]">
                            <img
                                src="{{ $image ?: $fallback }}"
                                alt="{{ $category->name }}"
                                class="size-full object-cover object-center transition duration-500 group-hover:scale-105"
                                loading="lazy"
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-zinc-900/70 to-transparent"></div>
                        </div>
                        <div class="absolute inset-x-0 bottom-0 p-4">
                            <h3 class="text-base font-semibold text-white">{{ $category->name }}</h3>
                            <p class="mt-0.5 text-xs text-zinc-300">
                                {{ trans_choice(':count product|:count products', $category->products_count, ['count' => $category->products_count]) }}
                            </p>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </x-container>
</div>
