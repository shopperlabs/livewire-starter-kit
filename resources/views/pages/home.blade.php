<div>
    <section class="relative overflow-hidden bg-zinc-100 dark:bg-zinc-950">
        <x-container>
            <div class="relative py-20 sm:py-28 lg:py-36">
                <div class="max-w-xl">
                    <h1 class="text-5xl font-extrabold tracking-tight text-zinc-900 dark:text-white font-heading sm:text-6xl lg:text-7xl">
                        {{ __('Explore Premium Products') }}
                    </h1>
                    <p class="mt-6 text-lg text-zinc-600 dark:text-zinc-400">
                        {{ __('Discover our curated collection of high-quality products, handpicked just for you.') }}
                    </p>
                    <div class="mt-8 flex items-center gap-4">
                        <flux:button variant="primary" :href="route('shop.index')" wire:navigate icon:trailing="arrow-right">
                            {{ __('Shop Now') }}
                        </flux:button>
                        <flux:button :href="route('shop.categories')" wire:navigate icon:trailing="arrow-right">
                            {{ __('Categories') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        </x-container>
    </section>

    <x-container class="py-10 sm:py-12">
        <x-trust-badges />
    </x-container>

    <livewire:home.featured-collections />

    <livewire:home.featured-products />

    <livewire:home.shop-by-category />
</div>
