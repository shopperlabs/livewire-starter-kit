<?php

declare(strict_types=1);

use Livewire\Volt\Component;
use Shopper\Core\Models\Order;

new #[\Livewire\Attributes\Layout('layouts.account')] class extends Component {
    public Order $order;

    public function mount(Order $order): void
    {
        abort_unless($order->customer_id === auth()->id(), 403);

        $this->order = $order->load([
            'items.product.media',
            'shippingAddress',
            'shippingOption.carrier',
        ]);
    }
}; ?>


<div>
    <nav class="flex items-center gap-2 text-sm text-zinc-500">
        <x-link :href="route('dashboard')" class="hover:text-zinc-900 dark:hover:text-white">{{ __('Account') }}</x-link>
        <span>/</span>
        <x-link :href="route('account.orders')" class="hover:text-zinc-900 dark:hover:text-white">{{ __('Orders') }}</x-link>
        <span>/</span>
        <span class="text-zinc-900 dark:text-white">{{ __('Order details') }}</span>
    </nav>

    <div class="mt-6">
        <flux:heading size="xl" level="1">{{ __('Order details') }}</flux:heading>
        <p class="mt-1 text-sm text-zinc-500">
            {{ __('Ordered on :date', ['date' => $order->created_at->translatedFormat('M d, Y')]) }}
            <span class="mx-2">|</span>
            {{ __('Order #:number', ['number' => $order->number]) }}
        </p>
    </div>

    <div class="mt-6 flex flex-wrap gap-2">
        @if ($order->status === \Shopper\Core\Enum\OrderStatus::Cancelled)
            <x-order.status :status="$order->status" />
        @else
            <x-order.status :status="$order->payment_status" />
            <x-order.status :status="$order->shipping_status" />
        @endif
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        @if ($order->shippingAddress)
            <div>
                <x-card>
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white font-heading">{{ __('Shipping address') }}</h3>
                    <address class="mt-3 text-sm not-italic text-zinc-500">
                        <p class="font-medium text-zinc-900 dark:text-white">{{ $order->shippingAddress->full_name }}</p>
                        <p>{{ $order->shippingAddress->street_address }}</p>
                        @if ($order->shippingAddress->street_address_plus)
                            <p>{{ $order->shippingAddress->street_address_plus }}</p>
                        @endif
                        <p>{{ $order->shippingAddress->city }} {{ $order->shippingAddress->postal_code }}</p>
                        @if ($order->shippingAddress->country_name)
                            <p>{{ $order->shippingAddress->country_name }}</p>
                        @endif
                    </address>
                </x-card>
            </div>
        @endif

        <div class="lg:col-span-2">
            <x-card>
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white font-heading">{{ __('Order summary') }}</h3>
                @php
                    $shippingPrice = $order->shippingOption?->price ?? 0;
                    $itemsTotal = $order->price_amount - ($order->tax_amount ?? 0) - $shippingPrice;
                @endphp
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-zinc-500">{{ __('Items') }}</dt>
                        <dd class="text-zinc-900 dark:text-white">
                            {{ shopper_money_format($itemsTotal, $order->currency_code) }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-zinc-500">
                            {{ __('Delivery') }}
                            @if ($order->shippingOption?->carrier)
                                <span class="text-zinc-400">({{ $order->shippingOption->carrier->name }})</span>
                            @endif
                        </dt>
                        <dd class="text-zinc-900 dark:text-white">
                            {{ $shippingPrice > 0 ? shopper_money_format($shippingPrice, $order->currency_code) : __('Free') }}
                        </dd>
                    </div>
                    @if ($order->tax_amount > 0)
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">{{ __('Tax') }}</dt>
                            <dd class="text-zinc-900 dark:text-white">{{ shopper_money_format($order->tax_amount, $order->currency_code) }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between border-t border-zinc-200 dark:border-zinc-700 pt-2">
                        <dt class="font-semibold text-zinc-900 dark:text-white">{{ __('Total') }}</dt>
                        <dd class="font-semibold text-zinc-900 dark:text-white">{{ shopper_money_format($order->price_amount, $order->currency_code) }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>
    </div>

    <div class="mt-8 overflow-hidden">
        <x-card class="p-0">
            <div class="divide-y divide-zinc-200 dark:divide-white/10">
                @foreach ($order->items as $item)
                    <x-order.item :$item :currency-code="$order->currency_code" class="px-5 py-4" />
                @endforeach
            </div>
        </x-card>
    </div>
</div>
