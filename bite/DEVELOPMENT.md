# Bite Development Guide

This guide is the technical source of truth for local development and implementation patterns.

## Stack
- Laravel 12
- Livewire 3 + Volt
- Blade + Tailwind CSS
- Vite
- SQLite/MySQL

## Local Workflow
1. Install dependencies: `composer install && npm install`
2. Configure env: `.env` from `.env.example`
3. Run migrations: `php artisan migrate --seed`
4. Run dev stack: `composer run dev`

## Multi-Tenancy Model (Single DB)
Bite uses single-database tenancy. Tenant isolation is enforced by:
- `shop_id` foreign keys on tenant-owned records
- Explicit query scoping in components/controllers
- Route access control by authenticated user role

Historical tenant/domain migrations may exist for backward compatibility, but runtime tenancy packages are not used.

## RBAC Model
Roles:
- `server`
- `kitchen`
- `manager`
- `admin`
- `super_admin` (separate privilege flag)

Route segmentation:
- `server|manager|admin`: POS and invoices
- `kitchen|manager|admin`: KDS
- `manager|admin`: admin modules (products/menu/inventory/reports/settings)
- `super_admin`: platform admin routes

## Payment and Webhooks
- Stripe webhook route remains `/webhooks/stripe`
- CSRF is explicitly exempted only for this webhook path
- Webhook signatures are required (`Stripe-Signature` + `STRIPE_WEBHOOK_SECRET`)
- Webhook events are idempotent via `webhook_events`
- Payment updates lock the target order row to prevent race duplicates

## Order Lifecycle
Typical status progression:
`unpaid -> paid -> preparing -> ready -> completed`

Additional rules:
- Guest tracking uses `orders.tracking_token` (UUID)
- Fulfillment writes `orders.fulfilled_at` and is one-time
- Repeated completion attempts must not re-consume inventory or reprint receipts

## Service Boundaries
- `InventoryService`: stock consumption for completed orders
- `LoyaltyService`: loyalty rewards on successful payment transitions
- `PrintNodeService`: kitchen/receipt print dispatch

## Frontend/PWA
Service worker is restricted to static asset/offline caching. Authenticated dynamic pages and API responses must not be cached.

## Testing Strategy
Primary suites:
- Feature tests in `tests/Feature`
- Livewire component behavior tests in `tests/Feature/Livewire`

Critical test categories:
- webhook signature + idempotency
- RBAC route matrix
- tenant isolation (cross-shop payload rejection)
- token tracking privacy
- PIN/override throttling
- fulfillment idempotency

## Useful Commands
```bash
php artisan route:list -v
php artisan test
./vendor/bin/pint
npm run build
```
