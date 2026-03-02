# CLAUDE.md — Bite-POS

> This file is read automatically by Claude Code at the start of every session.
> It provides the context needed to work effectively on this codebase.

## Project Overview

Bite-POS is a multi-tenant SaaS POS (point of sale) system built for restaurants and cafes in Oman. It includes a POS terminal, kitchen display system (KDS), guest digital menu with QR ordering, reporting dashboard, menu builder, and super admin panel.

**Target market:** Small-to-medium restaurants and cafes in Muscat, Oman.
**Currency:** OMR (Omani Rial) — uses 3 decimal places, not 2.
**Language:** English first, Arabic (RTL) planned for future.

## Tech Stack

- **Framework:** Laravel 11 with Livewire 3 (full-stack, no separate frontend)
- **Views:** Blade templates + Livewire components (NO Inertia, NO Vue, NO React)
- **Styling:** Vanilla CSS with CSS custom properties (design tokens). No Tailwind.
- **Database:** MySQL 8.0
- **Multi-tenancy:** stancl/tenancy package (see `create_tenants_table` and `create_domains_table` migrations)
- **Auth:** Laravel Breeze (modified) + custom Staff PIN login system
- **Payments:** Stripe via webhook handler (NOT Laravel Cashier yet)
- **Printing:** PrintNode API integration for kitchen tickets and receipts
- **PWA:** Service worker with offline page, manifest.json, static asset caching

## Project Structure

```
bite/
├── app/
│   ├── Http/
│   │   └── Middleware/
│   │       ├── EnsureUserHasRole.php
│   │       └── EnsureUserIsSuperAdmin.php
│   ├── Livewire/              # All interactive UI lives here
│   │   ├── Actions/Logout.php
│   │   ├── Admin/
│   │   │   ├── AuditLogs.php
│   │   │   ├── InventoryManager.php
│   │   │   ├── MenuBuilder.php
│   │   │   └── ReportsDashboard.php
│   │   ├── Forms/LoginForm.php
│   │   ├── Guest/
│   │   │   └── OrderTracker.php
│   │   ├── SuperAdmin/
│   │   │   ├── Dashboard.php
│   │   │   └── Shops/
│   │   │       ├── Index.php
│   │   │       └── Manage.php
│   │   ├── Profile/UpdatePinForm.php
│   │   ├── GuestMenu.php       # QR-based guest ordering
│   │   ├── KitchenDisplay.php  # Real-time KDS
│   │   ├── ModifierManager.php
│   │   ├── PinLogin.php        # Staff 4-digit PIN auth
│   │   ├── PosDashboard.php    # Main POS terminal
│   │   ├── ProductManager.php
│   │   ├── ShopDashboard.php   # Owner dashboard
│   │   └── ShopSettings.php
│   ├── Models/
│   │   ├── AuditLog.php
│   │   ├── Category.php
│   │   ├── Ingredient.php
│   │   ├── LoyaltyCustomer.php
│   │   ├── ModifierGroup.php
│   │   ├── ModifierOption.php
│   │   ├── Order.php
│   │   ├── OrderItem.php
│   │   ├── OrderItemModifier.php
│   │   ├── Payment.php
│   │   ├── Product.php
│   │   ├── Shop.php
│   │   ├── Supplier.php
│   │   └── User.php
│   └── Providers/
│       ├── AppServiceProvider.php
│       └── VoltServiceProvider.php
├── database/
│   ├── factories/              # CategoryFactory, ShopFactory, UserFactory, ProductFactory
│   ├── migrations/             # ~30 migrations covering full schema
│   └── seeders/DatabaseSeeder.php
├── routes/
│   ├── web.php                 # Main routes (3.4KB — all app routes)
│   ├── auth.php                # Auth routes (Breeze)
│   └── console.php             # Artisan commands
├── resources/views/            # Blade templates (check here for all UI)
├── public/                     # Assets, manifest.json, service worker
├── config/
├── tests/                      # 40+ feature tests
└── .env                        # Environment config
```

## Key Architecture Decisions

### Multi-tenancy
Uses `stancl/tenancy`. Each shop is a tenant. Routes, data, and branding are scoped per tenant. Always ensure tenant context when querying data.

### Livewire Components Are the App
There are NO traditional controllers for app functionality. All UI interaction flows through Livewire components in `app/Livewire/`. The Blade views in `resources/views/` are paired with these components.

### Role System
- **Super Admin:** Can manage all shops, impersonate shop owners. Middleware: `EnsureUserIsSuperAdmin`
- **Owner/Manager:** Shop-level admin. Can access dashboard, reports, menu builder, settings.
- **Staff:** POS terminal and kitchen display access only. Authenticated via 4-digit PIN (`PinLogin.php`).
- **Manager Override:** PIN-gated actions for sensitive operations with rate limiting.

### Order Lifecycle
`paid → preparing → ready → completed`
- Orders are created from POS or Guest Menu
- KDS shows real-time status transitions
- Split orders and split payments supported with DB transactions + row locking
- Guest orders tracked via UUID tokens

### Design System
- CSS custom properties for theming (colors, spacing, typography)
- Custom fonts: Bricolage Grotesque
- Warm, editorial aesthetic: cream/orange tones, surface cards
- Per-shop branding support
- **Do NOT introduce Tailwind.** Keep using the existing CSS custom property system.

## Coding Conventions

### General
- Follow existing Laravel conventions in the codebase
- Use Livewire 3 syntax (not Livewire 2)
- Keep components focused — one component per feature/page
- Use Blade directives and components for reusable UI pieces
- Always scope queries to the current shop/tenant

### Naming
- Livewire components: PascalCase (e.g., `PosDashboard`, `MenuBuilder`)
- Blade views: kebab-case (e.g., `pos-dashboard.blade.php`, `guest-menu.blade.php`)
- Models: Singular PascalCase (e.g., `Order`, `OrderItem`)
- Migrations: Laravel default timestamp format

### Database
- MySQL 8.0 — not SQLite
- Use proper migrations for all schema changes
- Use DB transactions for financial operations (orders, payments, splits)
- Row locking where concurrent access is possible
- Always include `shop_id` foreign key on tenant-scoped tables

### Security
- Rate limiting on PIN login attempts
- Manager override PIN for sensitive actions
- Stripe webhook signature verification + idempotency
- Tenant isolation — never leak data across shops
- Audit logging for sensitive operations

### Testing
- 40+ existing feature tests in `tests/`
- Tests cover: RBAC, tenant isolation, security, modifier validation, order lifecycle
- Run tests with `php artisan test`
- When adding new features, add or update relevant tests
- Always run the test suite before committing

## When Working on Tasks

1. **Read first.** Before changing a file, read it fully to understand the existing patterns.
2. **Match the style.** Don't introduce new patterns — follow what's already there.
3. **Don't install new CSS frameworks.** Use the existing CSS custom property design system.
4. **Test your changes.** Run `php artisan test` and fix any failures.
5. **One feature per session.** Keep PRs/commits focused on a single task.
6. **Migration safety.** New migrations should be additive. Don't modify existing migration files.
7. **Don't touch .env.** Never commit secrets. If you need a new env variable, document it.

## Current Priorities (Ship-to-Revenue Roadmap)

Working through the task list in order. Current phase is **Phase 1: Polish & Ship**.

Priority tasks (in order):
1. Currency system fix (OMR with 3 decimal places)
2. Product images in guest menu
3. Cart quantity controls in guest menu
4. Modifier names in review modal
5. Order item preview in POS ticket cards
6. Quick-pay buttons on POS cards

See the full roadmap in the Google Doc for the complete 28-task list.

## Common Commands

```bash
# Run the app
php artisan serve

# Run tests
php artisan test

# Run a specific test
php artisan test --filter=TestClassName

# Fresh migrate + seed
php artisan migrate:fresh --seed

# Create a migration
php artisan make:migration create_example_table

# Create a Livewire component
php artisan make:livewire ComponentName

# Clear caches
php artisan optimize:clear

# Queue worker (for jobs like printing, emails)
php artisan queue:work
```

## Don'ts

- **Don't create API controllers** for features that should be Livewire components.
- **Don't hardcode currency.** Always use the shop's currency config.
- **Don't skip tenant scoping.** Every query on tenant data must be scoped to the current shop.
- **Don't modify existing migrations.** Create new ones for schema changes.
- **Don't install Laravel Cashier yet.** That's Phase 5. Stripe is currently webhook-based.
