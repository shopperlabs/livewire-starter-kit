<div>
    <x-container class="py-8 sm:py-12">
        <nav class="flex items-center gap-2 text-sm text-zinc-500 mb-8">
            <x-link :href="route('home')" class="hover:text-zinc-900 dark:hover:text-white transition">{{ __('Home') }}</x-link>
            <span>/</span>
            <span class="text-zinc-900 dark:text-white">{{ $collection->name }}</span>
        </nav>

        <div>
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white font-heading">{{ $collection->name }}</h1>
            @if($collection->description)
                <p class="mt-2 text-sm text-zinc-500 max-w-2xl">{{ strip_tags($collection->description) }}</p>
            @endif
        </div>

        @if($this->products->isEmpty())
            <div class="mt-16 flex flex-col items-center justify-center text-center">
                <h3 class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('No products in this collection') }}</h3>
            </div>
        @else
            <div class="mt-8 grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4 xl:gap-x-6">
                @foreach($this->products as $product)
                    <x-product-card :$product />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $this->products->links() }}
            </div>
        @endif
    </x-container>
</div>
