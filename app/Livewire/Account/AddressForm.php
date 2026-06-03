<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Actions\GetCountriesByZone;
use App\Actions\ZoneSessionManager;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Shopper\Core\Enum\AddressType;
use Shopper\Core\Models\Address;
use Shopper\Core\Models\Country;

class AddressForm extends Component
{
    public bool $showModal = false;

    #[Validate('required|string')]
    public ?string $first_name = null;

    #[Validate('required|string')]
    public ?string $last_name = null;

    #[Validate('required|min:3')]
    public ?string $street_address = null;

    #[Validate('nullable|string')]
    public ?string $street_address_plus = null;

    #[Validate('required')]
    public AddressType $type = AddressType::Shipping;

    #[Validate('required')]
    public ?int $country_id = null;

    #[Validate('required|string')]
    public ?string $postal_code = null;

    #[Validate('required|string')]
    public ?string $city = null;

    #[Validate('nullable|string')]
    public ?string $state = null;

    #[Validate('nullable|string')]
    public ?string $phone_number = null;

    #[Locked]
    public ?int $addressId = null;

    public Collection $countries;

    public function mount(?int $addressId = null): void
    {
        $address = $addressId
            ? Auth::user()->addresses()->findOrFail($addressId)
            : null;

        $this->addressId = $address?->id;

        $this->countries = Country::query()
            ->whereIn(
                column: 'id',
                values: resolve(GetCountriesByZone::class)
                    ->handle()
                    ->where('zoneId', ZoneSessionManager::getSession()?->zoneId)
                    ->pluck('countryId')
            )
            ->pluck('name', 'id');

        $this->country_id = ZoneSessionManager::getSession()?->countryId;

        if ($address) {
            $this->fill(array_merge($address->toArray(), ['type' => $address->type]));
        }
    }

    public function openModal(): void
    {
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        $address = $this->addressId
            ? Auth::user()->addresses()->findOrFail($this->addressId)
            : new Address;

        $address->fill(array_merge($validated, ['user_id' => Auth::id()]));
        $address->save();

        if (! $this->addressId) {
            $this->reset();
            $this->type = AddressType::Shipping;
        }

        $this->dispatch('notify', type: 'success', message: __('The address has been saved.'));

        $this->showModal = false;

        $this->dispatch('addresses-updated');
    }

    public function render(): View
    {
        return view('livewire.account.address-form', [
            'title' => $this->addressId
                ? __('Update address')
                : __('Add new address'),
        ]);
    }
}
