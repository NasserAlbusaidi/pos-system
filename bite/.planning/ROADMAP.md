# Roadmap: Bite-POS

## Milestones

- ✅ **v1.0 Sourdough Demo** — Phases 1-2 (shipped 2026-03-21)
- ✅ **v1.1 Customization & Polish** — Phases 3-5 (shipped 2026-03-21)
- ✅ **v1.2 Production Readiness** — Phases 6-9 (shipped 2026-03-28, SEC-04 deferred)
- 🔵 **v1.3 Brand Consistency** — Phases 10-14 (active, started 2026-04-28)

## Phases

<details>
<summary>✅ v1.0 Sourdough Demo (Phases 1-2) — SHIPPED 2026-03-21</summary>

- [x] Phase 1: Polish (3/3 plans) — completed 2026-03-20
- [x] Phase 2: Demo (2/2 plans) — completed 2026-03-21

See: `.planning/milestones/v1.0-ROADMAP.md` for full details

</details>

<details>
<summary>✅ v1.1 Customization & Polish (Phases 3-5) — SHIPPED 2026-03-21</summary>

- [x] Phase 3: Item Availability (2/2 plans) — completed 2026-03-21
- [x] Phase 4: Image Optimization (2/2 plans) — completed 2026-03-21
- [x] Phase 5: Menu Themes (2/2 plans) — completed 2026-03-21

See: `.planning/milestones/v1.1-ROADMAP.md` for full details

</details>

<details>
<summary>✅ v1.2 Production Readiness (Phases 6-9) — SHIPPED 2026-03-28</summary>

**Milestone Goal:** Deploy Bite-POS to Google Cloud Run with production-grade infrastructure, hardening, and security — ready for Sourdough Oman as first live customer.

- [x] **Phase 6: Containerization & Cloud Services** - App runs in Docker with Cloud SQL and Cloud Storage, secrets managed via environment (completed 2026-03-27)
- [x] **Phase 7: Hardening & Security** - Production-grade health checks, rate limiting, observability, tenant isolation audit, and input validation (completed 2026-03-27)
- [x] **Phase 8: CI/CD & Data Safety** - Automated test-build-deploy pipeline and database backup strategy (completed 2026-03-27)
- [x] **Phase 9: Production Activation & Gap Closure** - Activate production services (backups, logging, Sentry), close audit gaps, minor code fixes (completed 2026-03-27, SEC-04 deferred)

</details>

### 🔵 v1.3 Brand Consistency

**Milestone Goal:** Unify the visual layer of Bite-POS so that every screen — guest menu, POS, KDS, admin, super-admin, emails, and printed documents — pulls from one design token system, one logo component, and one theme cascade. Lift the co-founder consistency audit from 4/10 to 9/10 across cross-screen consistency, logo, typography, spacing, and one-off styling.

- [ ] **Phase 10: Design Tokens** - Establish typography + spacing tokens as CSS custom properties on top of existing color tokens; document the system as the single source of truth
- [ ] **Phase 11: Logo & Brand Identity** - One canonical `<x-application-logo>` Blade component used everywhere; favicons + PWA icons regenerated from the same SVG
- [ ] **Phase 12: Theme Cascade** - `data-theme` (warm/modern/dark) extended from guest menu to admin, POS, and super-admin layouts with documented overrides in app.css
- [ ] **Phase 13: Email & Print Token Injection** - Welcome email, receipts, invoices, and shift reports inject shop branding via a single shared partial — no more duplicated `<style>` blocks or hardcoded hex colors
- [ ] **Phase 14: Component Reuse & Style Cleanup** - Inline styles cut from 153 → <30, reusable component classes (`.surface-card`, `.field`, `.tag`, `.loading-spinner`) in use, icon standard documented and applied

## Phase Details

### Phase 6: Containerization & Cloud Services
**Goal**: App runs as a containerized service connected to managed cloud database and storage, with no hardcoded secrets
**Depends on**: Phase 5 (v1.1 complete)
**Requirements**: DEPLOY-01, DEPLOY-02, DEPLOY-03, SEC-02
**Plans:** 1/2 plans executed
Plans:
- [x] 06-01-PLAN.md — Production container with Nginx + PHP-FPM, Cloud SQL MySQL config, secrets enforcement
- [x] 06-02-PLAN.md — GCS storage migration for product images, ImageService refactor, Livewire temp uploads
**Success Criteria** (what must be TRUE):
  1. App boots in a Docker container with PHP-FPM + Nginx serving requests and returning correct responses
  2. App reads and writes data to Cloud SQL MySQL 8.0 via Cloud SQL Auth Proxy without connection errors
  3. Uploading a product image stores it in Google Cloud Storage and the guest menu displays it from a GCS URL
  4. Container runs with zero secrets in the image — all credentials come from Cloud Run environment variables or Secret Manager
  5. Running `docker build` produces a working image with Composer deps installed and Vite assets compiled

### Phase 7: Hardening & Security
**Goal**: App is production-hardened with health monitoring, rate limiting, structured logging, and verified security boundaries
**Depends on**: Phase 6
**Requirements**: HARD-01, HARD-02, HARD-03, HARD-04, SEC-01, SEC-03
**Plans:** 3/3 plans complete
Plans:
- [x] 07-01-PLAN.md — Health check endpoint, startup env validation, rate limiting (HARD-01, HARD-02, HARD-03)
- [x] 07-02-PLAN.md — Structured JSON logging, PII masking, slow request detection (HARD-04)
- [x] 07-03-PLAN.md — Tenant isolation audit with regression tests, input validation sweep (SEC-01, SEC-03)
**Success Criteria** (what must be TRUE):
  1. GET /health returns status of DB connectivity, storage access, GD extension, and queue — Cloud Run uses this for liveness checks
  2. App refuses to boot and logs a clear error message when any required environment variable is missing
  3. Repeated login attempts, rapid guest orders, and webhook floods are rate-limited and return 429 responses
  4. Unhandled exceptions appear in Sentry within seconds and structured JSON logs are queryable in Cloud Logging
  5. Every database query on tenant-scoped tables is confirmed scoped to shop_id — no cross-tenant data leakage possible

### Phase 8: CI/CD & Data Safety
**Goal**: Code pushed to main is automatically tested, built, and deployed to Cloud Run, with database backups ensuring data recovery
**Depends on**: Phase 7
**Requirements**: DEPLOY-04, SEC-04
**Plans:** 1/2 plans executed
Plans:
- [x] 08-01-PLAN.md — CI/CD pipeline: rewrite ci.yml with test gate + Docker build + Artifact Registry push + Cloud Run deploy + health check + auto-rollback (DEPLOY-04)
- [ ] 08-02-PLAN.md — GCP WIF setup, GitHub secrets configuration, pipeline verification, Cloud SQL backup enablement (DEPLOY-04, SEC-04)
**Success Criteria** (what must be TRUE):
  1. Pushing to main triggers a GitHub Actions workflow that runs the test suite, builds the Docker image, pushes to Artifact Registry, and deploys to Cloud Run
  2. A failed test suite prevents deployment — the pipeline stops and reports the failure
  3. Cloud SQL automated daily backups are enabled with a defined retention period and point-in-time recovery is available

### Phase 9: Production Activation & Gap Closure
**Goal**: All production services fully activated (database backups, structured logging, error tracking) and audit gaps from v1.2-MILESTONE-AUDIT.md closed
**Depends on**: Phase 8
**Requirements**: SEC-04
**Gap Closure**: Closes gaps from v1.2-MILESTONE-AUDIT.md
**Plans:** 3 plans (2 complete, 1 gap closure)
Plans:
- [x] 09-01-PLAN.md — AppServiceProvider DB_SOCKET validation fix, test coverage, stale ci.yml removal (HARD-02 gap, cleanup)
- [x] 09-02-PLAN.md — Cloud SQL backup enablement, GCS bucket setup, Cloud Run env var activation, Sentry DSN (SEC-04, HARD-04 activation)
- [ ] ~~09-03-PLAN.md — Cloud SQL backup enablement gap closure (SEC-04)~~ — deferred to backlog; GCP Free Trial restriction still active 2026-03-28
**Success Criteria** (what must be TRUE):
  1. Cloud SQL automated daily backups enabled with 7-day retention and point-in-time recovery (SEC-04)
  2. LOG_CHANNEL=stackdriver set on Cloud Run — structured JSON logs with PII masking queryable in Cloud Logging (HARD-04 activation)
  3. Real SENTRY_LARAVEL_DSN configured on Cloud Run — unhandled exceptions appear in Sentry dashboard
  4. FILESYSTEM_DISK=gcs and LIVEWIRE_TEMP_DISK=gcs confirmed set on Cloud Run
  5. AppServiceProvider validates DB_SOCKET when DB_HOST is not explicitly set (HARD-02 gap fix)
  6. Stale bite/.github/workflows/ci.yml removed

### Phase 10: Design Tokens
**Goal**: Typography and spacing scales exist as CSS custom properties alongside the existing color tokens, are documented as the single source of truth, and are applied across guest menu, admin, POS, and super-admin views — replacing scattered hardcoded values
**Depends on**: Phase 9 (v1.2 complete; production stable)
**Requirements**: DS-01, DS-02, DS-03
**Plans:** 0/3 plans executed
Plans:
- [ ] 10-01-PLAN.md — Define typography scale (`--font-size-xs` … `--font-size-2xl`, `--font-weight-regular/medium/semibold/bold`, line-heights for Rubik Latin and IBM Plex Sans Arabic) in `resources/css/app.css` directly under the existing `:root` color block; verify scale renders correctly in both EN and AR locales (DS-01)
- [ ] 10-02-PLAN.md — Define spacing scale (`--space-1` … `--space-12` on a consistent ratio, e.g. 4px base) in the same `:root` block; sweep blade views and existing component classes to replace hardcoded `px`/`rem` margins/padding (DS-02)
- [ ] 10-03-PLAN.md — Document the token system: location (`resources/css/app.css`), naming convention, ratio rules, and contributor guidance written to `docs/design-system.md`; audit Tailwind utility class usage across blade views and produce a sweep target list for phases 11–14 (DS-03)
**Success Criteria** (what must be TRUE):
  1. `resources/css/app.css` `:root` block defines `--font-size-xs` through `--font-size-2xl`, four `--font-weight-*` tokens, and `--space-1` through `--space-12` — verifiable via `grep` against the file
  2. Guest menu, POS dashboard, admin shop settings, and super admin shop list each render typography sized exclusively from `--font-size-*` tokens (computed-style spot-check shows no orphan `font-size: 13px;` style attributes in the rendered HTML)
  3. The same four screens render spacing exclusively from `--space-*` tokens — `style="padding: 12px;"` and similar literal values reduced to zero on those four screens
  4. `docs/design-system.md` exists, lists every token with its value, documents the 1.25 typography ratio and 4px spacing base, and shows a "do/don't" example for adding a new component
  5. Both `App::setLocale('en')` and `App::setLocale('ar')` render guest menu typography without overflow or clipping at 360px viewport — Rubik and IBM Plex Sans Arabic both honor the scale
**Plans:** TBD

### Phase 11: Logo & Brand Identity
**Goal**: A single `<x-application-logo>` Blade component is the only way the Bite-POS logo appears anywhere in the product — guest menu header, admin sidebar, login page, welcome landing, super admin, welcome email, and PWA install — and favicons/manifest icons all regenerate from the same canonical SVG
**Depends on**: Phase 10 (uses spacing tokens for size variants)
**Requirements**: DS-04, DS-05, DS-06
**Plans:** 0/3 plans executed
Plans:
- [ ] 11-01-PLAN.md — Audit every existing logo placement; refactor `x-application-logo` to accept `size` prop (sm/md/lg) and `variant` prop (mark/wordmark) with sizes derived from `--space-*` tokens; replace inline `<img>` and hardcoded `<span class="logo-badge">B</span>` with the component in all admin/POS/super-admin layouts and login/welcome pages (DS-04)
- [ ] 11-02-PLAN.md — Replace hardcoded "B" badge in `resources/views/emails/welcome.blade.php` with inlined `x-application-logo` SVG (email-safe, no external assets); render the component at sm/md/lg in a preview blade and visually verify in Mailtrap or local mail log (DS-05)
- [ ] 11-03-PLAN.md — Regenerate favicon (`favicon.ico`, `favicon-32x32.png`, `apple-touch-icon.png`) and PWA manifest icons (192x192, 512x512, maskable variants) from the canonical logo SVG; commit assets to `public/` and update `manifest.webmanifest` (DS-06)
**Success Criteria** (what must be TRUE):
  1. `grep -r "logo-badge\|>B<\|application-logo.png" resources/views` returns zero results outside `x-application-logo.blade.php` itself
  2. Guest menu header, admin sidebar, login page, welcome landing, super admin shell, and welcome email all render via `<x-application-logo>` — verifiable by visiting each route and inspecting DOM
  3. `<x-application-logo size="sm" />`, `size="md"`, and `size="lg"` produce three distinct heights driven by `--space-*` tokens (no hardcoded `width="40"`)
  4. Favicon visible in Chrome/Safari tab, "Add to Home Screen" on iOS shows the correct logo, and Lighthouse PWA audit reports no manifest icon errors
  5. Welcome email opened in Gmail/Apple Mail shows the new logo (not "B") at all three breakpoints (mobile, desktop, dark mode)
**Plans:** TBD
**UI hint**: yes

### Phase 12: Theme Cascade
**Goal**: The `data-theme` attribute (warm/modern/dark) — currently honored only by the guest menu — cascades into admin, POS, and super-admin layouts; selecting a theme in shop settings restyles every screen consistently (radius, font weight, accent application, spacing density), and theme override blocks live in one place in `app.css` with no parallel theme system
**Depends on**: Phase 10 (consumes typography + spacing tokens), Phase 11 (logo component reacts to theme)
**Requirements**: DS-07, DS-08, DS-09
**Plans:** 0/3 plans executed
Plans:
- [ ] 12-01-PLAN.md — Add `data-theme="{{ $shop->branding['theme'] ?? 'warm' }}"` to `<html>` in `layouts/app.blade.php`, `layouts/admin.blade.php`, and the super-admin layout; verify theme attribute renders in DOM for an authenticated session (DS-07)
- [ ] 12-02-PLAN.md — Define admin/POS theme override blocks in `app.css` (`[data-theme="warm"] .surface-card { … }`, `[data-theme="modern"] …`, `[data-theme="dark"] …`) covering card radius, font weight on headings, accent color application, and spacing density; visually verify each theme on POS dashboard, KDS, admin shop settings (DS-08)
- [ ] 12-03-PLAN.md — Consolidate guest-menu theme blocks and new admin/POS theme blocks into a single `/* Themes */` section in `app.css` with inline comments documenting which selectors each theme overrides; update `docs/design-system.md` with a "How themes work" section (DS-09)
**Success Criteria** (what must be TRUE):
  1. Inspecting `<html>` on `/menu/sourdough`, `/dashboard`, `/pos/sourdough`, `/super-admin/shops` all show a `data-theme` attribute matching the shop's selected theme
  2. Switching theme from "warm" to "modern" in shop settings (`/admin/shop-settings`) and reloading the admin dashboard visibly changes card border-radius, heading font-weight, and accent color across admin AND POS — not just the guest menu
  3. The "dark" theme renders admin sidebar, POS dashboard, and super-admin tables in a coherent dark palette (no orphan white panels) — verified by visual inspection at three routes
  4. `app.css` has exactly one section labeled `/* Themes */` containing all `[data-theme="…"]` selectors; no theme overrides exist in blade `<style>` blocks (verifiable via `grep -r "data-theme" resources/views`)
  5. `docs/design-system.md` "Themes" section lists which CSS variables and selectors each theme overrides, with a screenshot or code sample per theme
**Plans:** TBD
**UI hint**: yes

### Phase 13: Email & Print Token Injection
**Goal**: Welcome emails, receipts, invoices, and shift reports all inject shop branding (paper/ink/crema + typography + spacing tokens) via a single shared Blade partial — no template hardcodes a hex color, and changing a shop's branding visibly updates every emitted document on the next render
**Depends on**: Phase 10 (tokens exist), Phase 12 (branding partial pattern established)
**Requirements**: DS-10, DS-11, DS-12
**Plans:** 0/3 plans executed
Plans:
- [ ] 13-01-PLAN.md — Create `resources/views/components/branding-styles.blade.php` partial that emits a `<style>` block injecting `--paper`, `--ink`, `--crema`, `--font-size-*`, `--space-*` from `$shop->branding`; refactor `layouts/app.blade.php`, `layouts/admin.blade.php`, super-admin layout, `emails/layout.blade.php`, and `print/layout.blade.php` to use this single partial (DS-12)
- [ ] 13-02-PLAN.md — Refactor `emails/welcome.blade.php` to consume the branding partial — strip 25+ hardcoded hex colors and replace with `var(--paper)`/`var(--ink)`/`var(--crema)`; verify welcome email render with three different shop brandings shows three different palettes (DS-10)
- [ ] 13-03-PLAN.md — Refactor receipt, invoice, and shift report print templates (`print/receipt.blade.php`, `print/invoice.blade.php`, `print/shift-report.blade.php`) to consume the branding partial with print-safe fallbacks (`var(--ink, #000)`, `var(--paper, #fff)`) for shops with no branding configured (DS-11)
**Success Criteria** (what must be TRUE):
  1. `grep -rE "#[0-9a-fA-F]{6}" resources/views/emails resources/views/print` returns ≤ 3 results, all of them documented print-safe fallbacks (e.g. `var(--ink, #000)`)
  2. Sending the welcome email for a shop with `branding.paper = "#FFE4D6"` renders the email body with that paper color (verifiable in Mailtrap or local mail log); changing branding to `branding.paper = "#1A1A1A"` and re-sending produces a dark-themed email
  3. Printing a receipt for a "warm" branded shop and a "modern" branded shop produces two visibly distinct ink/paper combinations — both readable on thermal paper
  4. Each layout (`app`, `admin`, `super-admin`, `email`, `print`) includes the branding via `@include('components.branding-styles')` (or `<x-branding-styles>`) — only one partial defines the branding `<style>` block (verifiable via `grep -r "<style>" resources/views/layouts resources/views/emails resources/views/print` showing only the partial itself)
  5. A shift report rendered for a shop with no branding configured falls back to print-safe black-on-white (no broken `var(--ink)` reference) — verifiable by clearing `branding` JSON and reprinting
**Plans:** TBD

### Phase 14: Component Reuse & Style Cleanup
**Goal**: The codebase reads like one product, not five. Inline `style="…"` attributes drop from 153 to under 30, in-blade `<style>` blocks are consolidated into `app.css` component classes, the major Livewire components share a small library of reusable classes (`.surface-card`, `.field`, `.tag`, `.loading-spinner`), and inline SVG icons follow a documented standard
**Depends on**: Phase 10, Phase 11, Phase 12, Phase 13 (all token + theme + partial work in place)
**Requirements**: DS-13, DS-14, DS-15, DS-16
**Plans:** 0/4 plans executed
Plans:
- [ ] 14-01-PLAN.md — Inline-style sweep: catalogue all 153 `style="…"` occurrences (`grep -rn 'style="' resources/views`), categorise as "dynamic-required" vs "extractable-to-class", refactor extractable ones into component classes, target ≤30 remaining (DS-13)
- [ ] 14-02-PLAN.md — In-blade `<style>` block consolidation: identify every blade view with a `<style>` block (excluding email/print branding partials), extract rules to `resources/css/app.css` under named component classes, leave only print-related and dynamic branding-injection blocks in views (DS-14)
- [ ] 14-03-PLAN.md — Reusable component class library: define `.surface-card`, `.field`, `.tag`, `.loading-spinner` (and any other recurring patterns surfaced in 14-01) in `app.css`; refactor `shop-settings.blade.php`, `pos-dashboard.blade.php`, and `shift-report.blade.php` to use these classes; sweep remaining Tailwind utility class clusters and replace with vanilla equivalents (DS-15)
- [ ] 14-04-PLAN.md — Icon standardization: document the standard (outline-only, 1.5px stroke, 24x24 viewBox) in `docs/design-system.md`; extract the most-reused inline SVGs (cart, search, close, check, chevron, user, settings) as Blade components in `resources/views/components/icons/`; refactor at least three Livewire views to consume them (DS-16)
**Success Criteria** (what must be TRUE):
  1. `grep -rEn 'style="[^"]+"' resources/views | wc -l` returns ≤ 30 (down from 153) — and a brief comment in code or docs justifies each remaining instance as a dynamic computed value
  2. `grep -rln "<style>" resources/views/livewire resources/views/dashboard resources/views/admin` returns only files that use the branding-injection partial (legal, offline, and ad-hoc per-page `<style>` blocks have been moved to `app.css` component classes)
  3. `.surface-card`, `.field`, `.tag`, and `.loading-spinner` each appear in at least three different blade views (verifiable via `grep -rl "surface-card" resources/views` etc.) — replacing copy-pasted inline styling
  4. `resources/views/components/icons/` exists and contains at least seven canonical inline-SVG components; at least three Livewire views (e.g. `pos-dashboard`, `guest-menu`, `kds`) consume them via `<x-icons.cart />` style tags
  5. `docs/design-system.md` "Icons" section documents the outline/1.5px-stroke/24x24 standard with one positive and one negative example, and a CI/grep sanity check confirms no `<svg>` in views uses `fill="currentColor"` without conforming to the standard (or has been audited and exempted)
  6. Tailwind utility-class sweep: count of Tailwind utility classes (e.g. `class="… p-4 …"` `flex` `gap-`) in `resources/views` reduced by ≥80% from the v1.3 baseline captured in 10-03; remaining usages are documented or scheduled for v1.4 cleanup
**Plans:** TBD
**UI hint**: yes

## Progress

| Phase | Milestone | Plans Complete | Status | Completed |
|-------|-----------|----------------|--------|-----------|
| 1. Polish | v1.0 | 3/3 | Complete | 2026-03-20 |
| 2. Demo | v1.0 | 2/2 | Complete | 2026-03-21 |
| 3. Item Availability | v1.1 | 2/2 | Complete | 2026-03-21 |
| 4. Image Optimization | v1.1 | 2/2 | Complete | 2026-03-21 |
| 5. Menu Themes | v1.1 | 2/2 | Complete | 2026-03-21 |
| 6. Containerization & Cloud Services | v1.2 | 2/2 | Complete | 2026-03-27 |
| 7. Hardening & Security | v1.2 | 3/3 | Complete | 2026-03-27 |
| 8. CI/CD & Data Safety | v1.2 | 2/2 | Complete | 2026-03-27 |
| 9. Production Activation & Gap Closure | v1.2 | 2/3 | Complete (SEC-04 deferred) | 2026-03-28 |
| 10. Design Tokens | v1.3 | 0/3 | Pending | — |
| 11. Logo & Brand Identity | v1.3 | 0/3 | Pending | — |
| 12. Theme Cascade | v1.3 | 0/3 | Pending | — |
| 13. Email & Print Token Injection | v1.3 | 0/3 | Pending | — |
| 14. Component Reuse & Style Cleanup | v1.3 | 0/4 | Pending | — |
