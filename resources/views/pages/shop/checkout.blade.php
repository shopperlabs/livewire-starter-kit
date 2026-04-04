@php
    $context = $this->cartContext;
    $cart = $this->cart;
    $thumbnailCollection = config('shopper.media.storage.thumbnail_collection');
@endphp

<div>
    <x-container class="py-8 sm:py-12">
        <flux:heading size="xl">{{ __('Checkout') }}</flux:heading>

        <nav class="mt-8 mb-10">
            <ol class="flex items-center gap-2">
                @foreach ([1 => __('Shipping'), 2 => __('Delivery'), 3 => __('Payment')] as $stepNum => $stepLabel)
                    <li class="flex items-center gap-2">
                        <button
                            type="button"
                            wire:click="goToStep({{ $stepNum }})"
                            @disabled($stepNum > $step)
                            @class([
                                'flex items-center gap-2 text-sm font-medium transition',
                                'text-zinc-900 dark:text-white' => $step === $stepNum,
                                'text-green-600' => $step > $stepNum,
                                'text-zinc-400 dark:text-zinc-500' => $step < $stepNum,
                            ])
                        >
                            <span @class([
                                'flex size-7 items-center justify-center rounded-full text-xs font-bold',
                                'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => $step === $stepNum,
                                'bg-green-100 text-green-600' => $step > $stepNum,
                                'bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500' => $step < $stepNum,
                            ])>
                                @if ($step > $stepNum)
                                    <flux:icon.check variant="micro" class="size-4" />
                                @else
                                    {{ $stepNum }}
                                @endif
                            </span>
                            {{ $stepLabel }}
                        </button>

                        @if ($stepNum < 3)
                            <flux:icon.chevron-right variant="micro" class="size-4 text-zinc-300 dark:text-zinc-600" />
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>

        <div class="lg:grid lg:grid-cols-12 lg:gap-x-12">
            <div class="lg:col-span-7">
                @if ($step === 1)
                    @if ($this->savedAddresses->isNotEmpty())
                        <div class="mb-8">
                            <flux:heading size="lg">{{ __('Saved Addresses') }}</flux:heading>
                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                @foreach ($this->savedAddresses as $address)
                                    <button
                                        type="button"
                                        wire:click="selectAddress({{ $address->id }})"
                                        @class([
                                            'rounded-xl text-left transition',
                                            'ring-zinc-900 dark:ring-white ring-2 ring-zinc-900 dark:ring-white' => $selectedAddressId === $address->id,
                                            'ring-zinc-200 dark:ring-zinc-700 hover:border-zinc-400 dark:hover:border-zinc-500' => $selectedAddressId !== $address->id,
                                        ])
                                    >
                                        <x-card>
                                            <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                                {{ $address->first_name }} {{ $address->last_name }}
                                            </p>
                                            <p class="mt-1 text-xs text-zinc-500">
                                                {{ $address->street_address }}, {{ $address->city }} {{ $address->postal_code }}
                                            </p>
                                            <p class="text-xs text-zinc-500">{{ $address->country?->name }}</p>

                                            @if ($address->isShippingDefault())
                                                <flux:badge size="sm" class="mt-2">{{ __('Default') }}</flux:badge>
                                            @endif
                                        </x-card>
                                    </button>
                                @endforeach
                            </div>

                            @if ($selectedAddressId)
                                <button type="button" wire:click="clearAddress" class="mt-3 text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white transition underline">
                                    {{ __('Use a new address instead') }}
                                </button>
                            @endif

                            <flux:separator class="my-6" />
                        </div>
                    @endif

                    <form wire:submit="saveShippingAddress" class="space-y-5">
                        <flux:heading size="lg">{{ __('Shipping Address') }}</flux:heading>

                        <div class="grid grid-cols-2 gap-4">
                            <flux:field>
                                <flux:label>{{ __('First name') }}</flux:label>
                                <flux:input wire:model="shippingFirstName" />
                                <flux:error name="shippingFirstName" />
                            </flux:field>

                            <flux:field>
                                <flux:label>{{ __('Last name') }}</flux:label>
                                <flux:input wire:model="shippingLastName" />
                                <flux:error name="shippingLastName" />
                            </flux:field>
                        </div>

                        <flux:field>
                            <flux:label>{{ __('Address') }}</flux:label>
                            <flux:input wire:model="shippingAddress" />
                            <flux:error name="shippingAddress" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('Apartment, suite, etc. (optional)') }}</flux:label>
                            <flux:input wire:model="shippingAddressPlus" />
                        </flux:field>

                        <div class="grid grid-cols-2 gap-4">
                            <flux:field>
                                <flux:label>{{ __('City') }}</flux:label>
                                <flux:input wire:model="shippingCity" />
                                <flux:error name="shippingCity" />
                            </flux:field>

                            <flux:field>
                                <flux:label>{{ __('Postal code') }}</flux:label>
                                <flux:input wire:model="shippingPostalCode" />
                                <flux:error name="shippingPostalCode" />
                            </flux:field>

                            <flux:field>
                                <flux:label>{{ __('State / Province') }}</flux:label>
                                <flux:input wire:model="shippingState" />
                                <flux:error name="shippingState" />
                            </flux:field>

                            <flux:field>
                                <flux:label>{{ __('Country') }}</flux:label>
                                <flux:input :value="\App\Actions\ZoneSessionManager::getSession()?->countryName" readonly />
                            </flux:field>
                        </div>

                        <flux:field>
                            <flux:label>{{ __('Phone (optional)') }}</flux:label>
                            <flux:input type="tel" wire:model="shippingPhone" />
                        </flux:field>

                        <flux:button type="submit" variant="primary" class="w-full">
                            {{ __('Continue to Delivery') }}
                        </flux:button>
                    </form>
                @endif

                @if ($step === 2)
                    @if (count($deliveryOptions) === 0)
                        <div class="flex items-center gap-4 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
                            <flux:icon.shopping-bag variant="outline" class="size-5 text-zinc-400" />
                            <flux:text>{{ __('No delivery option available for your address.') }}</flux:text>
                        </div>
                        <button type="button" wire:click="goToStep(1)" class="mt-4 text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white transition">
                            <span>&larr;</span> {{ __('Return to shipping') }}
                        </button>
                    @else
                        <form wire:submit="saveShippingOption" class="space-y-5">
                            <flux:heading size="lg">{{ __('Delivery Method') }}</flux:heading>
                            <flux:error name="selectedDeliveryOption" />

                            <flux:radio.group wire:model="selectedDeliveryOption" variant="cards" class="flex-col">
                                @foreach ($deliveryOptions as $option)
                                    <flux:radio value="{{ $option['service_code'] }}" class="w-full">
                                        <div class="flex items-center justify-between w-full">
                                            <div class="flex items-start gap-3">
                                                @if ($option['carrier_logo'])
                                                    <img src="{{ $option['carrier_logo'] }}" alt="{{ $option['carrier_name'] }}" class="mt-0.5 size-6 rounded-full object-cover" />
                                                @endif
                                                <div class="flex flex-col">
                                                    <span class="text-sm font-medium font-heading">{{ $option['service_name'] }}</span>
                                                    @if ($option['estimated_days'])
                                                        <span class="text-sm text-zinc-500">{{ __(':days days delivery', ['days' => $option['estimated_days']]) }}</span>
                                                    @elseif ($option['description'])
                                                        <span class="text-sm text-zinc-500">{{ $option['description'] }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <span class="text-sm font-medium text-zinc-900 dark:text-white">
                                                {{ shopper_money_format($option['amount'], $option['currency']) }}
                                            </span>
                                        </div>
                                    </flux:radio>
                                @endforeach
                            </flux:radio.group>

                            <flux:button type="submit" variant="primary" class="w-full">
                                {{ __('Continue to Payment') }}
                            </flux:button>
                        </form>
                    @endif
                @endif

                @if ($step === 3)
                    <form wire:submit="placeOrder" class="space-y-5">
                        <div>
                            <flux:heading size="lg">{{ __('Payment Method') }}</flux:heading>
                            <flux:subheading>{{ __('All transactions are secure and encrypted.') }}</flux:subheading>
                        </div>

                        <flux:error name="paymentMethodId" />

                        @if (count($paymentOptions) === 0)
                            <flux:text>{{ __('No payment methods available for your region.') }}</flux:text>
                        @else
                            <flux:radio.group wire:model="paymentMethodId" variant="cards" class="flex-col">
                                @foreach ($paymentOptions as $method)
                                    <flux:radio value="{{ $method['id'] }}" class="w-full">
                                        <div class="flex items-center justify-between gap-6 w-full">
                                            <span class="text-sm font-medium font-heading">{{ $method['title'] }}</span>
                                            @if ($method['logo'])
                                                <img src="{{ $method['logo'] }}" alt="{{ $method['title'] }}" class="h-5 w-auto object-cover" />
                                            @endif
                                        </div>
                                    </flux:radio>
                                @endforeach
                            </flux:radio.group>

                            <p class="text-sm leading-5 text-zinc-500">
                                {{ __("By clicking 'Place my order', you confirm that you have read and accepted our terms of use and returns policy.") }}
                            </p>
                        @endif

                        <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled" :disabled="count($paymentOptions) === 0">
                            <span wire:loading.remove wire:target="placeOrder">{{ __('Place my order') }}</span>
                            <span wire:loading wire:target="placeOrder">{{ __('Processing...') }}</span>
                        </flux:button>
                    </form>
                @endif
            </div>

            <div class="mt-8 lg:col-span-5 lg:mt-0">
                <x-card class="p-6">
                    <flux:heading size="lg" class="font-heading font-semibold text-lg">{{ __('Order Summary') }}</flux:heading>

                    @if ($cart)
                        <ul role="list" class="mt-4 divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach ($cart->lines as $line)
                                @php
                                    $purchasable = $line->purchasable;
                                    $image = $purchasable->getFirstMediaUrl($thumbnailCollection);
                                    $fallback = shopper_fallback_url();
                                @endphp

                                <li class="flex gap-3 py-3" wire:key="checkout-line-{{ $line->id }}">
                                    <div class="size-14 shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                        <img src="{{ $image ?: $fallback }}" alt="{{ $purchasable->name }}" class="size-full object-cover" />
                                    </div>
                                    <div class="flex flex-1 justify-between">
                                        <div>
                                            <flux:text class="text-sm! font-medium text-zinc-900! dark:text-white!">{{ $purchasable->name }}</flux:text>
                                            <flux:text size="xs">{{ __('Qty: :qty', ['qty' => $line->quantity]) }}</flux:text>
                                        </div>
                                        <flux:text class="text-sm! font-medium text-zinc-900! dark:text-white!">
                                            {{ shopper_money_format($line->unit_price_amount * $line->quantity) }}
                                        </flux:text>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @php
                        $shippingOption = session()->get(\App\CheckoutSession::SHIPPING_OPTION);
                        $deliveryPrice = $shippingOption ? (int) data_get($shippingOption, '0.price', 0) : null;
                        $deliveryCurrency = $shippingOption ? data_get($shippingOption, '0.currency') : null;
                        $subtotal = $context?->total ?? 0;
                    @endphp

                    <dl class="mt-4 space-y-3 text-sm text-zinc-500 border-t border-zinc-200 dark:border-zinc-700 pt-4">
                        <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-700 pb-3">
                            <dt>{{ __('Tax') }}</dt>
                            <dd class="text-base text-zinc-900 dark:text-white">{{ shopper_money_format($context?->taxTotal ?? 0) }}</dd>
                        </div>

                        <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-700 pb-3">
                            <dt>{{ __('Delivery') }}</dt>
                            <dd class="text-base text-zinc-900 dark:text-white">
                                @if ($deliveryPrice !== null)
                                    {{ $deliveryPrice > 0 ? shopper_money_format($deliveryPrice, $deliveryCurrency) : __('Free') }}
                                @else
                                    {{ __('Calculated at next step') }}
                                @endif
                            </dd>
                        </div>

                        @if($context && $context->discountTotal > 0)
                            <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-700 pb-3">
                                <dt>{{ __('Discount') }}</dt>
                                <dd class="text-emerald-600">-{{ shopper_money_format($context->discountTotal) }}</dd>
                            </div>
                        @endif

                        <div class="flex items-center justify-between pt-1">
                            <dt class="text-base font-semibold text-zinc-900 dark:text-white">
                                {{ __('Total') }} {{ current_tax_label() }}
                            </dt>
                            <dd class="text-base font-semibold text-zinc-900 dark:text-white">
                                @if ($deliveryPrice !== null && $deliveryPrice > 0)
                                    {{ shopper_money_format($subtotal + $deliveryPrice) }}
                                @else
                                    {{ shopper_money_format($subtotal) }}
                                @endif
                            </dd>
                        </div>
                    </dl>
                </x-card>
            </div>
        </div>
    </x-container>
</div>
