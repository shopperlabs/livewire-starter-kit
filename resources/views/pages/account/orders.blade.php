<?php

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\ShippingStatus;
use Shopper\Core\Models\Order;

new #[\Livewire\Attributes\Layout('layouts.account')] class extends Component {
    use WithPagination;

    #[Url]
    public string $tab = 'all';

    public function updatedTab(): void
    {
        $this->resetPage();
    }

    public function orders(): LengthAwarePaginator
    {
        $query = Order::query()
            ->where('customer_id', auth()->id())
            ->with(['items.product.media', 'shippingAddress']);

        $query = match ($this->tab) {
            'not-shipped' => $query->where('shipping_status', ShippingStatus::Unfulfilled),
            'cancelled' => $query->where('status', OrderStatus::Cancelled),
            default => $query,
        };

        return $query->latest()->paginate(10);
    }

    public function with(): array
    {
        return [
            'orders' => $this->orders(),
        ];
    }
}; ?>

@php
    $shippingLabel = fn (\Shopper\Core\Models\Order $order): string => match ($order->shipping_status) {
        ShippingStatus::Delivered => __('Delivered :date', ['date' => $order->updated_at->translatedFormat('M d')]),
        ShippingStatus::Shipped, ShippingStatus::PartiallyShipped => __('Shipped :date', ['date' => $order->updated_at->translatedFormat('M d')]),
        ShippingStatus::Returned, ShippingStatus::PartiallyReturned => __('Returned'),
        default => match ($order->status) {
            OrderStatus::Cancelled => __('Cancelled'),
            OrderStatus::Completed => __('Completed :date', ['date' => $order->updated_at->translatedFormat('M d')]),
            default => __('Processing'),
        },
    };
@endphp

<div>
    <div class="flex items-center gap-3">
        <flux:heading size="xl" level="1">{{ __('Your Orders') }}</flux:heading>
        @if ($orders->total() > 0)
            <span class="inline-flex items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800 px-2.5 py-0.5 text-xs font-medium text-zinc-600 dark:text-zinc-400">
                {{ $orders->total() }}
            </span>
        @endif
    </div>

    <div class="mt-6 flex items-center justify-between">
        <div class="flex items-center gap-1 rounded-lg border border-zinc-200 dark:border-zinc-700 p-1">
            <button
                type="button"
                wire:click="$set('tab', 'all')"
                @class([
                    'rounded-md px-3 py-1.5 text-sm font-medium transition',
                    'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => $tab === 'all',
                    'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white' => $tab !== 'all',
                ])
            >
                {{ __('Orders') }}
            </button>
            <button
                type="button"
                wire:click="$set('tab', 'not-shipped')"
                @class([
                    'rounded-md px-3 py-1.5 text-sm font-medium transition',
                    'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => $tab === 'not-shipped',
                    'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white' => $tab !== 'not-shipped',
                ])
            >
                {{ __('Not Yet Shipped') }}
            </button>
            <button
                type="button"
                wire:click="$set('tab', 'cancelled')"
                @class([
                    'rounded-md px-3 py-1.5 text-sm font-medium transition',
                    'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => $tab === 'cancelled',
                    'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white' => $tab !== 'cancelled',
                ])
            >
                {{ __('Cancelled Orders') }}
            </button>
        </div>
    </div>

    @if ($orders->isEmpty())
        <div class="mt-12 flex flex-col items-center justify-center text-center">
            <x-flux::icon.shopping-bag variant="outline" class="size-12 text-zinc-300 dark:text-zinc-600" />
            <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">{{ __('No orders found') }}</h3>
            <p class="mt-1 text-sm text-zinc-500">{{ __('Your orders will appear here once you make a purchase.') }}</p>
            <div class="mt-6">
                <flux:button variant="primary" :href="route('shop.index')" wire:navigate>
                    {{ __('Start Shopping') }}
                </flux:button>
            </div>
        </div>
    @else
        <div class="mt-6 space-y-6">
            @foreach ($orders as $order)
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div class="flex flex-wrap items-start justify-between gap-4 bg-zinc-50 dark:bg-zinc-800/50 px-5 py-4 border-b border-zinc-200 dark:border-zinc-700">
                        <div class="flex flex-wrap items-center gap-8 text-sm">
                            <div>
                                <dt class="text-xs text-zinc-500">{{ __('Order placed') }}</dt>
                                <dd class="mt-0.5 font-medium text-zinc-900 dark:text-white">
                                    {{ $order->created_at->translatedFormat('M d, Y') }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-zinc-500">{{ __('Total') }}</dt>
                                <dd class="mt-0.5 font-medium text-zinc-900 dark:text-white">
                                    {{ shopper_money_format($order->price_amount, $order->currency_code) }}
                                </dd>
                            </div>
                            @if ($order->shippingAddress)
                                <div>
                                    <dt class="text-xs text-zinc-500">{{ __('Ship to') }}</dt>
                                    <dd class="mt-0.5 font-medium text-zinc-900 dark:text-white">
                                        {{ $order->shippingAddress->full_name }}
                                    </dd>
                                </div>
                            @endif
                        </div>
                        <div class="flex flex-col items-end gap-1.5 text-sm">
                            <span class="font-medium text-zinc-900 dark:text-white">
                                {{ __('Order #:number', ['number' => $order->number]) }}
                            </span>
                            <x-link :href="route('account.orders.show', $order)" class="text-sm font-medium text-accent hover:underline">
                                {{ __('View order details') }}
                            </x-link>
                        </div>
                    </div>

                    <div class="px-5 py-4">
                        <div class="flex items-center gap-3 flex-wrap">
                            <h3 class="text-base font-semibold text-zinc-900 dark:text-white font-heading">
                                {{ $shippingLabel($order) }}
                            </h3>
                            @if ($order->status === OrderStatus::Cancelled)
                                <x-order.status :status="$order->status" />
                            @else
                                <x-order.status :status="$order->payment_status" />
                                <x-order.status :status="$order->shipping_status" />
                            @endif
                        </div>

                        <div class="mt-4 space-y-4">
                            @foreach ($order->items as $item)
                                <x-order.item :$item :currency-code="$order->currency_code" />
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $orders->links() }}
        </div>
    @endif
</div>
