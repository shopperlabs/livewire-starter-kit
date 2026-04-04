@php
    $cart = $this->cart;
    $context = $this->cartContext;
    $thumbnailCollection = config('shopper.media.storage.thumbnail_collection');
@endphp

<div>
    <x-container class="py-8 sm:py-12">
        <flux:heading size="xl">{{ __('Shopping Cart') }}</flux:heading>

        @if(!$cart || $cart->lines->isEmpty())
            <div class="mt-16 flex flex-col items-center justify-center text-center">
                <flux:icon.shopping-bag variant="outline" class="size-16 text-zinc-300 dark:text-zinc-600" />
                <flux:heading size="lg" class="mt-4">{{ __('Your cart is empty') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Start shopping to add items to your cart.') }}</flux:text>
                <div class="mt-6">
                    <flux:button variant="primary" :href="route('shop.index')" wire:navigate>
                        {{ __('Continue Shopping') }}
                    </flux:button>
                </div>
            </div>
        @else
            <div class="mt-8 lg:grid lg:grid-cols-12 lg:gap-x-12">
                <div class="lg:col-span-7">
                    <ul role="list" class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($cart->lines as $line)
                            @php
                                $purchasable = $line->purchasable;
                                $product = $purchasable instanceof \App\Models\ProductVariant ? $purchasable->product : $purchasable;
                                $image = $purchasable->getFirstMediaUrl($thumbnailCollection);
                                $fallback = shopper_fallback_url();
                            @endphp

                            <li class="flex gap-4 py-6" wire:key="cart-line-{{ $line->id }}">
                                <div class="size-20 shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800 sm:size-24">
                                    <img src="{{ $image ?: $fallback }}" alt="{{ $purchasable->name }}" class="size-full object-cover object-center" />
                                </div>

                                <div class="flex flex-1 flex-col justify-between">
                                    <div class="flex justify-between">
                                        <div>
                                            <h3 class="text-sm font-medium text-zinc-900 dark:text-white">
                                                @if($product)
                                                    <x-link :href="route('shop.product', $product)" class="hover:underline">{{ $purchasable->name }}</x-link>
                                                @else
                                                    {{ $purchasable->name }}
                                                @endif
                                            </h3>
                                            <p class="mt-0.5 text-sm text-zinc-500">{{ shopper_money_format($line->unit_price_amount) }}</p>
                                        </div>
                                        <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                            {{ shopper_money_format($line->unit_price_amount * $line->quantity) }}
                                        </p>
                                    </div>

                                    <div class="mt-2 flex items-center justify-between">
                                        <div class="flex items-center rounded-lg border border-zinc-300 dark:border-zinc-600">
                                            <button type="button" wire:click="updateQuantity({{ $line->id }}, {{ $line->quantity - 1 }})" class="px-2 py-1 text-zinc-500 hover:text-zinc-900 dark:hover:text-white" @disabled($line->quantity <= 1)>
                                                <flux:icon.minus variant="micro" class="size-3" />
                                            </button>
                                            <span class="min-w-[1.5rem] text-center text-xs font-medium text-zinc-900 dark:text-white">{{ $line->quantity }}</span>
                                            <button type="button" wire:click="updateQuantity({{ $line->id }}, {{ $line->quantity + 1 }})" class="px-2 py-1 text-zinc-500 hover:text-zinc-900 dark:hover:text-white">
                                                <flux:icon.plus variant="micro" class="size-3" />
                                            </button>
                                        </div>

                                        <button type="button" wire:click="removeLine({{ $line->id }})" class="text-sm text-red-500 hover:text-red-700 transition">
                                            {{ __('Remove') }}
                                        </button>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-4 flex items-center justify-between border-t border-zinc-200 dark:border-zinc-700 pt-4">
                        <x-link :href="route('shop.index')" class="text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white transition">
                            &larr; {{ __('Continue Shopping') }}
                        </x-link>
                        <button type="button" wire:click="clearCart" wire:confirm="{{ __('Are you sure you want to clear your cart?') }}" class="text-sm text-red-500 hover:text-red-700 transition">
                            {{ __('Clear Cart') }}
                        </button>
                    </div>
                </div>

                <div class="mt-8 lg:col-span-5 lg:mt-0">
                    <div class="rounded-2xl bg-zinc-50 dark:bg-zinc-800/50 p-6">
                        <flux:heading size="lg">{{ __('Order Summary') }}</flux:heading>

                        <dl class="mt-6 space-y-3 text-sm text-zinc-500">
                            <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-700 pb-3">
                                <dt>{{ __('Tax') }}</dt>
                                <dd class="text-base text-zinc-900 dark:text-white">
                                    {{ shopper_money_format($context?->taxTotal ?? 0) }}
                                </dd>
                            </div>

                            <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-700 pb-3">
                                <dt>{{ __('Delivery') }}</dt>
                                <dd>{{ __('Calculated at checkout') }}</dd>
                            </div>

                            @if($context && $context->discountTotal > 0)
                                <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-700 pb-3">
                                    <dt>{{ __('Discount') }}</dt>
                                    <dd class="text-emerald-600">-{{ shopper_money_format($context->discountTotal) }}</dd>
                                </div>
                            @endif

                            <div class="flex items-center justify-between pt-1">
                                <dt class="text-base font-semibold text-zinc-900 dark:text-white">
                                    {{ __('Subtotal') }} {{ current_tax_label() }}
                                </dt>
                                <dd class="text-base font-semibold text-zinc-900 dark:text-white">
                                    {{ shopper_money_format($context?->subtotal ?? 0) }}
                                </dd>
                            </div>
                        </dl>

                        <div class="mt-6">
                            @auth
                                <flux:button variant="primary" :href="route('shop.checkout')" wire:navigate class="w-full">
                                    {{ __('Proceed to checkout') }}
                                </flux:button>
                            @else
                                <flux:button variant="primary" :href="route('login')" wire:navigate class="w-full">
                                    {{ __('Sign in to checkout') }}
                                </flux:button>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </x-container>
</div>
