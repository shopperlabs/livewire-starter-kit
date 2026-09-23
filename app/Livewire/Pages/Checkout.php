<?php

declare(strict_types=1);

namespace App\Livewire\Pages;

use App\Actions\Checkout\CompleteCheckout;
use App\Actions\Checkout\CreatePaymentSession;
use App\Actions\Checkout\FetchDeliveryRates;
use App\Actions\Checkout\FetchPaymentMethods;
use App\Actions\ZoneSessionManager;
use App\Exceptions\CheckoutException;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Shopper\Cart\CartManager;
use Shopper\Cart\CartSessionManager;
use Shopper\Cart\Exceptions\DiscountLimitReachedException;
use Shopper\Cart\Exceptions\InsufficientStockException;
use Shopper\Cart\Exceptions\PriceChangedException;
use Shopper\Cart\Models\Cart as CartModel;
use Shopper\Cart\Models\CartAddress;
use Shopper\Cart\Pipelines\CartPipelineContext;
use Shopper\Core\Enum\AddressType;
use Shopper\Core\Exceptions\CampaignBudgetExceededException;
use Shopper\Core\Models\Address;
use Shopper\Payment\Services\PaymentProcessingService;
use Throwable;

final class Checkout extends Component
{
    use WithRateLimiting;

    #[Locked]
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

    #[Locked]
    public ?int $shippingCountryId = null;

    #[Locked]
    public array $deliveryOptions = [];

    #[Locked]
    public array $deliveryWarnings = [];

    public ?string $selectedDeliveryOption = null;

    public ?int $paymentMethodId = null;

    #[Locked]
    public array $paymentOptions = [];

    public function mount(): void
    {
        $cart = $this->cart;

        if (! $cart || $cart->lines->isEmpty()) {
            $this->redirect(route('shop.cart'), navigate: true);

            return;
        }

        $this->restoreFromCart($cart);
    }

    #[Computed]
    public function savedAddresses(): EloquentCollection
    {
        $countryIds = $this->cart?->zone?->countries->modelKeys() ?? [];

        return auth()->user()->addresses()
            ->whereIn('country_id', $countryIds)
            ->with('country')
            ->get();
    }

    #[Computed]
    public function cart(): ?CartModel
    {
        return resolve(CartSessionManager::class)->current()?->load('lines.purchasable.media');
    }

    #[Computed]
    public function cartContext(): ?CartPipelineContext
    {
        return $this->cart ? resolve(CartManager::class)->totals($this->cart) : null;
    }

    public function selectAddress(int $addressId): void
    {
        $address = $this->savedAddresses->find($addressId) ?? abort(404);

        $this->prefillFromAddress($address);
    }

    public function clearAddress(): void
    {
        $this->selectedAddressId = null;
        $this->shippingCountryId = null;
        $this->reset('shippingFirstName', 'shippingLastName', 'shippingAddress', 'shippingAddressPlus', 'shippingPostalCode', 'shippingCity', 'shippingState', 'shippingPhone');
    }

    public function saveShippingAddress(): void
    {
        $this->validate();

        $cart = $this->cart;

        if (! $cart) {
            $this->redirect(route('shop.cart'), navigate: true);

            return;
        }

        $address = [
            'first_name' => $this->shippingFirstName,
            'last_name' => $this->shippingLastName,
            'address_1' => $this->shippingAddress,
            'address_2' => $this->shippingAddressPlus ?: null,
            'postal_code' => $this->shippingPostalCode,
            'city' => $this->shippingCity,
            'state' => $this->shippingState ?: null,
            'phone' => $this->shippingPhone ?: null,
            'country_id' => $this->shippingCountryId ?? ZoneSessionManager::getSession()?->countryId,
        ];

        $manager = resolve(CartManager::class);
        $manager->addAddress($cart, AddressType::Shipping, $address);
        $manager->addAddress($cart, AddressType::Billing, $address);
        unset($this->cart, $this->cartContext);

        $this->loadDeliveryOptions();
        $this->step = 2;
    }

    public function saveShippingOption(): void
    {
        $this->validate([
            'selectedDeliveryOption' => 'required|string',
        ]);

        $option = collect($this->deliveryOptions)->firstWhere('id', $this->selectedDeliveryOption);
        $cart = $this->cart;

        if (! $option || ! $cart) {
            return;
        }

        resolve(CartManager::class)->setShippingMethod($cart, $option['id'], (int) $option['amount']);
        unset($this->cart, $this->cartContext);

        $this->loadPaymentMethods();
        $this->step = 3;
    }

    public function placeOrder(): void
    {
        try {
            $this->rateLimit(10);
        } catch (TooManyRequestsException) {
            $this->dispatch('notify', type: 'error', message: __('Too many attempts. Please slow down.'));

            return;
        }

        $this->validate([
            'paymentMethodId' => 'required|integer',
        ]);

        $method = collect($this->paymentOptions)->firstWhere('id', $this->paymentMethodId);
        $cart = $this->cart;

        if (! $method || ! $cart) {
            return;
        }

        $manager = resolve(CartManager::class);
        $previousSession = $cart->payment_session;
        $manager->setPaymentMethod($cart, (int) $method['id']);
        $manager->setEmail($cart, auth()->user()->email);

        if ($cart->payment_session === null) {
            resolve(CreatePaymentSession::class)->cancel($previousSession);
        }

        if (($method['driver'] ?? null) === 'stripe') {
            // The payment page opens or resumes the intent for the current total.
            $this->redirectRoute('shop.checkout.stripe');

            return;
        }

        try {
            $order = resolve(CompleteCheckout::class)->handle($cart);

            if ($order->wasRecentlyCreated) {
                resolve(PaymentProcessingService::class)->initiate($order);
            }

            resolve(CartSessionManager::class)->forget();

            $this->redirect(route('shop.checkout.success', ['order' => $order->id]), navigate: true);
        } catch (CheckoutException|PriceChangedException|InsufficientStockException|DiscountLimitReachedException|CampaignBudgetExceededException $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('notify', type: 'error', message: __('An error occurred while placing your order. Please try again.'));
        }
    }

    public function goToStep(int $step): void
    {
        if ($step >= $this->step) {
            return;
        }

        if ($step === 2) {
            $this->loadDeliveryOptions();
        }

        if ($step === 3) {
            $this->loadPaymentMethods();
        }

        $this->step = $step;
    }

    public function render(): View
    {
        return view('pages.shop.checkout')
            ->title(__('Checkout'));
    }

    private function restoreFromCart(CartModel $cart): void
    {
        $shipping = $cart->shippingAddress();

        if ($shipping) {
            $this->prefillFromCartAddress($shipping);
        } elseif ($this->savedAddresses->isNotEmpty()) {
            $default = $this->savedAddresses->firstWhere('shipping_default', true)
                ?? $this->savedAddresses->first();

            $this->prefillFromAddress($default);
        }

        $this->selectedDeliveryOption = $cart->shipping_option_id;
        $this->paymentMethodId = $cart->payment_method_id ? (int) $cart->payment_method_id : null;
    }

    private function loadDeliveryOptions(): void
    {
        $cart = $this->cart;

        if (! $cart) {
            return;
        }

        ['options' => $this->deliveryOptions, 'warnings' => $this->deliveryWarnings] = resolve(FetchDeliveryRates::class)->handle($cart);
    }

    private function loadPaymentMethods(): void
    {
        $zone = $this->cart?->zone;

        $this->paymentOptions = $zone ? resolve(FetchPaymentMethods::class)->handle($zone) : [];
    }

    private function prefillFromAddress(Address $address): void
    {
        $this->selectedAddressId = $address->id;
        $this->shippingCountryId = $address->country_id;
        $this->shippingFirstName = $address->first_name;
        $this->shippingLastName = $address->last_name;
        $this->shippingAddress = $address->street_address;
        $this->shippingAddressPlus = $address->street_address_plus ?? '';
        $this->shippingPostalCode = $address->postal_code;
        $this->shippingCity = $address->city;
        $this->shippingState = $address->state ?? '';
        $this->shippingPhone = $address->phone_number ?? '';
    }

    private function prefillFromCartAddress(CartAddress $address): void
    {
        $this->shippingCountryId = $address->country_id;
        $this->shippingFirstName = $address->first_name ?? '';
        $this->shippingLastName = $address->last_name ?? '';
        $this->shippingAddress = $address->address_1 ?? '';
        $this->shippingAddressPlus = $address->address_2 ?? '';
        $this->shippingPostalCode = $address->postal_code ?? '';
        $this->shippingCity = $address->city ?? '';
        $this->shippingState = $address->state ?? '';
        $this->shippingPhone = $address->phone ?? '';
    }
}
