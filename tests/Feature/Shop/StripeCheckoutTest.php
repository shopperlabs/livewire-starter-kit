<?php

declare(strict_types=1);

use App\Livewire\Pages\StripePayment;
use Livewire\Livewire;
use Shopper\Cart\CartManager;
use Shopper\Core\Models\Order;
use Shopper\Core\Models\PaymentMethod;
use Shopper\Payment\Contracts\PaymentDriver;
use Shopper\Payment\DataTransferObjects\PaymentResult;
use Shopper\Payment\Models\PaymentTransaction;
use Shopper\Payment\Models\PaymentWebhookEvent;
use Shopper\Payment\PaymentManager;
use Shopper\Stripe\StripeDriver;

beforeEach(function (): void {
    $this->stripe = Mockery::mock(PaymentDriver::class);
    $this->stripe->allows('isConfigured')->andReturnTrue();
    $this->stripe->allows('code')->andReturn('stripe');

    resolve(PaymentManager::class)
        ->extend('stripe', fn (): PaymentDriver => $this->stripe)
        ->forgetDrivers();

    $method = PaymentMethod::factory()->create(['title' => 'Card', 'is_enabled' => true, 'driver' => 'stripe']);
    $method->zones()->attach($this->zone);

    $this->cart = prepareCartForPayment($this->cart, $method);

    resolve(CartManager::class)->setPaymentSession($this->cart, [
        'driver' => 'stripe',
        'reference' => 'pi_123',
        'amount' => 5700,
        'currency' => 'USD',
    ]);
});

function intent(string $status, int $amount = 5700): PaymentResult
{
    return new PaymentResult(success: true, status: $status, reference: 'pi_123', clientSecret: 'pi_123_secret', amount: $amount);
}

it('creates the order when Stripe sends the customer back', function (): void {
    $this->stripe->allows('retrievePayment')->with('pi_123')->andReturn(intent('authorized'));

    $response = $this->get(route('shop.checkout.stripe-return', ['payment_intent' => 'pi_123']));
    $order = Order::query()->sole();

    $response->assertRedirect(route('shop.checkout.success', ['order' => $order]));

    expect($order->price_amount)->toBe(5700)
        ->and(PaymentTransaction::query()->where('reference', 'pi_123')->value('order_id'))->toBe($order->id);

    // A refreshed return URL lands on the same order.
    $this->get(route('shop.checkout.stripe-return', ['payment_intent' => 'pi_123']))
        ->assertRedirect(route('shop.checkout.success', ['order' => $order]));

    expect(Order::query()->count())->toBe(1);
});

it('finds the cart without the session when the return opens elsewhere', function (): void {
    $this->stripe->allows('retrievePayment')->andReturn(intent('authorized'));
    session()->flush();

    $this->get(route('shop.checkout.stripe-return', ['payment_intent' => 'pi_123']))
        ->assertRedirectContains('/checkout/success/');

    expect(Order::query()->count())->toBe(1);
});

it('ignores an intent that is not pinned on one of the customer carts', function (): void {
    $this->get(route('shop.checkout.stripe-return', ['payment_intent' => 'pi_other']))
        ->assertRedirect(route('shop.checkout'))
        ->assertSessionHasErrors('payment');

    expect(Order::query()->count())->toBe(0);
});

it('does not create the order when the payment is not authorized', function (): void {
    $this->stripe->allows('retrievePayment')->andReturn(intent('requires_action'));

    $this->get(route('shop.checkout.stripe-return', ['payment_intent' => 'pi_123']))
        ->assertRedirect(route('shop.checkout'))
        ->assertSessionHasErrors('payment');

    expect(Order::query()->count())->toBe(0);
});

it('releases the payment when the order cannot be created', function (): void {
    $this->stripe->allows('retrievePayment')->andReturn(intent('authorized'));
    $this->stripe->expects('cancelPayment')->with('pi_123')->andReturn(intent('canceled'));
    $this->option->update(['is_enabled' => false]);

    $this->get(route('shop.checkout.stripe-return', ['payment_intent' => 'pi_123']))
        ->assertRedirect(route('shop.cart'))
        ->assertSessionHasErrors('order');

    expect(Order::query()->count())->toBe(0)
        ->and($this->cart->refresh()->payment_session)->toBeNull();
});

it('refunds a captured payment when the order cannot be created', function (): void {
    $this->stripe->allows('retrievePayment')->andReturn(intent('captured'));
    $this->stripe->expects('refundPayment')->withArgs(['pi_123', 5700, 'requested_by_customer'])->andReturn(intent('refunded'));
    $this->product->prices()->update(['amount' => 3000]);

    $this->get(route('shop.checkout.stripe-return', ['payment_intent' => 'pi_123']))
        ->assertRedirect(route('shop.cart'));

    expect(Order::query()->count())->toBe(0);
});

it('sends an already paid intent straight to the order instead of the payment form', function (): void {
    $this->stripe->allows('retrievePayment')->andReturn(intent('authorized'));

    Livewire::test(StripePayment::class)
        ->assertRedirect(route('shop.checkout.stripe-return', ['payment_intent' => 'pi_123']));
});

it('opens a new intent for the current total when the cart changed', function (): void {
    resolve(CartManager::class)->add($this->cart, $this->product, 1);

    $this->stripe->expects('initiatePayment')->withArgs(fn (int $amount): bool => $amount === 8200)
        ->andReturn(new PaymentResult(success: true, status: 'pending', reference: 'pi_456', clientSecret: 'pi_456_secret'));
    $this->stripe->expects('cancelPayment')->with('pi_123')->andReturn(intent('canceled'));

    Livewire::test(StripePayment::class)
        ->assertSet('clientSecret', 'pi_456_secret')
        ->assertSet('amount', 8200);

    expect($this->cart->refresh()->payment_session['reference'])->toBe('pi_456');
});

it('rejects a Stripe webhook with an invalid signature', function (): void {
    resolve(PaymentManager::class)
        ->extend('stripe', fn (): PaymentDriver => new StripeDriver('sk_test_fake', 'pk_test_fake', 'whsec_fake'))
        ->forgetDrivers();

    $this->postJson('/webhooks/stripe', ['id' => 'evt_1'], ['Stripe-Signature' => 't=1,v1=forged'])
        ->assertStatus(400);

    expect(PaymentWebhookEvent::query()->count())->toBe(0);
});

it('rejects webhooks for unknown payment drivers', function (): void {
    $this->postJson('/webhooks/ghost')->assertNotFound();
});
