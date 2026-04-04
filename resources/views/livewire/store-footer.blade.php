<footer aria-labelledby="footer-heading" class="bg-linear-to-b from-zinc-50 to-white dark:from-zinc-900/95 dark:to-zinc-950 border-t border-zinc-200 dark:border-white/10">
    <h2 id="footer-heading" class="sr-only">{{ __('Footer') }}</h2>
    <x-container>
        <div class="py-10 sm:py-20 lg:grid lg:grid-cols-2 lg:gap-10 lg:py-24">
            <div>
                <div class="max-w-sm">
                    <x-link :href="route('home')">
                        <x-brand.icon class="h-10 w-auto fill-current text-black dark:text-white" aria-hidden="true" />
                    </x-link>
                    <p class="mt-8 text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                        {{ __('Build modern, scalable online stores. It includes essential features like product management, checkout, and order handling.') }}
                    </p>
                </div>

                <div class="mt-12">
                    <livewire:zone-selector />
                </div>
            </div>
            <div class="mt-16 gap-8 space-y-6 lg:col-span-1 lg:mt-0 sm:grid sm:grid-cols-3 sm:space-y-0">
                <div>
                    <h3 class="text-sm font-semibold leading-6 text-zinc-900 dark:text-white">{{ __('Shop') }}</h3>
                    <ul role="list" class="mt-6 space-y-4">
                        <li>
                            <x-link :href="route('shop.index')" class="text-sm leading-6 text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                                {{ __('All Products') }}
                            </x-link>
                        </li>
                        <li>
                            <x-link :href="route('shop.categories')" class="text-sm leading-6 text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                                {{ __('Categories') }}
                            </x-link>
                        </li>
                    </ul>
                </div>

                @if($this->categories->isNotEmpty())
                    <div>
                        <h3 class="text-sm font-semibold leading-6 text-zinc-900 dark:text-white">
                            {{ __('Categories') }}
                        </h3>
                        <ul role="list" class="mt-6 space-y-4">
                            @foreach($this->categories as $category)
                                <li>
                                    <x-link :href="route('shop.category', $category)" class="text-sm leading-6 text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                                        {{ $category->name }}
                                    </x-link>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div>
                    <h3 class="text-sm font-semibold leading-6 text-zinc-900 dark:text-white">{{ __('Account') }}</h3>
                    <ul role="list" class="mt-6 space-y-4">
                        @auth
                            <li>
                                <x-link :href="route('dashboard')" class="text-sm leading-6 text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                                    {{ __('My account') }}
                                </x-link>
                            </li>
                            <li>
                                <x-link :href="route('account.orders')" class="text-sm leading-6 text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                                    {{ __('My orders') }}
                                </x-link>
                            </li>
                        @else
                            <li>
                                <x-link :href="route('login')" class="text-sm leading-6 text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                                    {{ __('Log in') }}
                                </x-link>
                            </li>
                            <li>
                                <x-link :href="route('register')" class="text-sm leading-6 text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                                    {{ __('Create account') }}
                                </x-link>
                            </li>
                        @endauth
                    </ul>
                </div>
            </div>
        </div>
        <div class="sm:flex sm:items-center justify-between border-t border-zinc-100 dark:border-zinc-800 py-6 lg:py-14">
            <p class="text-sm text-zinc-500">
                {{ __('© :date Shopper Labs, Inc. All rights reserved.', ['date' => date('Y')]) }}
            </p>
            <a href="https://laravelshopper.dev" class="mt-2 inline-flex items-center gap-x-2 text-sm font-medium text-zinc-400 sm:mt-0" target="_blank">
                <span>{{ __('Powered by') }}</span>
                <x-brand.shopper class="size-5 opacity-45" aria-hidden="true" />
                <span>&</span>
                <x-brand.starter-kit class="size-5 opacity-45" aria-hidden="true" />
            </a>
        </div>
    </x-container>
</footer>
