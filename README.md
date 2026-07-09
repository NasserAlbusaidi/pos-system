# Bite POS

[![CI](https://github.com/NasserAlbusaidi/pos-system/actions/workflows/ci.yml/badge.svg)](https://github.com/NasserAlbusaidi/pos-system/actions/workflows/ci.yml)

Bite is a Laravel 12 + Livewire 3 point-of-sale system for restaurants and retail shops. The application lives in [`bite/`](bite/).

## Features

- QR-based bilingual (EN/AR) guest menu with cart, group ordering, and token-based order tracking
- POS dashboard with split payments and cash reconciliation
- Kitchen display system (KDS)
- Menu builder with categories, products, modifier groups, and time-based pricing rules
- Snap-to-Menu AI extraction — upload a menu photo and generate categories + products
- 5-step onboarding wizard with brand color picker and theme preview
- Three selectable menu themes (warm / modern / dark) with auto-optimized WebP image variants
- Phone-based loyalty points (1 point per OMR subtotal)
- Manager/admin modules for catalog, inventory, reports, and settings
- Super-admin control plane for multi-shop management
- Billing with 14-day trial, Free/Pro plans, and Stripe-backed subscription webhooks

## Security and Integrity Defaults

- Single-database tenancy with strict `shop_id` scoping
- Route-level RBAC via `role` middleware
- Signed Stripe webhook verification only (no unsigned fallback)
- Webhook idempotency via `webhook_events(provider,event_id)` unique key
- Non-enumerable order tracking with `orders.tracking_token`
- Fulfillment idempotency with `orders.fulfilled_at`
- PIN and manager override brute-force throttling

## Quickstart

Requires PHP 8.2+, Composer, Node.js 20+, and SQLite (default) or MySQL 8+.

```bash
cd bite
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
composer run dev
```

See [`bite/README.md`](bite/README.md) for SQLite setup, running pieces separately, webhook configuration, and the scheduler.

## Testing

```bash
cd bite
composer test
./vendor/bin/pint --test
npm run build
```

## Documentation

- App guide: [`bite/README.md`](bite/README.md)
- Developer guide: [`bite/DEVELOPMENT.md`](bite/DEVELOPMENT.md)
- Architecture: [`bite/docs/ARCHITECTURE.md`](bite/docs/ARCHITECTURE.md)
- Operations/runbook: [`bite/docs/OPERATIONS.md`](bite/docs/OPERATIONS.md)
- Deployment: [`bite/docs/DEPLOYMENT.md`](bite/docs/DEPLOYMENT.md)

## License

[MIT](LICENSE)
