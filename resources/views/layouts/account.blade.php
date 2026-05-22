<x-layouts::store :title="$title ?? null">
    <x-container class="py-10 sm:py-16 lg:py-24">
        <div class="grid grid-cols-1 lg:grid-cols-5 lg:gap-x-12">
            <div class="lg:col-span-1">
                <h2 class="hidden font-heading text-xl/6 font-medium text-zinc-900 lg:block dark:text-white">
                    {{ __('My account') }}
                </h2>

                <nav role="navigation" class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-4 sm:-mx-6 sm:px-6 lg:hidden">
                    <x-layouts::account.nav-link :href="route('dashboard')" :title="__('Overview')" :exact="true" />
                    <x-layouts::account.nav-link :href="route('account.orders')" :title="__('Orders')" />
                    <x-layouts::account.nav-link :href="route('account.addresses')" :title="__('Addresses')" />
                    <x-layouts::account.nav-link :href="route('profile.edit')" :title="__('Profile')" />
                </nav>

                <nav role="navigation" class="mt-10 hidden flex-col space-y-4 lg:flex">
                    <x-layouts::account.nav-link :href="route('dashboard')" :title="__('Overview')" :exact="true" />
                    <x-layouts::account.nav-link :href="route('account.orders')" :title="__('Orders')" />
                    <x-layouts::account.nav-link :href="route('account.addresses')" :title="__('Addresses')" />
                    <x-layouts::account.nav-link :href="route('profile.edit')" :title="__('Profile')" />

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-red-600 transition hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                            {{ __('Log out') }}
                        </button>
                    </form>
                </nav>
            </div>

            <div class="lg:col-span-4">
                {{ $slot }}
            </div>
        </div>
    </x-container>
</x-layouts::store>
