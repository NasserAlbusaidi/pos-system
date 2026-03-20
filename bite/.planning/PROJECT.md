# Bite-POS

## What This Is

A multi-tenant SaaS POS system for restaurants and cafes in Oman. Features a POS terminal, kitchen display system (KDS), QR-based guest digital menu with ordering, reporting dashboard, menu builder, billing/subscriptions, and super admin panel. Built with Laravel 12 + Livewire 3, vanilla CSS with design tokens, MySQL 8.0.

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

### Active

- [ ] Guest menu visual overhaul (photo-forward cards, warm branding cascade, Playfair Display typography)
- [ ] Fix image URL bug in guest menu (missing /storage/ prefix)
- [ ] 2-column compact mobile grid for guest menu
- [ ] Derive all CSS tokens from 3 brand colors (branding cascade)
- [ ] Image loading states (skeleton shimmer) and error handling (onerror fallback)
- [ ] Empty category hiding in guest menu
- [ ] Pre-build Sourdough Oman demo shop with real menu data (33 items, bilingual, photos)
- [ ] Regression tests for image rendering and branding cascade

### Out of Scope

- Menu templates/themes (selectable layouts) — branding tokens sufficient for now
- Per-shop custom fonts — adding Playfair Display system-wide first
- Image optimization pipeline (WebP, resize) — tracked in TODOS.md for later
- Snap-to-Menu photo extraction — photos available from Sourdough PDF
- Item availability indicators ("sold out") — future production feature
- Thawani Pay integration — separate initiative, not needed for demo

## Context

**First client prospect:** Sourdough Oman, a family-run artisan bakery in Azaiba, Muscat (18th November Street). They have a professionally designed PDF menu with warm parchment aesthetic, gold typography, and cut-out product photography. They have a confirmed operational bottleneck (1 cashier, long weekend lines, 45-minute seating limit during busy hours). Already on Talabat for delivery.

**Pitch strategy:** "Pre-Built Sourdough" — create their shop in Bite-POS before visiting, using their PDF menu data. Walk in showing them their own bakery already running. Key pitch: "Talabat for your dine-in customers, except you keep 100% and the line moves faster."

**Design target:** Sourdough's PDF menu (parchment #F5F0E8, gold #C4975A, dark #2C2520). The guest menu must close the aesthetic gap — not replicate the PDF, but feel like it belongs in their world.

**Eng review findings:** Photo upload, image display, card grid, and branding tokens all already exist. Scope is "polish + configure," not "build from scratch." Critical bug found: guest menu image URLs missing `/storage/` prefix.

**Design review decisions:** object-contain for photos (no cropping cut-outs), sentence case for product names, skeleton shimmer for image loading, Playfair Display for category headers, 2-column compact grid on all screen sizes.

## Constraints

- **CSS**: Vanilla CSS with design tokens. Do NOT use Tailwind.
- **Currency**: OMR (3 decimal places). Always use `formatPrice()` helper.
- **Tenancy**: Manual `shop_id` scoping. No tenancy package.
- **Fonts**: Self-hosted in `public/fonts/`. Rubik (body), IBM Plex Sans Arabic (RTL), JetBrains Mono (mono), Playfair Display (category headers — to be added).
- **Billing**: Thawani Pay for production (not Stripe). Stripe only for subscription billing.
- **Database**: MySQL 8.0 (prod), SQLite in-memory (tests).
- **Migrations**: New migrations only — never modify existing ones.

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| object-contain for product photos | Sourdough's cut-out photos must not be cropped | — Pending |
| Sentence case for product names | Uppercase fights artisan bakery brand identity | — Pending |
| Playfair Display for category headers | Warm serif pairs with Rubik body, matches food/artisan aesthetic | — Pending |
| 2-column compact grid on all screens | 33-item menu needs quick scanning, matches Talabat UX | — Pending |
| Derive all CSS tokens from 3 brand colors | Only paper/ink/crema were overridden; canvas/panel/line stayed cold | — Pending |
| Pre-build Sourdough's shop before visiting | Tests the full flow AND creates the most compelling pitch demo | — Pending |

---
*Last updated: 2026-03-20 after initialization*
