@blaze

@props([
    'address',
])

<x-card class="relative flex flex-col justify-between">
    @if ($address->type === \Shopper\Core\Enum\AddressType::Billing)
        <div class="absolute top-2 right-2">
            <flux:badge size="sm" color="zinc">{{ __('Billing') }}</flux:badge>
        </div>
    @endif

    <div class="flex flex-col gap-4">
        <h4 class="text-sm font-medium text-zinc-900 dark:text-white font-heading">
            {{ $address->first_name }} {{ $address->last_name }}
        </h4>
        <p class="flex flex-col text-sm text-zinc-500">
            <span>
                {{ $address->street_address }}
                @if ($address->street_address_plus)
                    <span>, {{ $address->street_address_plus }}</span>
                @endif
            </span>
            <span>{{ $address->postal_code }}, {{ $address->city }}</span>
            <span>{{ $address->country?->name }}</span>
        </p>

        <div class="space-y-1">
            @if ($address->isShippingDefault())
                <flux:badge size="sm" icon="check">{{ __('Default shipping') }}</flux:badge>
            @endif

            @if ($address->isBillingDefault())
                <flux:badge size="sm" icon="check">{{ __('Default billing') }}</flux:badge>
            @endif
        </div>
    </div>

    <div class="mt-4 flex items-center gap-2">
        <flux:button
            size="sm"
            variant="danger"
            wire:click="removeAddress({{ $address->id }})"
            wire:confirm="{{ __('Do you really want to delete this address?') }}"
            icon="trash"
        />

        <livewire:account.address-form :address-id="$address->id" :key="'address-form-'.$address->id" />

        <flux:dropdown>
            <flux:button size="sm" icon="ellipsis-horizontal" />
            <flux:menu>
                @unless ($address->isShippingDefault())
                    <flux:menu.item wire:click="setDefaultShipping({{ $address->id }})" icon="truck">
                        {{ __('Set as default shipping') }}
                    </flux:menu.item>
                @endunless

                @unless ($address->isBillingDefault())
                    <flux:menu.item wire:click="setDefaultBilling({{ $address->id }})" icon="credit-card">
                        {{ __('Set as default billing') }}
                    </flux:menu.item>
                @endunless
            </flux:menu>
        </flux:dropdown>
    </div>
</x-card>
