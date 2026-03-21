# Bite-POS

## What This Is

A multi-tenant SaaS POS system for restaurants and cafes in Oman. Features a POS terminal, kitchen display system (KDS), QR-based guest digital menu with ordering, reporting dashboard, menu builder, billing/subscriptions, and super admin panel. Built with Laravel 12 + Livewire 3, vanilla CSS with design tokens, MySQL 8.0.

Shipped v1.1 with three selectable menu themes (warm/modern/dark), auto-optimized product images (WebP variants), and real-time sold-out indicators — on top of the v1.0 polished guest menu and pitch-ready Sourdough Oman demo.

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
- ✓ Admin sold-out toggle with greyed-out guest menu display and cart recovery — v1.1
- ✓ On-upload image optimization pipeline (resize + WebP, 3 size variants) — v1.1
- ✓ Optimized image serving in guest menu (card-size WebP, lazy loading) — v1.1
- ✓ Backfill artisan command for existing product images — v1.1
- ✓ Three selectable menu themes (warm/modern/dark) with distinct layouts and font pairings — v1.1
- ✓ Theme picker with live preview in shop settings — v1.1
- ✓ Brand color overrides preserved across theme switches — v1.1
- ✓ RTL Arabic compatibility for all three themes — v1.1

### Active

(No active requirements — planning next milestone)

### Out of Scope

- Thawani Pay integration — separate initiative, needed before production launch
- Custom fonts (per-shop Google Fonts) — dropped from v1.1; preset theme pairings sufficient
- Menu templates marketplace (user-uploaded themes) — curated presets sufficient
- Custom CSS editor per shop — security risk (XSS)
- Stock management (auto-decrement) — deferred to v2
- CDN image delivery — deferred to v2

## Context

**Current state:** v1.1 shipped. 188 tests passing (458 assertions). Guest menu supports 3 themes with brand color overrides, auto-optimized WebP product images, and sold-out indicators. Self-hosted font system (Rubik, Playfair Display, Inter, DM Sans, DM Serif Display, IBM Plex Sans Arabic). Image pipeline produces thumb (200px), card (400px), full (800px) variants on upload.

**First client prospect:** Sourdough Oman, a family-run artisan bakery in Azaiba, Muscat (18th November Street). Confirmed operational bottleneck (1 cashier, long weekend lines). Already on Talabat for delivery.

**Pitch strategy:** "Pre-Built Sourdough" — walk in showing them their own bakery already running on Bite-POS. Key pitch: "Talabat for your dine-in customers, except you keep 100% and the line moves faster."

**Demo credentials:** `admin@sourdough.om` / `password` at `/menu/sourdough`. Run `php artisan db:seed --class=SourdoughMenuSeeder` on fresh databases.

**Tech debt:**
- SourdoughMenuSeeder not in DatabaseSeeder (intentional — explicit invocation)
- Product photos use placeholder icons (real photos deferred until Sourdough provides them)
- GD WebP support unverified on production server — need startup health check

## Constraints

- **CSS**: Vanilla CSS with design tokens. Do NOT use Tailwind.
- **Currency**: OMR (3 decimal places). Always use `formatPrice()` helper.
- **Tenancy**: Manual `shop_id` scoping. No tenancy package.
- **Fonts**: Self-hosted in `public/fonts/`. Rubik (body), Inter (modern theme), DM Sans/DM Serif Display (dark theme), IBM Plex Sans Arabic (RTL), JetBrains Mono (mono), Playfair Display (warm theme headers).
- **Billing**: Thawani Pay for production (not Stripe). Stripe only for subscription billing.
- **Database**: MySQL 8.0 (prod), SQLite in-memory (tests).
- **Migrations**: New migrations only — never modify existing ones.
- **Images**: intervention/image v3 only (v4 requires PHP 8.3+). GD driver, not Imagick.

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
| WebP quality 80, JPEG fallback 85 | Balance file size vs food photo quality | ✓ Good — visually indistinguishable from originals |
| intervention/image v3 (not v4) | v4 requires PHP 8.3+; production runs 8.2 | ✓ Good — avoids deployment blocker |
| Theme tokens outside @layer | @layer has lower specificity than inline branding styles | ✓ Good — fixed cascade conflict |
| Warm theme preserves object-contain | Sourdough cut-out photos look wrong with cover crop | ✓ Good — modern/dark use cover appropriately |
| Drop custom fonts from v1.1 | Three preset font pairings provide sufficient variety | ✓ Good — reduced scope, faster ship |

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
*Last updated: 2026-03-21 after v1.1 milestone (Customization & Polish) completed*
