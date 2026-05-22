<div>
    @if($this->products->isNotEmpty())
        <section class="bg-zinc-50 py-12 sm:py-16 lg:py-20 dark:bg-zinc-900/50">
            <x-container>
                <div class="flex items-center justify-between">
                    <h2 class="font-heading text-2xl font-bold text-zinc-900 dark:text-white">{{ __('Featured Products') }}</h2>
                    <x-link :href="route('shop.index')" class="text-sm font-medium text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                        {{ __('View all') }}
                        <span aria-hidden="true"> &rarr;</span>
                    </x-link>
                </div>
                <div class="mt-10 grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4 xl:gap-x-6">
                    @foreach ($this->products as $product)
                        <x-product-card :$product />
                    @endforeach
                </div>
            </x-container>
        </section>
    @endif
</div>
