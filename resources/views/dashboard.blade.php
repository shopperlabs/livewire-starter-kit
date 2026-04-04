<x-layouts::account :title="__('Dashboard')">
    <flux:heading size="xl" level="1">{{ __('Welcome back, :name', ['name' => auth()->user()->first_name]) }}</flux:heading>

    <div class="mt-8 grid gap-4 sm:grid-cols-3">
        <x-link :href="route('account.orders')">
            <x-card class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                    <x-flux::icon.shopping-bag variant="outline" class="size-5 text-zinc-600 dark:text-zinc-400" />
                </div>
                <div>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('Orders') }}</p>
                    <p class="text-xs text-zinc-500">{{ __('View your order history') }}</p>
                </div>
            </x-card>
        </x-link>

        <x-link :href="route('account.addresses')">
            <x-card class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                    <x-flux::icon.map-pin variant="outline" class="size-5 text-zinc-600 dark:text-zinc-400" />
                </div>
                <div>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('Addresses') }}</p>
                    <p class="text-xs text-zinc-500">{{ __('Manage your addresses') }}</p>
                </div>
            </x-card>
        </x-link>

        <x-link :href="route('profile.edit')">
            <x-card class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                    <x-flux::icon.user variant="outline" class="size-5 text-zinc-600 dark:text-zinc-400" />
                </div>
                <div>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('Profile') }}</p>
                    <p class="text-xs text-zinc-500">{{ __('Manage your account') }}</p>
                </div>
            </x-card>
        </x-link>
    </div>
</x-layouts::account>
