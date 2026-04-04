<?php

declare(strict_types=1);

namespace App\Livewire\Pages;

use App\Actions\Checkout\BuildShippingPackages;
use App\Actions\Checkout\FetchDeliveryRates;
use App\Actions\Checkout\FetchPaymentMethods;
use App\Actions\CreateOrder;
use App\Actions\ZoneSessionManager;
use App\CheckoutSession;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Shopper\Cart\CartManager;
use Shopper\Cart\CartSessionManager;
use Shopper\Cart\Models\Cart as CartModel;
use Shopper\Cart\Pipelines\CartPipelineContext;
use Shopper\Core\Enum\AddressType;
use Shopper\Core\Models\Address;
use Shopper\Payment\Services\PaymentProcessingService;
use Throwable;

class Checkout extends Component
{
    public int $step = 1;

    public ?int $selectedAddressId = null;

    #[Validate('required|string|max:255')]
    public string $shippingFirstName = '';

    #[Validate('required|string|max:255')]
    public string $shippingLastName = '';

    #[Validate('required|string|max:255')]
    public string $shippingAddress = '';

    #[Validate('nullable|string|max:255')]
    public string $shippingAddressPlus = '';

    #[Validate('required|string|max:20')]
    public string $shippingPostalCode = '';

    #[Validate('required|string|max:255')]
    public string $shippingCity = '';

    #[Validate('nullable|string|max:255')]
    public string $shippingState = '';

    #[Validate('nullable|string|max:20')]
    public string $shippingPhone = '';

    /** @var array<int, array<string, mixed>> */
    public array $deliveryOptions = [];

    public string|int|null $selectedDeliveryOption = null;

    public ?int $paymentMethodId = null;

    /** @var array<int, array<string, mixed>> */
    public array $paymentOptions = [];

    public function mount(): void
    {
        $cart = resolve(CartSessionManager::class)->current();

        if (! $cart || $cart->lines->isEmpty()) {
            $this->redirect(route('shop.cart'), navigate: true);

            return;
        }

        $this->restoreFromSession();
    }

    /** @return EloquentCollection<int, Address> */
    #[Computed]
    public function savedAddresses(): EloquentCollection
    {
        return auth()->user()->addresses()
            ->with('country')
            ->get();
    }

    #[Computed]
    public function cart(): ?CartModel
    {
        return resolve(CartSessionManager::class)->current();
    }

    #[Computed]
    public function cartContext(): ?CartPipelineContext
    {
        if (! $this->cart) {
            return null;
        }

        return resolve(CartManager::class)->calculate($this->cart);
    }

    public function selectAddress(int $addressId): void
    {
        $address = auth()->user()->addresses()->findOrFail($addressId);

        $this->prefillFromAddress($address);
    }

    public function clearAddress(): void
    {
        $this->selectedAddressId = null;
        $this->reset('shippingFirstName', 'shippingLastName', 'shippingAddress', 'shippingAddressPlus', 'shippingPostalCode', 'shippingCity', 'shippingState', 'shippingPhone');
    }

    public function saveShippingAddress(): void
    {
        $this->validate([
            'shippingFirstName' => 'required|string|max:255',
            'shippingLastName' => 'required|string|max:255',
            'shippingAddress' => 'required|string|max:255',
            'shippingPostalCode' => 'required|string|max:20',
            'shippingCity' => 'required|string|max:255',
        ]);

        $zone = ZoneSessionManager::getSession();
        $addressData = [
            'first_name' => $this->shippingFirstName,
            'last_name' => $this->shippingLastName,
            'street_address' => $this->shippingAddress,
            'street_address_plus' => $this->shippingAddressPlus,
            'postal_code' => $this->shippingPostalCode,
            'city' => $this->shippingCity,
            'state' => $this->shippingState,
            'phone_number' => $this->shippingPhone,
            'country_id' => $zone?->countryId,
        ];

        session()->put(CheckoutSession::SHIPPING_ADDRESS, $addressData);

        if ($this->cart) {
            resolve(CartManager::class)->addAddress($this->cart, AddressType::Shipping, [
                'first_name' => $this->shippingFirstName,
                'last_name' => $this->shippingLastName,
                'address_1' => $this->shippingAddress,
                'address_2' => $this->shippingAddressPlus,
                'postal_code' => $this->shippingPostalCode,
                'city' => $this->shippingCity,
                'phone' => $this->shippingPhone,
                'country_id' => $zone?->countryId,
            ]);
        }

        $packages = resolve(BuildShippingPackages::class)->handle();
        $this->deliveryOptions = resolve(FetchDeliveryRates::class)->handle($addressData, $packages);

        $this->step = 2;
    }

    public function saveShippingOption(): void
    {
        $this->validate([
            'selectedDeliveryOption' => 'required',
        ]);

        $selected = collect($this->deliveryOptions)
            ->first(fn (array $option): bool => $option['service_code'] === $this->selectedDeliveryOption);

        if (! $selected) {
            return;
        }

        session()->forget(CheckoutSession::SHIPPING_OPTION);

        session()->push(CheckoutSession::SHIPPING_OPTION, [
            'id' => $selected['service_code'],
            'name' => $selected['service_name'],
            'price' => is_no_division_currency($selected['currency'])
                ? $selected['amount']
                : $selected['amount'] / 100,
            'service_code' => $selected['service_code'],
            'carrier_code' => $selected['carrier_code'],
            'currency' => $selected['currency'],
            'estimated_days' => $selected['estimated_days'],
        ]);

        $this->loadPaymentMethods();

        $this->step = 3;
    }

    public function placeOrder(): void
    {
        $this->validate([
            'paymentMethodId' => 'required',
        ]);

        $selectedMethod = collect($this->paymentOptions)
            ->first(fn (array $method): bool => $method['id'] === $this->paymentMethodId);

        if (! $selectedMethod) {
            return;
        }

        session()->forget(CheckoutSession::PAYMENT);
        session()->push(CheckoutSession::PAYMENT, $selectedMethod);

        try {
            $order = resolve(CreateOrder::class)->handle();

            $service = resolve(PaymentProcessingService::class);
            $result = $service->initiate($order);

            session()->forget(CheckoutSession::KEY);

            if (! $result->success) {
                session()->flash('error', $result->message ?? __('Payment initiation failed.'));
                $this->redirect(route('shop.checkout.success', ['order' => $order->id]), navigate: true);

                return;
            }

            if ($result->clientSecret) {
                session()->put('stripe_payment', [
                    'client_secret' => $result->clientSecret,
                    'publishable_key' => $result->data['publishable_key'] ?? config('shopper.payment.drivers.stripe.credentials.publishable_key'),
                ]);

                $this->redirect(route('shop.checkout.stripe', ['number' => $order->number]));

                return;
            }

            if ($result->redirectUrl) {
                $this->redirect($result->redirectUrl);

                return;
            }

            $this->redirect(route('shop.checkout.success', ['order' => $order->id]), navigate: true);
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('notify', type: 'error', message: __('An error occurred while placing your order. Please try again.'));
        }
    }

    public function goToStep(int $step): void
    {
        if ($step < $this->step) {
            $shippingAddress = session()->get(CheckoutSession::SHIPPING_ADDRESS);

            if ($step === 2 && $shippingAddress) {
                $packages = resolve(BuildShippingPackages::class)->handle();
                $this->deliveryOptions = resolve(FetchDeliveryRates::class)->handle($shippingAddress, $packages);
            }

            if ($step === 3) {
                $this->loadPaymentMethods();
            }

            $this->step = $step;
        }
    }

    public function render(): View
    {
        return view('pages.shop.checkout')
            ->title(__('Checkout'));
    }

    private function restoreFromSession(): void
    {
        $checkout = session()->get(CheckoutSession::KEY, []);

        if ($address = data_get($checkout, 'shipping_address')) {
            $this->shippingFirstName = $address['first_name'] ?? '';
            $this->shippingLastName = $address['last_name'] ?? '';
            $this->shippingAddress = $address['street_address'] ?? '';
            $this->shippingAddressPlus = $address['street_address_plus'] ?? '';
            $this->shippingPostalCode = $address['postal_code'] ?? '';
            $this->shippingCity = $address['city'] ?? '';
            $this->shippingState = $address['state'] ?? '';
            $this->shippingPhone = $address['phone_number'] ?? '';
        } elseif ($this->savedAddresses->isNotEmpty()) {
            $default = $this->savedAddresses->firstWhere('shipping_default', true)
                ?? $this->savedAddresses->first();

            $this->prefillFromAddress($default);
        }

        $shippingOption = data_get($checkout, 'shipping_option.0');
        $this->selectedDeliveryOption = $shippingOption['id'] ?? null;
        $this->paymentMethodId = data_get($checkout, 'payment.0.id');
    }

    private function loadPaymentMethods(): void
    {
        $countryId = data_get(session()->get(CheckoutSession::SHIPPING_ADDRESS), 'country_id');

        if (! $countryId) {
            return;
        }

        $this->paymentOptions = resolve(FetchPaymentMethods::class)->handle($countryId);
    }

    private function prefillFromAddress(Address $address): void
    {
        $this->selectedAddressId = $address->id;
        $this->shippingFirstName = $address->first_name;
        $this->shippingLastName = $address->last_name;
        $this->shippingAddress = $address->street_address;
        $this->shippingAddressPlus = $address->street_address_plus ?? '';
        $this->shippingPostalCode = $address->postal_code;
        $this->shippingCity = $address->city;
        $this->shippingState = $address->state ?? '';
        $this->shippingPhone = $address->phone_number ?? '';
    }
}
