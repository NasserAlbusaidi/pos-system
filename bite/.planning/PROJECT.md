# Bite-POS

## What This Is

A multi-tenant SaaS POS system for restaurants and cafes in Oman. Features a POS terminal, kitchen display system (KDS), QR-based guest digital menu with ordering, reporting dashboard, menu builder, billing/subscriptions, and super admin panel. Built with Laravel 12 + Livewire 3, vanilla CSS with design tokens, MySQL 8.0.

Shipped v1.0 with a polished guest menu and a pitch-ready Sourdough Oman demo — 33 bilingual bakery items, warm branding cascade, and end-to-end order flow verified.

## Core Value

Customers can scan a QR code, browse a beautiful digital menu with photos, and place orders without waiting in line — reducing the cashier bottleneck and giving restaurant owners a modern, bilingual (EN/AR) ordering system they control.

## Requirements

### Validated

- ✓ Multi-tenant shop management with manual `shop_id` scoping — existing
- ✓ POS terminal for order creation and payment processing — existing
- ✓ Kitchen display system (KDS) with order status transitions — existing
- ✓ QR-based guest digital menu with cart and ordering — existing
- ✓ Menu builder with categories, products, modifier groups/options — existing
- ✓ Bilingual support (EN/AR) with RTL layout — existing
- ✓ Role-based access (admin, manager, server, kitchen) — existing
- ✓ Staff PIN login for quick access — existing
- ✓ Snap-to-Menu AI menu extraction from photos — existing
- ✓ Group ordering with shareable links — existing
- ✓ Loyalty points system (phone-based) — existing
- ✓ Pricing rules (time-based discounts) — existing
- ✓ Order splitting — existing
- ✓ Billing with 14-day trial, Free/Pro plans — existing
- ✓ Product image upload and storage — existing
- ✓ Branding system (3 CSS color tokens per shop) — existing
- ✓ 5-step onboarding wizard — existing
- ✓ Audit logging — existing
- ✓ Guest menu visual overhaul (photo-forward cards, warm branding cascade, Playfair Display typography) — v1.0
- ✓ Fix image URL bug in guest menu (missing /storage/ prefix) — v1.0
- ✓ 2-column compact mobile grid for guest menu — v1.0
- ✓ Derive all CSS tokens from 3 brand colors (branding cascade) — v1.0
- ✓ Image loading states (skeleton shimmer) and error handling (onerror fallback) — v1.0
- ✓ Empty category hiding in guest menu — v1.0
- ✓ Pre-build Sourdough Oman demo shop with real menu data (33 items, bilingual) — v1.0
- ✓ Regression tests for image rendering and branding cascade — v1.0

### Active

#### Menu Themes
- [ ] 3-4 selectable themes (layout + color palette + font pairing)
- [ ] Brand color overrides on top of selected theme

#### Custom Fonts
- [ ] Any Google Font — admin types name, system fetches and self-hosts

#### Image Optimization
- [x] On-upload pipeline (resize + WebP conversion, 3 variants) — Validated in Phase 4: image-optimization
- [x] Blade views serve optimized variants (card/thumb) with lazy loading — Validated in Phase 4: image-optimization
- [x] Backfill command for existing product images — Validated in Phase 4: image-optimization

#### Item Availability
- [x] Manual sold-out toggle per product — Validated in Phase 3: item-availability

## Current Milestone: v1.1 Customization & Polish

**Goal:** Give shops visual identity and operational control — selectable themes, custom fonts, optimized images, and item availability toggles.

**Target features:**
- Menu themes (3-4 full themes with brand color overrides)
- Per-shop custom fonts (any Google Font)
- Image optimization on upload (resize + WebP)
- Item availability indicators (manual sold-out toggle)

### Out of Scope

- ~~Menu templates/themes~~ — moved to Active for v1.1
- ~~Per-shop custom fonts~~ — moved to Active for v1.1
- ~~Image optimization pipeline (WebP, resize)~~ — moved to Active for v1.1
- ~~Item availability indicators ("sold out")~~ — moved to Active for v1.1
- Thawani Pay integration — separate initiative, needed before production launch

## Context

**Current state:** v1.0 shipped, v1.1 in progress. Phase 4 complete — product images now auto-optimize on upload (3 WebP variants: 200px thumb, 400px card, 800px full). Guest menu serves card-size WebP with lazy loading; product manager shows thumbnails. `php artisan images:optimize` backfills existing images. 178 tests passing.

**First client prospect:** Sourdough Oman, a family-run artisan bakery in Azaiba, Muscat (18th November Street). Confirmed operational bottleneck (1 cashier, long weekend lines). Already on Talabat for delivery.

**Pitch strategy:** "Pre-Built Sourdough" — walk in showing them their own bakery already running on Bite-POS. Key pitch: "Talabat for your dine-in customers, except you keep 100% and the line moves faster."

**Demo credentials:** `admin@sourdough.om` / `password` at `/menu/sourdough`. Run `php artisan db:seed --class=SourdoughMenuSeeder` on fresh databases.

**Tech debt from v1.0:** SourdoughMenuSeeder not in DatabaseSeeder (intentional — explicit invocation). Product photos use placeholder icons (real photos deferred until Sourdough provides them).

## Constraints

- **CSS**: Vanilla CSS with design tokens. Do NOT use Tailwind.
- **Currency**: OMR (3 decimal places). Always use `formatPrice()` helper.
- **Tenancy**: Manual `shop_id` scoping. No tenancy package.
- **Fonts**: Self-hosted in `public/fonts/`. Rubik (body), IBM Plex Sans Arabic (RTL), JetBrains Mono (mono), Playfair Display (category headers).
- **Billing**: Thawani Pay for production (not Stripe). Stripe only for subscription billing.
- **Database**: MySQL 8.0 (prod), SQLite in-memory (tests).
- **Migrations**: New migrations only — never modify existing ones.

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| object-contain for product photos | Sourdough's cut-out photos must not be cropped | ✓ Good — photos display correctly |
| Sentence case for product names | Uppercase fights artisan bakery brand identity | ✓ Good — natural reading flow |
| Playfair Display for category headers | Warm serif pairs with Rubik body, matches food/artisan aesthetic | ✓ Good — adds warmth without weight |
| 2-column compact grid on all screens | 33-item menu needs quick scanning, matches Talabat UX | ✓ Good — scannable on mobile |
| Derive all CSS tokens from 3 brand colors | Only paper/ink/crema were overridden; canvas/panel/line stayed cold | ✓ Good — PHP linear RGB interpolation produces warm, predictable results |
| Pre-build Sourdough's shop before visiting | Tests the full flow AND creates the most compelling pitch demo | ✓ Good — demo is pitch-ready |
| Seeder instead of manual admin entry | Snap-to-Menu not built, admin panel not automatable by agents | ✓ Good — reproducible, idempotent |

---
## Evolution

This document evolves at phase transitions and milestone boundaries.

**After each phase transition** (via `/gsd:transition`):
1. Requirements invalidated? → Move to Out of Scope with reason
2. Requirements validated? → Move to Validated with phase reference
3. New requirements emerged? → Add to Active
4. Decisions to log? → Add to Key Decisions
5. "What This Is" still accurate? → Update if drifted

**After each milestone** (via `/gsd:complete-milestone`):
1. Full review of all sections
2. Core Value check — still the right priority?
3. Audit Out of Scope — reasons still valid?
4. Update Context with current state

---
*Last updated: 2026-03-21 after Phase 4 (Image Optimization) completed*
