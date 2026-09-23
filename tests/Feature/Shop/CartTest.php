<?php

declare(strict_types=1);

use App\Livewire\Pages\Cart;
use App\Livewire\ZoneSelector;
use App\Actions\ZoneSessionManager;
use Livewire\Livewire;
use Shopper\Core\Models\Country;
use Shopper\Core\Models\Currency;
use Shopper\Core\Models\Discount;
use Shopper\Core\Models\Zone;

it('updates, removes and clears cart lines', function (): void {
    $line = $this->cart->lines()->sole();

    Livewire::test(Cart::class)
        ->call('updateQuantity', $line->id, 3)
        ->assertDispatched('cart-updated');

    expect($line->refresh()->quantity)->toBe(3);

    Livewire::test(Cart::class)->call('removeLine', $line->id);

    expect($this->cart->lines()->count())->toBe(0);
});

it('warns instead of failing when the quantity exceeds the stock', function (): void {
    $line = $this->cart->lines()->sole();

    Livewire::test(Cart::class)
        ->call('updateQuantity', $line->id, 500)
        ->assertDispatched('notify', type: 'error');

    expect($line->refresh()->quantity)->toBe(2);
});

it('shows cart amounts in the cart currency', function (): void {
    Currency::query()->firstOrCreate(['code' => 'EUR'], ['name' => 'Euro', 'symbol' => '€', 'format' => '1.234,56 €']);
    $this->cart->update(['currency_code' => 'EUR']);

    Livewire::test(Cart::class)->assertSee(shopper_money_format(2500, 'EUR'));
});

it('applies a valid coupon and removes it', function (): void {
    Discount::factory()->create([
        'code' => 'WELCOME10',
        'is_active' => true,
        'type' => 'percentage',
        'value' => 10,
        'apply_to' => 'order',
        'min_required' => 'none',
        'eligibility' => 'everyone',
    ]);

    Livewire::test(Cart::class)
        ->set('couponCode', 'WELCOME10')
        ->call('applyCoupon')
        ->assertHasNoErrors()
        ->assertSet('couponCode', '');

    expect($this->cart->promotions()->where('code', 'WELCOME10')->exists())->toBeTrue();

    Livewire::test(Cart::class)->call('removeCoupon', 'WELCOME10');

    expect($this->cart->promotions()->where('code', 'WELCOME10')->exists())->toBeFalse();
});

it('answers the same way for unknown and inactive coupons', function (): void {
    Discount::factory()->create(['code' => 'EXPIRED', 'is_active' => false]);

    $unknown = Livewire::test(Cart::class)->set('couponCode', 'NOPE')->call('applyCoupon');
    $inactive = Livewire::test(Cart::class)->set('couponCode', 'EXPIRED')->call('applyCoupon');

    expect($unknown->errors()->first('couponCode'))->toBe($inactive->errors()->first('couponCode'))
        ->and($this->cart->promotions()->count())->toBe(0);
});

it('throttles coupon attempts', function (): void {
    $component = Livewire::test(Cart::class)->set('couponCode', 'GUESS');

    foreach (range(1, 5) as $attempt) {
        $component->call('applyCoupon');
    }

    $component->call('applyCoupon')
        ->assertHasErrors('couponCode');

    expect($component->errors()->first('couponCode'))->toContain('Too many attempts');
});

it('re-prices the cart and resets the payment choice when the zone changes', function (): void {
    $eur = Currency::query()->firstOrCreate(['code' => 'EUR'], ['name' => 'Euro', 'symbol' => '€', 'format' => '1.234,56 €']);
    $france = Country::factory()->create(['cca2' => 'FR', 'name' => 'France']);
    $europe = Zone::factory()->create(['is_enabled' => true, 'currency_id' => $eur->id]);
    $europe->countries()->attach($france);
    $this->product->prices()->create(['amount' => 2300, 'currency_id' => $eur->id]);

    $cart = prepareCartForPayment($this->cart, $this->paymentMethod);

    Livewire::test(ZoneSelector::class)->call('selectZone', $france->id);

    $cart->refresh();

    expect($cart->zone_id)->toBe($europe->id)
        ->and($cart->currency_code)->toBe('EUR')
        ->and($cart->payment_method_id)->toBeNull()
        ->and($cart->shipping_option_id)->toBeNull()
        ->and($cart->lines()->sole()->unit_price_amount)->toBe(2300)
        ->and(ZoneSessionManager::getSession()->currencyCode)->toBe('EUR');
});
