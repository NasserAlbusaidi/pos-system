# Bite POS

Bite is a Laravel 12 + Livewire 3 point-of-sale system for restaurants and retail shops.

## Canonical Documentation
- Developer guide: `DEVELOPMENT.md`
- Architecture: `docs/ARCHITECTURE.md`
- Operations/runbook: `docs/OPERATIONS.md`

## Core Capabilities
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

## Requirements
- PHP 8.2+
- Composer
- Node.js 20+
- SQLite (default) or MySQL 8+

## Quickstart
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
```

If using SQLite locally:
```bash
touch database/database.sqlite
# set DB_CONNECTION=sqlite in .env
```

## Running Locally
Run the full dev stack (server, queue worker, logs, Vite):
```bash
composer run dev
```

Or run pieces separately:
```bash
php artisan serve
php artisan queue:listen --tries=1 --timeout=0
npm run dev
```

## Testing and Quality
```bash
composer test
./vendor/bin/pint --test
npm run build
```

## Webhook Endpoint
- Path: `/webhooks/stripe`
- Keep this path stable for Stripe integration compatibility.
- Set `STRIPE_WEBHOOK_SECRET` in `.env`.

## Scheduler
Run scheduler in production:
```bash
php artisan schedule:run
```

Scheduled task in this app:
- `routes/console.php`: cancels expired unpaid orders every minute.
