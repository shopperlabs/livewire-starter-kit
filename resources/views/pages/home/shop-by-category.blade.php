<div>
    @if ($this->categories->isNotEmpty())
        <section class="py-12 sm:py-16 lg:py-20">
            <x-container>
                <div class="text-center">
                    <h2 class="text-2xl font-bold text-zinc-900 dark:text-white font-heading">{{ __('Shop by Category') }}</h2>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Browse our wide range of categories') }}</p>
                </div>
                <div class="mt-8 grid grid-cols-3 gap-6 sm:grid-cols-4 lg:grid-cols-8">
                    @foreach ($this->categories as $category)
                        <x-category-card :$category />
                    @endforeach
                </div>
            </x-container>
        </section>
    @endif
</div>
