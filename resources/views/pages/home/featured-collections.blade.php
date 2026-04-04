<div>
    @if ($this->collections->isNotEmpty())
        <section class="py-12 sm:py-16 lg:pb-24">
            <x-container>
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-zinc-900 dark:text-white font-heading">{{ __('Shop by Collections') }}</h2>
                </div>
                <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($this->collections as $collection)
                        <x-collection-banner :$collection />
                    @endforeach
                </div>
            </x-container>
        </section>
    @endif
</div>
