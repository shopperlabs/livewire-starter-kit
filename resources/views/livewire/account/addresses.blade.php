<div>
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('My Addresses') }}</flux:heading>
            <flux:text class="mt-1">{{ __('View and update your delivery and billing addresses.') }}</flux:text>
        </div>
    </div>

    <div class="mt-8 space-y-8">
        <livewire:account.address-form :key="'address-form-create'" />

        @if ($this->addresses->isNotEmpty())
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->addresses as $address)
                    <x-address.card :$address />
                @endforeach
            </div>
        @else
            <p class="text-sm text-zinc-500">
                {{ __('You have not yet added any addresses.') }}
            </p>
        @endif
    </div>
</div>
