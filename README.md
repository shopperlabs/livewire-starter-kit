# Shopper Livewire Starter Kit

A complete, production-ready storefront for [Shopper](https://laravelshopper.dev), built with **Livewire 3**, **Flux UI** and **Tailwind CSS v4**.

## Features

### Storefront

- **Home page** with featured products, collections and category browsing
- **Product catalog** with search, category filtering and sorting
- **Product detail** with variant selection (color, size), image gallery and related products
- **Collection & category pages** with pagination
- **Full-text search** across name, description and SKU

### Cart & Checkout

- **Shopping cart** with quantity management and real-time totals
- **Multi-step checkout** (Shipping address, Delivery options, Payment)
- **Saved addresses** selection and new address form
- **Carrier rate calculation** with real shipping providers
- **Stripe payment** integration with Payment Element
- **Order confirmation** page

### Customer Account

- **Dashboard** with quick links to orders, addresses and profile
- **Order history** with status tracking (payment, shipping)
- **Order detail** with item breakdown and shipping info
- **Address management** (add, edit, delete, set defaults)
- **Profile settings** (name, email, gender)
- **Security settings** (password, two-factor authentication)

### Multi-Currency & Zones

- **Zone selector** for country-based pricing and currency
- **Automatic currency** switching based on selected zone
- **Tax-inclusive/exclusive** labels per zone

### UI & Accessibility

- **Dark mode** support on all pages
- **Responsive design** (mobile-first with Tailwind breakpoints)
- **Flux UI components** (buttons, inputs, modals, badges, navbar, radio cards)
- **ARIA labels** and screen reader support
- **Keyboard navigation** on all interactive elements

## Requirements

- PHP ^8.4
- Laravel ^12.0
- Shopper ^2.7

## Installation

### On a new Shopper project

```bash
php artisan shopper:kit:install shopper/livewire-starter-kit
```

### On an existing project

We recommend creating a new git branch before installing:

```bash
git checkout -b storefront
php artisan shopper:kit:install shopper/livewire-starter-kit
```

The installer will:

1. Copy storefront files (views, components, routes, models, actions)
2. Install required Composer dependencies (Livewire, Flux UI, Volt, Fortify)
3. Run database migrations
4. Create the storage symlink
5. Install npm dependencies and build assets

## What's Included

```
app/
├── Actions/           # Business logic (cart, checkout, products)
├── Concerns/          # Shared validation traits
├── DTO/               # Data transfer objects (price, address, zone)
├── Http/Controllers/  # Stripe webhook handler
├── Livewire/          # All storefront components
│   ├── Account/       # Address management
│   ├── Home/          # Featured products, collections, categories
│   └── Pages/         # Cart, checkout, product listing, search
├── Models/            # Extended Shopper models (Product, Category)
├── Providers/         # Fortify, Volt service providers
└── Traits/            # Product pricing trait

resources/views/
├── components/        # Blade components (cards, badges, notifications)
├── layouts/           # Store, account, auth layouts
├── livewire/          # Livewire component views
└── pages/             # Full page views (shop, auth, settings, account)

routes/
├── web.php            # Storefront and checkout routes
└── settings.php       # Profile and security routes

tests/
├── Feature/Auth/      # Authentication tests
├── Feature/Settings/  # Profile and security tests
└── Feature/           # Dashboard test
```

## Customization

This is a starter kit, not a theme. Once installed, the code belongs to you. You can modify, delete or reorganize anything.

### Models

Product, Category, Channel and ProductVariant models extend Shopper's base models. Add your own methods, scopes or relationships directly.

### Views

All Blade views use Flux UI components and Tailwind CSS. Customize colors, layouts and components as needed.

### Checkout Flow

The checkout is split into Actions (`app/Actions/Checkout/`) for easy modification:

- `FetchDeliveryRates` — Shipping rate calculation
- `FetchPaymentMethods` — Payment method loading
- `BuildShippingPackages` — Package dimensions from cart
- `ResolveZoneForCountry` — Zone resolution with caching

## Dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| livewire/livewire | ^3.7 | Reactive components |
| livewire/flux | ^2.0 | UI component library |
| livewire/volt | ^1.7 | Single-file components |
| laravel/fortify | ^1.25 | Authentication backend |

## License

MIT
