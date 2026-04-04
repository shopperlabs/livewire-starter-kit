<header
    x-data="{ mobileOpen: false }"
    class="sticky top-0 z-30 bg-white/80 border-b border-zinc-200 backdrop-blur-xl dark:bg-zinc-900 dark:border-white/10"
>
    <x-announcement-bar :message="__('Summer Sale For All Swim Suits And Free Express Delivery - OFF 50%!')" />
    <x-container>
        <div class="flex items-center justify-between h-16">
            <button
                type="button"
                @click="mobileOpen = !mobileOpen"
                class="lg:hidden -ml-2 rounded-md p-2 text-zinc-500 hover:text-zinc-900 dark:hover:text-white"
            >
                <span class="sr-only">{{ __('Open menu') }}</span>
                <x-flux::icon.bars-3 x-show="!mobileOpen" variant="outline" class="size-5" aria-hidden="true" />
                <x-flux::icon.x-mark x-show="mobileOpen" x-cloak variant="outline" class="size-5" aria-hidden="true" />
            </button>

            <x-link :href="route('home')" class="flex items-center gap-2">
                <x-brand.icon class="size-8 fill-current text-black dark:text-white" aria-hidden="true" />
            </x-link>

            <nav role="navigation" class="hidden lg:flex items-center gap-6">
                @php
                    $navItems = [
                        ['href' => route('home'), 'label' => __('Home'), 'active' => request()->routeIs('home')],
                        ['href' => route('shop.index'), 'label' => __('Shop'), 'active' => request()->routeIs('shop.index')],
                    ];
                @endphp

                @foreach ($navItems as $item)
                    <x-link
                        :href="$item['href']"
                        @class([
                            'text-sm transition',
                            'font-medium text-zinc-900 dark:text-white' => $item['active'],
                            'text-zinc-500 hover:text-zinc-900 dark:hover:text-white' => ! $item['active'],
                        ])
                    >
                        {{ $item['label'] }}
                    </x-link>
                @endforeach

                @foreach ($this->categories as $category)
                    <x-link
                        :href="route('shop.category', $category)"
                        @class([
                            'text-sm transition',
                            'font-medium text-zinc-900 dark:text-white' => request()->routeIs('shop.category') && request()->route('category')?->is($category),
                            'text-zinc-500 hover:text-zinc-900 dark:hover:text-white' => ! (request()->routeIs('shop.category') && request()->route('category')?->is($category)),
                        ])
                    >
                        {{ $category->name }}
                    </x-link>
                @endforeach
            </nav>

            <div class="flex items-center gap-4">
                <x-link :href="route('shop.search')" class="text-zinc-500 hover:text-zinc-900 dark:hover:text-white transition">
                    <span class="sr-only">{{ __('Search') }}</span>
                    <x-flux::icon.magnifying-glass variant="outline" class="size-5" aria-hidden="true" />
                </x-link>

                <x-link :href="route('shop.cart')" class="relative text-zinc-500 hover:text-zinc-900 dark:hover:text-white transition">
                    <span class="sr-only">{{ __('Cart') }}</span>
                    <x-flux::icon.shopping-bag variant="outline" class="size-5" aria-hidden="true" />
                    <livewire:cart-count />
                </x-link>

                <x-link
                    :href="auth()->check() ? route('dashboard') : route('login')"
                    class="hidden lg:inline-flex text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white transition"
                >
                    <x-flux::icon.user variant="outline" class="size-5" aria-hidden="true" />
                </x-link>
            </div>
        </div>
    </x-container>

    <div x-show="mobileOpen" x-cloak x-transition class="lg:hidden border-t border-zinc-200 dark:border-zinc-700">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 py-4 space-y-3">
            <x-link :href="route('home')" class="block text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white" @click="mobileOpen = false">
                {{ __('Home') }}
            </x-link>
            <x-link :href="route('shop.index')" class="block text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white" @click="mobileOpen = false">
                {{ __('Shop') }}
            </x-link>

            @foreach ($this->categories as $category)
                <x-link :href="route('shop.category', $category)" class="block text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white" @click="mobileOpen = false">
                    {{ $category->name }}
                </x-link>
            @endforeach

            <div class="border-t border-zinc-200 dark:border-zinc-700 pt-3 space-y-3">
                @auth
                    <x-link :href="route('dashboard')" class="block text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white" @click="mobileOpen = false">
                        {{ __('My account') }}
                    </x-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                            {{ __('Log out') }}
                        </button>
                    </form>
                @else
                    <x-link :href="route('login')" class="block text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white" @click="mobileOpen = false">
                        {{ __('Log in') }}
                    </x-link>
                    <x-link :href="route('register')" class="block text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white" @click="mobileOpen = false">
                        {{ __('Create account') }}
                    </x-link>
                @endauth
            </div>
        </div>
    </div>
</header>
