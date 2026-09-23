<?php

declare(strict_types=1);

use App\Actions\ZoneSessionManager;
use App\DTO\CountryByZoneData;
use App\Models\Product;
use App\Models\User;
use Shopper\Cart\CartManager;
use Shopper\Core\Models\Carrier;
use Shopper\Core\Models\CarrierOption;
use Shopper\Core\Models\Country;
use Shopper\Core\Models\Currency;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\PaymentMethod;
use Shopper\Core\Models\Setting;
use Shopper\Core\Models\Zone;
use Shopper\Database\Seeders\AuthTableSeeder;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

/*
|--------------------------------------------------------------------------
| Store fixture
|--------------------------------------------------------------------------
|
| Every shop test runs against one US zone in USD with a flat-rate carrier,
| a manual payment method, a stocked product and a signed-in customer whose
| cart already holds two units.
|
*/

pest()->beforeEach(function (): void {
    $this->seed(AuthTableSeeder::class);

    $usd = Currency::query()->firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$', 'format' => '$1,234.56']);

    Setting::query()->updateOrCreate(['key' => 'currencies'], ['value' => [$usd->id], 'display_name' => 'Currencies', 'locked' => true]);
    Setting::query()->updateOrCreate(['key' => 'default_currency_id'], ['value' => $usd->id, 'display_name' => 'Default currency', 'locked' => true]);

    $this->country = Country::factory()->create(['cca2' => 'US', 'name' => 'United States']);
    $this->zone = Zone::factory()->create(['is_enabled' => true, 'currency_id' => $usd->id]);
    $this->zone->countries()->attach($this->country);

    $carrier = Carrier::factory()->create(['name' => 'Main Carrier', 'slug' => 'main-carrier', 'is_enabled' => true]);
    $this->zone->carriers()->attach($carrier);
    $this->option = CarrierOption::factory()->create([
        'name' => 'Standard',
        'price' => 700,
        'is_enabled' => true,
        'carrier_id' => $carrier->id,
        'zone_id' => $this->zone->id,
    ]);

    $this->paymentMethod = PaymentMethod::factory()->create(['title' => 'Cash on delivery', 'is_enabled' => true, 'driver' => 'manual']);
    $this->paymentMethod->zones()->attach($this->zone);

    $this->inventory = $inventory = Inventory::factory()->create(['is_default' => true, 'priority' => 0, 'country_id' => $this->country->id]);
    $this->product = Product::factory()->standard()->publish()->create();
    $this->product->prices()->create(['amount' => 2500, 'currency_id' => $usd->id]);
    $this->product->mutateStock($inventory->id, 100);
    $this->product->refresh();

    ZoneSessionManager::setSession(CountryByZoneData::fromArray([
        'zone_id' => $this->zone->id,
        'zone_name' => $this->zone->name,
        'zone_code' => $this->zone->code,
        'country_id' => $this->country->id,
        'country_name' => $this->country->name,
        'country_code' => 'US',
        'country_flag' => '',
        'currency_code' => 'USD',
    ]));

    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->cart = cartSession();
    resolve(CartManager::class)->add($this->cart, $this->product, 2);
})->in('Feature/Shop');

/**
 * Put the cart in the state the payment step expects: addresses, delivery
 * option, payment method and email set.
 */
function prepareCartForPayment(Shopper\Cart\Models\Cart $cart, Shopper\Core\Models\PaymentMethod $method): Shopper\Cart\Models\Cart
{
    $manager = resolve(Shopper\Cart\CartManager::class);
    $address = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address_1' => '1 Main Street',
        'postal_code' => '10001',
        'city' => 'New York',
        'country_id' => test()->country->id,
    ];

    $manager->addAddress($cart, Shopper\Core\Enum\AddressType::Shipping, $address);
    $manager->addAddress($cart, Shopper\Core\Enum\AddressType::Billing, $address);
    $manager->setShippingMethod($cart, "main-carrier:".test()->option->public_id, 700);
    $manager->setPaymentMethod($cart, $method->id);
    $manager->setEmail($cart, test()->user->email);

    return $cart->refresh();
}

expect()->extend('toBeOne', fn () => $this->toBe(1));
