<?php

declare(strict_types=1);

use App\Livewire\Pages\Cart;
use App\Livewire\Pages\CategoryIndex;
use App\Livewire\Pages\CategoryShow;
use App\Livewire\Pages\Checkout;
use App\Livewire\Pages\CollectionShow;
use App\Livewire\Pages\Home;
use App\Livewire\Pages\ProductIndex;
use App\Livewire\Pages\ProductShow;
use App\Livewire\Pages\SearchProducts;
use App\Livewire\Pages\StripePayment;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Storefront
Route::get('/', Home::class)->name('home');
Route::get('shop', ProductIndex::class)->name('shop.index');
Route::get('shop/{product:slug}', ProductShow::class)->name('shop.product');
Route::get('categories', CategoryIndex::class)->name('shop.categories');
Route::get('categories/{category:slug}', CategoryShow::class)->name('shop.category');
Route::get('collections/{collection:slug}', CollectionShow::class)->name('shop.collection');
Route::get('search', SearchProducts::class)->name('shop.search');
Route::get('cart', Cart::class)->name('shop.cart');

// Checkout
Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('checkout', Checkout::class)->name('shop.checkout');
    Route::get('checkout/payment/{number}', StripePayment::class)->name('shop.checkout.stripe');
    Volt::route('checkout/success/{order}', 'shop.checkout-success')->name('shop.checkout.success');
});

// Webhooks
Route::post('webhooks/stripe', App\Http\Controllers\StripeWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('webhooks.stripe');

// Account
Route::middleware(['auth', 'verified'])->prefix('account')->group(function (): void {
    Volt::route('orders', 'account.orders')->name('account.orders');
    Volt::route('orders/{order}', 'account.order-show')->name('account.orders.show');
    Route::get('addresses', App\Livewire\Account\Addresses::class)->name('account.addresses');
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';
