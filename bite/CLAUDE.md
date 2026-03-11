# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Bite-POS is a multi-tenant SaaS POS system for restaurants and cafes in Oman. Features include a POS terminal, kitchen display system (KDS), QR-based guest digital menu with ordering, reporting dashboard, menu builder, billing/subscriptions, and super admin panel.

**Currency:** OMR (Omani Rial) — 3 decimal places, not 2. Use `formatPrice($amount, $shop)` (auto-loaded global helper in `app/Helpers/currency.php`).

## Tech Stack

- **Framework:** Laravel 12 + Livewire 3 (full-stack, no separate frontend)
- **Styling:** Vanilla CSS with CSS custom properties (design tokens). **Do NOT use Tailwind** — the Tailwind deps in `package.json` are from Breeze scaffolding only.
- **Database:** MySQL 8.0 (production), SQLite in-memory (tests — see `phpunit.xml`)
- **Billing:** Laravel Cashier with `Shop` as the billable model (configured in `AppServiceProvider`). Stripe webhooks handled in `StripeWebhookController` and `StripeSubscriptionWebhookController`.
- **Auth:** Laravel Breeze (modified) + custom Staff PIN login (`PinLogin.php`)
- **Build:** Vite with `laravel-vite-plugin`

## Commands

```bash
# Full dev environment (server + queue + logs + vite concurrently)
composer dev

# Individual services
php artisan serve          # Laravel dev server
npm run dev                # Vite dev server

# Tests
php artisan test                          # All tests
php artisan test --filter=ClassName       # Single test class
php artisan test --filter=testMethodName  # Single test method

# Database
php artisan migrate
php artisan migrate:fresh --seed          # Reset with demo data

# Lint
./vendor/bin/pint

# Build frontend
npm run build

# First-time setup
composer setup
```

## Architecture

### Tenancy Model

There is **no tenancy package**. Multi-tenancy is manual: every tenant-scoped table has a `shop_id` FK. Always scope queries to the current shop. Models that guard `shop_id` (via `$guarded`): `Order`, `Product`, `User`. The `shop_id` must be set via `forceCreate()` or explicit assignment to prevent tenant isolation bypass.

### Livewire-First Architecture

All interactive UI is Livewire components in `app/Livewire/`. There are **no traditional controllers** for app features — controllers only exist for webhooks (`StripeWebhookController`, `StripeSubscriptionWebhookController`), document rendering (`InvoiceController`, `ReceiptController`), reports export (`ReportsExportController`), and impersonation (`ImpersonationController`).

### Role System & Middleware

Roles: `admin` (owner/manager), `manager`, `server`, `kitchen`. Super admin is a boolean flag `is_super_admin` on User.

- `role:admin` — middleware alias for `EnsureUserHasRole`, accepts comma-separated roles
- `super_admin` — middleware alias for `EnsureUserIsSuperAdmin`
- `CheckSubscription` — verifies shop has active subscription or trial; redirects to billing page

Routes are grouped by role access in `routes/web.php`. Public routes (guest menu, PIN login, webhooks) require no auth.

### Order Lifecycle

`unpaid → paid → preparing → ready → completed` (also `cancelled` for expired unpaid orders)

- Orders created from POS (`PosDashboard`) or guest menu (`GuestMenu`)
- `Order::cancelExpired()` cleans up unpaid orders past `expires_at`
- Split orders via `parent_order_id` / `split_group_id`
- Guest orders tracked via UUID `tracking_token`
- `Payment` model uses full `$guarded = ['id']` — all fields set explicitly for financial safety

### Billing

`Shop` uses the `Billable` trait (Laravel Cashier). `BillingService` handles subscription status checks. `BillingSettings` Livewire component manages the UI. `CheckSubscription` middleware enforces active subscription. New shops get a 14-day trial set in `ShopProvisioningService`.

### Services

| Service | Purpose |
|---------|---------|
| `ShopProvisioningService` | Creates shop + owner user in a transaction |
| `BillingService` | Subscription status checks |
| `PrintNodeService` | Kitchen ticket and receipt printing |
| `LoyaltyService` | Customer loyalty program |
| `WhatsAppService` | WhatsApp messaging integration |

### Key Public Routes

- `/menu/{shop:slug}` — Guest digital menu (no auth)
- `/track/{trackingToken}` — Guest order tracking (UUID)
- `/pos/pin/{shop:slug}` — Staff PIN login
- `/webhooks/stripe` and `/webhooks/stripe/subscription` — Stripe webhooks

## Testing

Tests use **SQLite in-memory** (not MySQL). This means some MySQL-specific features may behave differently in tests.

Factories available: `ShopFactory`, `UserFactory`, `ProductFactory`, `CategoryFactory`. `ShopFactory` auto-sets `status = 'active'` in `afterCreating`.

Test seeder credentials (from `DatabaseSeeder`):
- Admin: `admin@bite.com` / `password`
- Super admin: `super@bite.com` / `password`
- Demo shop slug: `demo`

## Conventions

- **Livewire 3 syntax** (not Livewire 2)
- Blade views: kebab-case (`pos-dashboard.blade.php`)
- Currency: always use `formatPrice()` helper, never hardcode decimals or symbols
- Financial operations: use DB transactions + row locking
- New migrations only — never modify existing migration files
- Design tokens: CSS custom properties for theming (colors via `branding` JSON on Shop)
- Fonts: Bricolage Grotesque

## Don'ts

- **Don't use Tailwind.** Use the existing CSS custom property design system.
- **Don't create API controllers** for features that should be Livewire components.
- **Don't skip tenant scoping.** Every query on tenant data must be scoped to `shop_id`.
- **Don't modify existing migrations.** Create new ones for schema changes.
