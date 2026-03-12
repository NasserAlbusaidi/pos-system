# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Bite-POS is a multi-tenant SaaS POS system for restaurants and cafes in Oman. Features include a POS terminal, kitchen display system (KDS), QR-based guest digital menu with ordering, reporting dashboard, menu builder, billing/subscriptions, and super admin panel.

**Currency:** OMR (Omani Rial) — 3 decimal places, not 2. Use `formatPrice($amount, $shop)` (auto-loaded global helper in `app/Helpers/currency.php`).

## Tech Stack

- **Framework:** Laravel 12 + Livewire 3 (full-stack, no separate frontend)
- **Styling:** Vanilla CSS with CSS custom properties (design tokens). **Do NOT use Tailwind** — the Tailwind deps in `package.json` are from Breeze scaffolding only.
- **Database:** MySQL 8.0 (production), SQLite in-memory (tests — see `phpunit.xml`)
- **Billing:** Laravel Cashier with `Shop` as the billable model (configured in `AppServiceProvider`). Stripe webhooks handled in `StripeWebhookController` (payments) and `StripeSubscriptionWebhookController` (subscription lifecycle).
- **Auth:** Laravel Breeze (modified) + custom Staff PIN login (`PinLogin.php`)
- **Build:** Vite with `laravel-vite-plugin`
- **Error Tracking:** Sentry (`sentry/sentry-laravel`)

## Commands

```bash
# Full dev environment (server + queue + logs + vite concurrently)
composer dev

# Individual services
php artisan serve          # Laravel dev server
npm run dev                # Vite dev server

# Tests
composer test                             # Clears config + runs all tests
php artisan test                          # All tests (without config clear)
php artisan test --filter=ClassName       # Single test class
php artisan test --filter=testMethodName  # Single test method

# Database
php artisan migrate
php artisan migrate:fresh --seed          # Reset with demo data

# Lint
./vendor/bin/pint              # Fix style
./vendor/bin/pint --test       # Check style without fixing

# Build frontend
npm run build

# First-time setup
composer setup
```

## Architecture

### Tenancy Model

There is **no tenancy package**. Multi-tenancy is manual: every tenant-scoped table has a `shop_id` FK. Always scope queries to the current shop. Models that guard `shop_id` (via `$guarded`): `Order`, `Product`, `User`. The `shop_id` must be set via `forceCreate()` or explicit assignment to prevent tenant isolation bypass.

### Livewire-First Architecture

All interactive UI is Livewire components in `app/Livewire/`. There are **no traditional controllers** for app features — controllers only exist for webhooks, document rendering (`InvoiceController`, `ReceiptController`), reports export (`ReportsExportController`), and impersonation (`ImpersonationController`).

### Role System & Middleware

Roles: `admin` (owner/manager), `manager`, `server`, `kitchen`. Super admin is a boolean flag `is_super_admin` on User.

Middleware aliases:
- `role:admin` — `EnsureUserHasRole`, accepts comma-separated roles
- `super_admin` — `EnsureUserIsSuperAdmin`
- `CheckSubscription` — verifies active subscription or trial; redirects to billing

RBAC matrix (from `routes/web.php`):
- `server|manager|admin` — POS + invoices
- `kitchen|manager|admin` — KDS transitions
- `manager|admin` — admin operational modules (catalog, reports, settings)
- `super_admin` — platform-level shop/user management

### Order Lifecycle

`unpaid → paid → preparing → ready → completed` (also `cancelled` for expired unpaid orders)

- Orders created from POS (`PosDashboard`) or guest menu (`GuestMenu`)
- `Order::cancelExpired()` runs every minute via scheduler (`routes/console.php`)
- Split orders via `parent_order_id` / `split_group_id`
- Guest orders tracked via UUID `tracking_token` (auto-generated on creation)
- `Payment` model uses full `$guarded = ['id']` — all fields set explicitly for financial safety

### Webhook Idempotency

Stripe webhook processing uses a `webhook_events(provider, event_id)` table with a unique key. The payment webhook handler acquires row locks before mutating orders. This prevents duplicate processing — do not bypass this pattern.

### Billing & Feature Gating

`Shop` uses the `Billable` trait. New shops get a 14-day trial (`config/billing.php`).

| Plan | Staff Limit | Product Limit | Price |
|------|-------------|---------------|-------|
| Free | 1 | 20 | 0 OMR |
| Pro  | Unlimited | Unlimited | 20 OMR/mo |

`BillingService::canAccess()` enforces these limits. Two separate webhook endpoints: `/webhooks/stripe` (payment events) and `/webhooks/stripe/subscription` (Cashier subscription lifecycle).

### Services

| Service | Purpose |
|---------|---------|
| `ShopProvisioningService` | Creates shop + owner user in a DB transaction, sets 14-day trial |
| `BillingService` | Subscription status checks, plan feature gating |
| `PrintNodeService` | Kitchen ticket and receipt printing via PrintNode API |
| `LoyaltyService` | Phone-based points system (1 point per OMR subtotal) |
| `WhatsAppService` | Order notification deep links via WhatsApp |

### Key Public Routes

- `/menu/{shop:slug}` — Guest digital menu (no auth)
- `/track/{trackingToken}` — Guest order tracking (UUID)
- `/pos/pin/{shop:slug}` — Staff PIN login
- `/webhooks/stripe` and `/webhooks/stripe/subscription` — Stripe webhooks

## Testing

Tests use **SQLite in-memory** (not MySQL). This means some MySQL-specific features may behave differently in tests. Tests also use sync queue, array cache, and array sessions (`phpunit.xml`).

Factories available: `ShopFactory`, `UserFactory`, `ProductFactory`, `CategoryFactory`. `ShopFactory` auto-sets `status = 'active'` in `afterCreating`.

Test seeder credentials (from `DatabaseSeeder`):
- Admin: `admin@bite.com` / `password`
- Super admin: `super@bite.com` / `password`
- Demo shop slug: `demo`

## Localization (en/ar)

All customer-facing text fields use `_en`/`_ar` column pairs. The `HasTranslations` trait (`app/Traits/HasTranslations.php`) provides `translated($field)` which resolves by `App::getLocale()` with English fallback.

| Model | Localized Fields |
|-------|-----------------|
| Product | `name_en`/`name_ar`, `description_en`/`description_ar` |
| Category | `name_en`/`name_ar` |
| ModifierGroup | `name_en`/`name_ar` |
| ModifierOption | `name_en`/`name_ar` |
| OrderItem | `product_name_snapshot_en`/`product_name_snapshot_ar` (frozen at order time) |
| OrderItemModifier | `modifier_option_name_snapshot_en`/`modifier_option_name_snapshot_ar` |

- **Guest views**: Use `$model->translated('name')` — resolves by locale
- **Admin views**: Use `$model->name_en` directly (admin UI is English)
- **Order snapshots**: Both `_en` and `_ar` are captured when the order is created
- **New translatable fields**: Always add both `_en` and `_ar` columns, use the `HasTranslations` trait

## Conventions

- **Livewire 3 syntax** (not Livewire 2)
- Blade views: kebab-case (`pos-dashboard.blade.php`)
- Currency: always use `formatPrice()` helper, never hardcode decimals or symbols
- Financial operations: use DB transactions + row locking
- New migrations only — never modify existing migration files
- Design tokens: CSS custom properties for theming (colors via `branding` JSON on Shop)
- Fonts: Rubik variable (display + body, self-hosted in `public/fonts/`), IBM Plex Sans Arabic (RTL), JetBrains Mono (mono)
- Slug generation: `SlugGenerator` helper appends random suffix on collision

## Post-Commit: Update Notion

After every git commit, update the [Bite-POS Notion page](https://www.notion.so/31f499aa39c9815490f0ddad78ba4dfe) (page ID: `31f499aa-39c9-8154-90f0-ddad78ba4dfe`):

1. **Roadmap table** — Update status of any area affected by the commit
2. **Project Stats** — Refresh test count, route count, etc. if they changed
3. **TODO list** — Check off completed items or add new ones that emerged
4. **Co-Owner Review** — Note any new features ready for review

Keep updates concise — just reflect what changed, don't rewrite the whole page.

## Don'ts

- **Don't use Tailwind.** Use the existing CSS custom property design system.
- **Don't create API controllers** for features that should be Livewire components.
- **Don't skip tenant scoping.** Every query on tenant data must be scoped to `shop_id`.
- **Don't modify existing migrations.** Create new ones for schema changes.
- **Don't bypass webhook idempotency.** Always check `webhook_events` before processing Stripe events.
