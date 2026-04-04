<div>
    <x-container class="py-8 sm:py-12">
        <flux:breadcrumbs class="mb-8">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>{{ __('Home') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('shop.categories') }}" wire:navigate>{{ __('Categories') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $category->name }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ $category->name }}</flux:heading>
                @if($category->description)
                    <flux:text class="mt-1">{{ strip_tags($category->description) }}</flux:text>
                @endif
            </div>

            <flux:select wire:model.live="sort" class="w-auto">
                <flux:select.option value="latest">{{ __('Newest') }}</flux:select.option>
                <flux:select.option value="name">{{ __('Name') }}</flux:select.option>
            </flux:select>
        </div>

        @if($this->products->isEmpty())
            <div class="mt-16 flex flex-col items-center justify-center text-center">
                <flux:icon.magnifying-glass variant="outline" class="size-12 text-zinc-300 dark:text-zinc-600" />
                <flux:heading size="sm" class="mt-4">{{ __('No products in this category') }}</flux:heading>
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
