<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Shopper\Core\Models\Address;

#[Layout('layouts.account')]
class Addresses extends Component
{
    /** @return Collection<int, Address> */
    #[Computed]
    #[On('addresses-updated')]
    public function addresses(): Collection
    {
        return Auth::user()->addresses()->with('country')->get();
    }

    public function setDefaultShipping(int $id): void
    {
        $this->setDefault($id, 'shipping_default', __('Default shipping address updated.'));
    }

    public function setDefaultBilling(int $id): void
    {
        $this->setDefault($id, 'billing_default', __('Default billing address updated.'));
    }

    public function removeAddress(int $id): void
    {
        Auth::user()->addresses()->findOrFail($id)->delete();

        unset($this->addresses);

        $this->dispatch('notify', type: 'success', message: __('The address has been removed.'));
        $this->dispatch('addresses-updated');
    }

    public function render(): View
    {
        return view('livewire.account.addresses')
            ->title(__('My Addresses'));
    }

    private function setDefault(int $id, string $column, string $message): void
    {
        $address = Auth::user()->addresses()->findOrFail($id);

        Auth::user()->addresses()
            ->where($column, true)
            ->update([$column => false]);

        $address->update([$column => true]);

        unset($this->addresses);

        $this->dispatch('notify', type: 'success', message: $message);
    }
}
