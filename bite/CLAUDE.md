# CLAUDE.md

Bite-POS — multi-tenant SaaS POS for restaurants/cafes in Oman (Laravel 12 + Livewire 3, full-stack). Solo-dev. **The Operating Contract is the top section and overrides convenience. Everything under `# REFERENCE` is lookup only.** Read the contract every session.

## Quick Nav

| Doing… | Open |
|---|---|
| Anything multi-tenant, money, webhooks, or a migration | **Operating Contract** (below) — non-negotiable |
| Currency formatting | `formatPrice($amount, $shop)` — `app/Helpers/currency.php` (auto-loaded) |
| Guest order pricing / quote / create | `app/Services/GuestOrderService.php` (stateless core; Livewire + JSON API both use it) |
| Public guest JSON API | `routes/api.php` + `app/Http/Controllers/Api/Guest/` |
| Routes + RBAC matrix | `routes/web.php`; middleware aliases in `bootstrap/app.php` |
| Image variants / upload disks | `app/Services/ImageService.php` |
| Stripe webhooks (idempotency) | `app/Http/Controllers/StripeWebhookController.php` |
| Order lifecycle / expiry | `app/Models/Order.php`, `routes/console.php` |
| Localization (`_en`/`_ar`, RTL) | `app/Traits/HasTranslations.php` |
| Deeper system picture | `docs/ARCHITECTURE.md` |
| Deploy / ops | `docs/DEPLOYMENT.md`, `docs/OPERATIONS.md` |
| Plans / design history | `docs/plans/`, `docs/design/` |

Also: `journal.md` (decisions/reflections, not a dev log). **Generic dev process — TDD, planning, security checklist, git conventions — lives in the global `~/.claude/CLAUDE.md` and is not duplicated here.**

---

# OPERATING CONTRACT

If this conflicts with anything under REFERENCE, this wins. This section is only Bite's project-specific non-negotiables; generic process is in the global file.

## 1. Tenant isolation is non-negotiable

There is **no tenancy package** — multi-tenancy is manual via a `shop_id` FK on every tenant-scoped table. Every query on tenant data is scoped to the current shop. `Order`, `Product`, `User` **guard `shop_id` in `$guarded`** — set it only via `forceCreate()` or explicit assignment, never mass-assignment. A missing scope is a cross-tenant data leak; treat it as the project's #1 failure mode.

## 2. Money is OMR — 3 decimals, never 2

Always `formatPrice($amount, $shop)`; never hardcode decimals or a currency symbol. Financial writes run in DB transactions + row locks (`lockForUpdate`). `Payment` is fully `$guarded = ['id']` — every field set explicitly. **Never trust a client-sent total**: the guest cart is re-priced server-side from fresh product/modifier data in `GuestOrderService` (the JSON API and Livewire menu both go through it). Money/legal code gets table-driven tests (clean + warning + error cases).

## 3. Webhook idempotency — never bypass

Stripe processing checks the `webhook_events(provider, event_id)` unique key before mutating, and row-locks the order. Duplicate events are caught on the `webhook_events_provider_event_id_unique` violation. Don't add a webhook path that skips this.

## 4. Memory, docs, and agent output are NOT citations

`MEMORY.md`/auto-memory exists so you don't re-ask settled preferences — it is never proof. Any claim from memory, a doc, a code comment, or a delegated agent that touches tenancy, money, a write path, routing, or schema gets re-confirmed against code (`grep`/`Read`) before it enters a plan. Memory orients; code decides. (This session a memory asserted "CI doesn't exist" — a `ci.yml` had since been added. Re-checking is the job, not friction.) Same rule for "PR merged"/commit-SHA/"tests passed" claims: one mechanical check (`gh pr view`, `git cat-file -t`) before repeating or acting.

## 5. Verify before "done"

`php artisan test` green **and** `./vendor/bin/pint --test` clean before calling anything done. CI **`Tests & Lint`** (`.github/workflows/ci.yml`, repo root one level above `bite/`) is the **only** job and gates every PR. **There is no auto-deploy** — the Cloud Run deploy job was removed 23 Jun 2026 (it had been failing its post-deploy health check since 17 Jun); Forge VPS is the planned target, not yet wired up. Don't claim a fix works off "should"; run it.

## 6. Migrations: new only

Never modify an existing migration file — add a new one. Tests run on **SQLite in-memory**, so MySQL-only behavior won't surface locally (see Bear Traps).

## Verification principles (before risky changes)

Run while writing the spec; report what you checked out loud (terseness never suppresses verification reporting).

1. **Classify every callsite on a shared read/write path** — INBOUND (display only) / OUTBOUND (feeds a write) / BOTH (treat as OUTBOUND).
2. **Verify every concrete claim against code, not docs** — route name, column, method signature, config key. Re-run the grep in the moment.
3. **Enumerate fields from the model/type, not memory.**
4. **Bug fix = failing regression test first** (RED for the right reason → GREEN), then full suite.
5. **One concern per PR** — surface tangential findings as follow-ups, don't bundle.

---

# REFERENCE

Lookup only. Does not override the contract.

## Tech Stack

- **Framework:** Laravel 12 + Livewire 3 (full-stack, no separate frontend). **Livewire 3 syntax**, not 2.
- **Styling:** Vanilla CSS with custom properties (design tokens). **Do NOT use Tailwind** — the Tailwind deps in `package.json` are Breeze scaffolding leftovers.
- **DB:** MySQL 8.0 (prod via Cloud SQL Auth Proxy Unix socket); SQLite in-memory for tests (`phpunit.xml`).
- **Storage:** Google Cloud Storage via `spatie/laravel-google-cloud-storage` in prod, local disk in dev/test.
- **Billing:** Laravel Cashier, `Shop` is the billable model (`AppServiceProvider`). Two webhook endpoints: `/webhooks/stripe` (payments) and `/webhooks/stripe/subscription` (Cashier lifecycle). Thawani Pay planned for the Oman market, not yet integrated.
- **Auth:** Laravel Breeze (modified) + custom Staff PIN login (`PinLogin`).
- **Other:** Vite build; Sentry error tracking; Gemini API for Snap-to-Menu (`MenuExtractionService`).
- **Hosting:** Planned **Laravel Forge VPS** (not yet set up — see `docs/DEPLOYMENT-FORGE.md`, `deploy/`). Cloud Run was retired 23 Jun 2026 (deploy job removed from CI). The `Dockerfile` + `docker/` single-container (Nginx + PHP-FPM + supervisord) remain as a dual-use runtime, not currently deployed anywhere.

## Commands

```bash
composer dev                              # server + queue + logs + vite concurrently
php artisan test                          # all tests; --filter=ClassName or =methodName for one
composer test                             # clears config then runs all tests
php artisan migrate:fresh --seed          # reset with demo data
./vendor/bin/pint                         # fix style; --test to check only
npm run dev | npm run build               # vite
composer setup                            # first-time setup
```

Seeder creds (`DatabaseSeeder`): admin `admin@bite.com`/`password`, super admin `super@bite.com`/`password`, demo shop slug `demo`. Factories: `ShopFactory` (auto `status='active'`), `UserFactory`, `ProductFactory`, `CategoryFactory`.

## Architecture

**Livewire-first.** All interactive UI is Livewire components in `app/Livewire/`. **No traditional controllers for app features** — controllers exist only for webhooks, document rendering (`Invoice`/`Receipt`), reports export, impersonation, the guest JSON API (`Api\Guest\`), and `HealthController`. Deeper picture: `docs/ARCHITECTURE.md`.

**Roles:** `admin` (owner/manager), `manager`, `server`, `kitchen`; super admin is the `is_super_admin` bool on `User`. Middleware aliases (`bootstrap/app.php`): `role:admin` (`EnsureUserHasRole`, comma-separated), `super_admin`, `subscribed` (`CheckSubscription`), `shop.active` (`EnsureShopActive`), `plan` (`EnsurePlanFeature`). Route RBAC matrix lives in `routes/web.php`.

**Order lifecycle:** `unpaid → paid → preparing → ready → completed` (+ `cancelled` for expired unpaid). Orders from `PosDashboard` or `GuestMenu`. `Order::cancelExpired()` runs every minute (scheduler). Splits via `parent_order_id`/`split_group_id`. Guest orders carry a UUID `tracking_token` (auto on create) — it's the bearer secret for `/track/{token}` and the JSON status endpoint. `Order::customerStatus()` maps the internal status to a customer-safe label (never exposes "unpaid").

**Billing:** `Shop` uses `Billable`; new shops get a 14-day trial (`config/billing.php`). Free plan (1 staff / 20 products) vs Pro (unlimited, 20 OMR/mo); `BillingService::canAccess()` enforces limits. Plan/limit/trial numbers live in `config/billing.php` — read them there, don't hardcode.

**Services:** `ShopProvisioningService` (shop+owner in a txn, 14-day trial), `BillingService`, `ImageService` (stream-based thumb/card/full WebP variants), `MenuExtractionService` (Snap-to-Menu via Gemini), `PrintNodeService`, `LoyaltyService` (1 point/OMR subtotal, phone-based), `WhatsAppService`.

## Localization (en/ar)

Customer-facing text uses `_en`/`_ar` column pairs. `HasTranslations::translated($field)` resolves by `App::getLocale()` with English fallback. Localized models: `Product` (`name`, `description`), `Category` (`name`), `ModifierGroup`/`ModifierOption` (`name`), `OrderItem` (`product_name_snapshot`), `OrderItemModifier` (`modifier_option_name_snapshot`). **Guest views:** `$model->translated('name')`. **Admin views:** `$model->name_en` directly (admin UI is English). **Order snapshots:** both `_en` and `_ar` frozen at order time. **New translatable field:** always add both columns + use the trait. Fonts: Rubik (display/body), IBM Plex Sans Arabic (RTL), JetBrains Mono.

## Conventions

- Blade views kebab-case (`pos-dashboard.blade.php`).
- Design tokens via CSS custom properties; per-shop theming via the `branding` JSON on `Shop`.
- `SlugGenerator` appends a random suffix on collision.
- Many small files > few large (200–400 lines typical, 800 max); organize by feature.

## Bear Traps

- **`ImageService::processUpload($storedPath, ?$disk = null)` — pass the disk explicitly.** The default falls back to `config('filesystems.default')`, which can differ from the disk the file was actually written to (a seeder/`ProductManager` writing to `public` while the read defaults elsewhere). This caused the "33 products, photo decode failed" bug — the file was on one disk, read from another. Stay disk-agnostic: `Storage::get/put` **streams** only, never `file_put_contents` (GCS has no local FS).
- **`APP_URL` must include the dev port (`:8000`) or every product photo 404s.** Public-disk image URLs come from `Storage::disk('public')->url()`, which is built from `APP_URL` (`config/filesystems.php`). `.env.example` historically shipped a bare `http://localhost`, but local dev serves on `:8000` (`composer dev` / `artisan serve`) — so the menu HTML loads on `:8000` while every `<img>` points at port 80 (nothing listening) and 404s. Files, symlink, and DB all look fine; only the emitted URL is wrong. Same symptom as the disk-mismatch bug, different cause — check `APP_URL` first. After changing it, `php artisan config:clear`.
- **Route RBAC guards the GET only; Livewire `update` runs on bare `web` middleware.** Only the **SuperAdmin** components self-guard (boot/mount). The admin *operational* components (`ProductManager`, `PricingRules`, `ReportsDashboard`, `CashReconciliation`, `AuditLogs`) do **not** — so role/subscription/active-shop enforcement on a sensitive Livewire **action** must live in the component, not just the route. (Audit-flagged; see the security note at session start.)
- **Scheduler runs as supervisord `[program:scheduler]` (`schedule:work`) — one per container.** Tasks (`Order::cancelExpired()` everyMinute, `GroupCart::cleanExpired()` hourly) fire from **every** running instance. Safe at one instance; if you scale app instances >1, add a single-runner lock or keep tasks idempotent before enabling autoscaling.
- **Production code paths use `config()`, never `env()`** — `config:cache` makes `env()` return null. The one sanctioned exception is `trustProxies` in `bootstrap/app.php` (runs before config is loaded).
- **Tests use SQLite in-memory + sync queue + array cache/sessions** (`phpunit.xml`). MySQL-specific behavior (locking semantics, some constraint/JSON behavior) may differ from prod — don't assume a green test proves MySQL behavior.

## CI/CD

`.github/workflows/ci.yml` (repo root, one dir above `bite/`) defines the **`CI`** workflow: a single **`Tests & Lint`** job (~1m50s) that gates every PR and also runs on `main` pushes (plus a Docker-build validation step on PRs). **The job builds frontend assets (`setup-node` → `npm ci` → `npm run build`) before `php artisan test`** — `public/build` is gitignored and many feature tests render `@vite` views that throw "Vite manifest not found" without a built manifest. Don't re-commit `public/build` to "fix" a missing manifest; the build step is the source of truth (added 23 Jun 2026, commit `c63c058`). **There is no deploy job** — the **Build & Deploy to Cloud Run** job (WIF + Artifact Registry + post-deploy health check / rollback) was removed 23 Jun 2026 after Cloud Run was abandoned for a planned Forge VPS. **Merging to `main` no longer deploys.** Deploy will be wired up for Forge (`deploy/forge-deploy.sh` does `npm ci && npm run build` natively on the VPS). The GCP Actions secrets (`GCP_*`, `WIF_*`, `CLOUD_RUN_SERVICE`) are now unused but left in place.

## Don'ts

- **Don't use Tailwind** — use the CSS custom-property design system.
- **Don't create API controllers** for features that should be Livewire components (the guest JSON API in `Api\Guest\` is the deliberate exception — it wraps `GuestOrderService` for an external QR-menu consumer).
- **Don't skip tenant scoping** (Contract §1).
- **Don't modify existing migrations** (Contract §6).
- **Don't bypass webhook idempotency** (Contract §3).
