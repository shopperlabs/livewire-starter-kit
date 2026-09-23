<?php

declare(strict_types=1);

use App\Actions\Checkout\CompleteCheckout;
use App\Exceptions\CheckoutException;
use App\Livewire\Pages\Checkout;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Shopper\Cart\CartManager;
use Shopper\Cart\Exceptions\InsufficientStockException;
use Shopper\Cart\Exceptions\PriceChangedException;
use Shopper\Core\Models\Order;
use Shopper\Core\Models\PaymentMethod;
use Shopper\Core\Models\Zone;
use Shopper\Payment\Models\PaymentTransaction;

it('places an order from the cart with the delivery and payment frozen on it', function (): void {
    Livewire::test(Checkout::class)
        ->set('shippingFirstName', 'John')
        ->set('shippingLastName', 'Doe')
        ->set('shippingAddress', '1 Main Street')
        ->set('shippingPostalCode', '10001')
        ->set('shippingCity', 'New York')
        ->call('saveShippingAddress')
        ->assertSet('step', 2)
        ->assertSet('deliveryOptions.0.id', "main-carrier:{$this->option->public_id}")
        ->set('selectedDeliveryOption', "main-carrier:{$this->option->public_id}")
        ->call('saveShippingOption')
        ->assertSet('step', 3)
        ->set('paymentMethodId', $this->paymentMethod->id)
        ->call('placeOrder')
        ->assertHasNoErrors()
        ->assertRedirect();

    $order = Order::query()->sole();

    expect($order->price_amount)->toBe(5700)
        ->and($order->shipping_amount)->toBe(700)
        ->and($order->shipping_option_id)->toBe($this->option->id)
        ->and($order->payment_method_id)->toBe($this->paymentMethod->id)
        ->and($order->email)->toBe($this->user->email)
        ->and($order->shippingAddress->city)->toBe('New York')
        ->and($this->cart->refresh()->order_id)->toBe($order->id)
        ->and(PaymentTransaction::query()->where('order_id', $order->id)->count())->toBe(1);
});

it('keeps the customer on the address step when the address is incomplete', function (): void {
    Livewire::test(Checkout::class)
        ->set('shippingFirstName', '')
        ->set('shippingPhone', str_repeat('9', 30))
        ->call('saveShippingAddress')
        ->assertHasErrors(['shippingFirstName' => 'required', 'shippingPhone' => 'max'])
        ->assertSet('step', 1);
});

it('refuses to complete a cart without a payment method', function (): void {
    resolve(CompleteCheckout::class)->handle($this->cart);
})->throws(CheckoutException::class);

it('refuses a payment method that does not belong to the cart zone', function (): void {
    $foreign = PaymentMethod::factory()->create(['is_enabled' => true, 'driver' => 'manual']);
    $foreign->zones()->attach(Zone::factory()->create(['is_enabled' => true]));

    resolve(CompleteCheckout::class)->handle(prepareCartForPayment($this->cart, $foreign));
})->throws(CheckoutException::class, 'The selected payment method is not available');

it('refuses a delivery option that is no longer offered', function (): void {
    $cart = prepareCartForPayment($this->cart, $this->paymentMethod);
    $this->option->update(['is_enabled' => false]);

    resolve(CompleteCheckout::class)->handle($cart);
})->throws(CheckoutException::class, 'no longer available');

it('refuses a delivery price that went up since it was chosen', function (): void {
    $cart = prepareCartForPayment($this->cart, $this->paymentMethod);
    $this->option->update(['price' => 1500]);

    resolve(CompleteCheckout::class)->handle($cart);
})->throws(CheckoutException::class, 'delivery price changed');

it('refuses to sell more than the stock', function (): void {
    $cart = prepareCartForPayment($this->cart, $this->paymentMethod);
    $this->product->mutateStock($this->inventory->id, -99);

    resolve(CompleteCheckout::class)->handle($cart);
})->throws(InsufficientStockException::class);

it('refuses a cart whose prices changed', function (): void {
    $cart = prepareCartForPayment($this->cart, $this->paymentMethod);
    $this->product->prices()->update(['amount' => 3000]);

    resolve(CompleteCheckout::class)->handle($cart);
})->throws(PriceChangedException::class);

it('does not create a second order for the same cart', function (): void {
    $cart = prepareCartForPayment($this->cart, $this->paymentMethod);

    $first = resolve(CompleteCheckout::class)->handle($cart);
    $second = resolve(CompleteCheckout::class)->handle($cart->refresh());

    expect($second->id)->toBe($first->id)
        ->and(Order::query()->count())->toBe(1);
});

it('adopts the guest cart when the customer logs in', function (): void {
    Auth::logout();
    $guestCart = cartSession();
    resolve(CartManager::class)->add($guestCart, $this->product, 1);

    Auth::login($this->user);

    expect($guestCart->refresh()->customer_id)->toBe($this->user->id);
});

it('hides the confirmation page of another customer order', function (): void {
    $order = resolve(CompleteCheckout::class)->handle(prepareCartForPayment($this->cart, $this->paymentMethod));

    $this->actingAs(User::factory()->create())
        ->get(route('shop.checkout.success', $order))
        ->assertForbidden();
});

it('sends the customer back to the cart when it is empty', function (): void {
    resolve(CartManager::class)->clear($this->cart);

    Livewire::test(Checkout::class)->assertRedirect(route('shop.cart'));
});
