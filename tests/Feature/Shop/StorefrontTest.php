<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;

it('renders the public storefront pages', function (string $route): void {
    $this->get(route($route))->assertOk();
})->with(['home', 'shop.index', 'shop.categories', 'shop.search', 'shop.cart']);

it('shows a published product with its price', function (): void {
    $this->get(route('shop.product', $this->product))
        ->assertOk()
        ->assertSee($this->product->name)
        ->assertSee(shopper_money_format(2500, 'USD'));
});

it('hides an unpublished product', function (): void {
    $draft = Product::factory()->standard()->create(['is_visible' => false]);

    $this->get(route('shop.product', $draft))->assertNotFound();
});

it('lists the products of a category', function (): void {
    $category = Category::factory()->create(['is_enabled' => true, 'slug' => 'phones']);
    $this->product->categories()->attach($category);

    $this->get(route('shop.category', $category))
        ->assertOk()
        ->assertSee($this->product->name);
});

it('finds products by name', function (): void {
    $this->get(route('shop.search', ['q' => $this->product->name]))
        ->assertOk()
        ->assertSee($this->product->name);
});
