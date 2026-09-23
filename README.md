<p>
  <a href="https://github.com/shopperlabs/shopper/blob/3.x/CONTRIBUTING.md">
    <img src="https://img.shields.io/badge/PRs-welcome-brightgreen.svg?style=flat" alt="PRs welcome!" />
  </a>
  <a href="https://laravelshopper.dev/discord">
    <img src="https://img.shields.io/badge/chat-on%20discord-7289DA.svg" alt="Discord Chat" />
  </a>
  <a href="https://twitter.com/intent/follow?screen_name=laravelshopper">
    <img src="https://img.shields.io/twitter/follow/laravelshopper.svg?label=Follow%20@laravelshopper" alt="Follow @laravelshopper" />
  </a>
</p>

# Shopper Livewire Starter Kit

A complete, production-ready storefront for [Shopper](https://shopperphp.com), built with **Livewire 4**, **Flux UI** and **Tailwind CSS v4**.

## Features

### Storefront

- **Home page** with featured products, collections and category browsing
- **Product catalog** with search, category filtering and sorting
- **Product detail** with variant selection (color, size), image gallery and related products
- **Collection & category pages** with pagination
- **Full-text search** across name, description and SKU

### Cart & Checkout

- **Shopping cart** with quantity management, promotion codes and real-time totals
- **Guest cart merge** into the customer cart on login
- **Multi-step checkout** (Shipping address, Delivery options, Payment)
- **Saved addresses** selection and new address form
- **Carrier rate calculation** with real shipping providers, re-quoted before the order is placed
- **Stripe payment** with Payment Element, manual capture by default and automatic release when the order cannot be created
- **Manual payment** methods (cash on delivery, bank transfer)
- **Order confirmation** page with the live payment status

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

- PHP ^8.3
- Laravel ^12.68 | ^13.27
- Shopper ^3.0

## Installation

### On a new Shopper project

```bash
php artisan shopper:kit:install shopperlabs/livewire-starter-kit
```

### On an existing project

We recommend creating a new git branch before installing:

```bash
git checkout -b storefront
php artisan shopper:kit:install shopperlabs/livewire-starter-kit
```

The installer will:

1. Copy storefront files (views, components, routes, models, actions)
2. Install required Composer dependencies (Livewire, Flux UI, Blaze, Fortify, Shopper Stripe)
3. Run database migrations
4. Create the storage symlink
5. Install npm dependencies and build assets

## Configuration

### Stripe

Add your keys to `.env`, then enable the Stripe payment method in the Shopper admin for each zone:

```dotenv
PAYMENT_STRIPE_ENABLED=true
STRIPE_SECRET_KEY=sk_test_...
STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_CAPTURE_METHOD=manual
```

Point a Stripe webhook endpoint to `https://your-store.test/webhooks/stripe` with the `payment_intent.amount_capturable_updated`, `payment_intent.succeeded`, `payment_intent.payment_failed`, `payment_intent.canceled`, `refund.created` and `refund.updated` events. Locally, forward events with:

```bash
stripe listen --forward-to your-store.test/webhooks/stripe
```

### Scheduler and queue worker

Shopper reconciles pending payments and releases abandoned orders on a schedule, and the reconciliation dispatches queued jobs. In production, run both:

```bash
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
php artisan queue:work
```

## What's Included

```
app/
├── Actions/           # Business logic (cart, checkout, products)
├── Concerns/          # Shared validation traits
├── DTO/               # Data transfer objects (price, zone)
├── Exceptions/        # Checkout exception shown to the customer
├── Http/Controllers/  # Stripe return and payment webhooks
├── Livewire/          # All storefront components
│   ├── Account/       # Address management
│   ├── Home/          # Featured products, collections, categories
│   └── Pages/         # Cart, checkout, product listing, search
├── Models/            # Extended Shopper models (Product, Category)
├── Providers/         # App and Fortify service providers
└── Traits/            # Product pricing trait

resources/views/
├── components/        # Blade components (cards, badges, notifications)
├── layouts/           # Store, account, auth layouts
├── livewire/          # Livewire component views
└── pages/             # Full page views (shop, auth, settings, account)

routes/
├── web.php            # Storefront and checkout routes
├── settings.php       # Profile and security routes
└── webhooks.php       # Payment provider webhooks, outside the web middleware

tests/
├── Feature/Auth/      # Authentication tests
├── Feature/Settings/  # Profile and security tests
├── Feature/Shop/      # Cart, checkout, Stripe return and storefront tests
└── Feature/           # Dashboard test
```

## Customization

This is a starter kit, not a theme. Once installed, the code belongs to you. You can modify, delete or reorganize anything.

### Models

Product, Category, Channel and ProductVariant models extend Shopper's base models. Add your own methods, scopes or relationships directly.

### Views

All Blade views use Flux UI components and Tailwind CSS. The brand color is the Flux accent, set once in `resources/css/app.css` (`--color-accent`), and the store name comes from `APP_NAME`. The logo lives in `resources/views/components/brand/`.

### Checkout Flow

The checkout is split into Actions (`app/Actions/Checkout/`) for easy modification:

- `FetchDeliveryRates` — Shipping rate calculation for the cart zone
- `FetchPaymentMethods` — Payment methods of the zone the storefront supports
- `BuildShippingPackages` — Package weight and dimensions from the cart
- `CreatePaymentSession` — Opens or resumes the provider payment for the cart total
- `CompleteCheckout` — Turns the cart into an order, idempotent

To add another payment provider, add its driver to `FetchPaymentMethods::SUPPORTED_DRIVERS` once its payment page and return flow exist.

## Dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| livewire/livewire | ^4.4 | Reactive components |
| livewire/flux | ^2.20 | UI component library |
| livewire/blaze | ^1.0 | Blade component compiler |
| laravel/fortify | ^1.40 | Authentication backend |
| danharrin/livewire-rate-limiting | ^2.3 | Rate limiting on checkout and coupons |
| shopper/stripe | ^3.0 | Stripe payment driver |

## License

MIT
