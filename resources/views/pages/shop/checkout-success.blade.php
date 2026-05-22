<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use Shopper\Core\Models\Order;

new class extends Component {
    public Order $order;

    public function mount(Order $order): void
    {
        abort_unless(Auth::check() && $order->customer_id === Auth::id(), 403);
    }
}; ?>

<div>
    <div class="mx-auto max-w-2xl px-4 py-16 text-center sm:px-6 lg:px-8">
        <div class="flex justify-center">
            <div class="flex size-16 items-center justify-center rounded-full bg-green-100">
                <x-flux::icon.check variant="outline" class="size-8 text-green-600" />
            </div>
        </div>

        <h1 class="mt-6 font-heading text-3xl font-bold text-zinc-900 dark:text-white">{{ __('Order Confirmed!') }}</h1>
        <p class="mt-2 text-zinc-500">
            {{ __('Thank you for your purchase. Your order number is :number.', ['number' => $order->number]) }}
        </p>

        <div class="mt-8 rounded-2xl bg-zinc-50 p-6 text-left dark:bg-zinc-800/50">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Order Details') }}</h2>
            <dl class="mt-4 space-y-3">
                <div class="flex justify-between">
                    <dt class="text-sm text-zinc-500">{{ __('Order number') }}</dt>
                    <dd class="text-sm font-medium text-zinc-900 dark:text-white">{{ $order->number }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-zinc-500">{{ __('Total') }}</dt>
                    <dd class="text-sm font-medium text-zinc-900 dark:text-white">{{ shopper_money_format($order->price_amount, $order->currency_code) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-zinc-500">{{ __('Status') }}</dt>
                    <dd class="text-sm font-medium text-zinc-900 dark:text-white">{{ $order->status->getLabel() }}</dd>
                </div>
            </dl>
        </div>

        <div class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
            @auth
                <flux:button variant="primary" :href="route('account.orders')" wire:navigate>
                    {{ __('View My Orders') }}
                </flux:button>
            @endauth
            <flux:button :href="route('shop.index')" wire:navigate>
                {{ __('Continue Shopping') }}
            </flux:button>
        </div>
    </div>
</div>
