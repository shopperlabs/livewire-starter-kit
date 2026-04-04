<x-layouts::store :title="$title ?? null">
    <x-container class="py-10 sm:py-16 lg:py-24">
        <div class="grid grid-cols-1 lg:grid-cols-5 lg:gap-x-12">
            <div class="lg:col-span-1">
                <h2 class="hidden text-xl font-medium leading-6 text-zinc-900 dark:text-white font-heading lg:block">
                    {{ __('My account') }}
                </h2>

                <nav role="navigation" class="flex overflow-x-auto gap-2 pb-4 -mx-4 px-4 sm:-mx-6 sm:px-6 lg:hidden">
                    <x-layouts::account.nav-link :href="route('dashboard')" :title="__('Overview')" :exact="true" />
                    <x-layouts::account.nav-link :href="route('account.orders')" :title="__('Orders')" />
                    <x-layouts::account.nav-link :href="route('account.addresses')" :title="__('Addresses')" />
                    <x-layouts::account.nav-link :href="route('profile.edit')" :title="__('Profile')" />
                </nav>

                <nav role="navigation" class="hidden mt-10 lg:flex flex-col space-y-4">
                    <x-layouts::account.nav-link :href="route('dashboard')" :title="__('Overview')" :exact="true" />
                    <x-layouts::account.nav-link :href="route('account.orders')" :title="__('Orders')" />
                    <x-layouts::account.nav-link :href="route('account.addresses')" :title="__('Addresses')" />
                    <x-layouts::account.nav-link :href="route('profile.edit')" :title="__('Profile')" />

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 transition">
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
