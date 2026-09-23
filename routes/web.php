<?php

declare(strict_types=1);

use App\Http\Controllers\StripeReturnController;
use App\Livewire\Account\Addresses;
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
    Route::get('checkout/payment', StripePayment::class)->name('shop.checkout.stripe');
    Route::get('checkout/payment/return', StripeReturnController::class)->name('shop.checkout.stripe-return');
    Route::livewire('checkout/success/{order}', 'pages::shop.checkout-success')->name('shop.checkout.success');
});

// Account
Route::middleware(['auth', 'verified'])->prefix('account')->group(function (): void {
    Route::livewire('orders', 'pages::account.orders')->name('account.orders');
    Route::livewire('orders/{order}', 'pages::account.order-show')->name('account.orders.show');
    Route::get('addresses', Addresses::class)->name('account.addresses');
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';
