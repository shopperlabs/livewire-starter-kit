<div>
    @if (\App\Actions\ZoneSessionManager::checkSession())
        <div class="flex items-center">
            <p class="text-sm/5 text-zinc-700 dark:text-zinc-300">
                {{ __('Shipping to') }} :
            </p>
            <button wire:click="openSelector" type="button" class="group ml-4 flex items-center font-medium hover:text-zinc-950 dark:hover:text-white">
                <img
                    src="{{ \App\Actions\ZoneSessionManager::getSession()->countryFlag }}"
                    alt=""
                    class="block h-auto w-5 shrink-0"
                />
                <span class="ml-2 block text-sm font-medium underline">
                    {{ \App\Actions\ZoneSessionManager::getSession()->countryName }}
                </span>
            </button>
        </div>
    @endif

    <flux:modal wire:model="showModal" class="p-0! md:w-lg">
        <x-card class="space-y-4">
            <flux:heading size="lg">{{ __('Select your country') }}</flux:heading>

            @if (\App\Actions\ZoneSessionManager::checkSession())
                <flux:text>
                    {{ __('Currently shipping to') }}:
                    <span class="font-semibold text-zinc-900 dark:text-white">
                        {{ \App\Actions\ZoneSessionManager::getSession()->countryName }}
                    </span>
                </flux:text>
            @endif

            <flux:text size="sm">
                {{ __('Changing your country may update prices and currency.') }}
            </flux:text>

            <div class="mt-4 max-h-96 divide-y divide-zinc-200 overflow-y-auto dark:divide-zinc-700">
                @foreach ($this->countries->groupBy('zoneName') as $zone => $countries)
                    <div class="py-4">
                        <h4 class="text-sm font-medium text-zinc-900 dark:text-white">{{ $zone }}</h4>
                        <ul role="listbox" class="mt-2 space-y-1">
                            @foreach ($countries as $country)
                                <li>
                                    <button
                                        wire:click="selectZone({{ $country->countryId }})"
                                        type="button"
                                        @class([
                                            'flex w-full items-center rounded-lg px-3 py-2 text-sm transition',
                                            'bg-zinc-100 font-medium text-zinc-900 dark:bg-zinc-800 dark:text-white' => \App\Actions\ZoneSessionManager::getSession()?->countryId === $country->countryId,
                                            'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50' => \App\Actions\ZoneSessionManager::getSession()?->countryId !== $country->countryId,
                                        ])
                                    >
                                        <img src="{{ $country->countryFlag }}" alt="" class="block h-auto w-5 shrink-0 rounded-xs" />
                                        <span class="ml-2">{{ $country->countryName }}</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </x-card>
    </flux:modal>
</div>
