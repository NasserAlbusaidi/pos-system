# Feature Research

**Domain:** Restaurant QR digital menu — customization & operations layer (v1.1)
**Researched:** 2026-03-21
**Confidence:** HIGH (existing codebase examined directly; library capabilities verified via official docs + Packagist)

---

## Context: What Already Exists

Before classifying features, note what is already built so complexity estimates are scoped to the delta only:

| Existing Capability | Relevance to v1.1 |
|---------------------|-------------------|
| `is_available` boolean on Product model | Toggle exists in DB + POS panel; guest menu already filters it out — work needed is: expose toggle in admin product manager, surface sold-out state visually on guest menu |
| Branding cascade (3 hex → 8 derived CSS tokens, injected inline per shop) | Themes will extend this mechanism — not replace it |
| `branding` JSON column on Shop | Already stores `paper`, `ink`, `accent` + misc config; extend for `theme` and `custom_font` keys |
| `public/fonts/` self-hosting pattern | Custom fonts follow the same pattern: download woff2, register `@font-face` |
| `ProductManager` Livewire component + `WithFileUploads` | Image upload already works; pipeline adds a processing step before `store()` |
| Guest menu already filters `is_available = true` in all queries | Sold-out items are already hidden from guests; feature is about surfacing them (greyed-out + badge) rather than building a new gate |

---

## Feature Landscape

### Table Stakes (Users Expect These)

Features that restaurant owners in competitive QR-menu markets assume exist. Missing them makes the product feel half-built compared to Zomato/Talabat-style menus.

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Sold-out badge on guest menu | Every QR menu system (Square, Lightspeed, Talabat) surfaces unavailability visually to guests rather than silently hiding items | LOW | `is_available` already in DB + filters; change is: show greyed card + "Sold Out" badge instead of hiding; add-to-cart blocked on unavailable items |
| Quick 86/restore from admin menu builder | Staff toggle product availability without touching POS — POS already has `toggle86()`, this exposes same action in ProductManager | LOW | One Livewire method + button per product row; audited already |
| Image display that doesn't look broken or blurry | Guests judge food quality by photo quality; compressed originals on mobile are a trust signal | MEDIUM | Intervention Image v3 + `toWebp(80)` + `scale(800)` on upload; originals preserved separately |
| Reasonable page load on slow Oman mobile networks | Rural/suburban restaurants; large JPEGs kill first paint | MEDIUM | WebP at 80% quality is 40-70% smaller than JPEG; directly tied to image pipeline |

### Differentiators (Competitive Advantage)

Features that set Bite-POS apart from generic QR menu tools. These match the core value of "a beautiful menu that reflects your brand."

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| Selectable menu themes (3-4 presets) | Restaurants have distinct identities — a bakery (Sourdough) needs warmth + serif, a sushi spot needs minimal + clean; no competitor in the Oman mid-market offers this | MEDIUM | Each theme = a named set of CSS variable overrides + font pairing + layout modifier class; stored as `branding.theme = 'warm'` on Shop; injected at render time alongside existing branding cascade |
| Per-shop custom Google Font (any font, self-hosted) | Font choice signals brand personality stronger than color; self-hosting avoids GDPR/performance issues of external Google CDN requests | HIGH | Most complex feature: admin types font name → PHP fetches CSS2 API with modern UA → parses woff2 URLs → downloads to `public/fonts/shops/{shop_id}/` → registers `@font-face` → stored in `branding.custom_font` |
| Brand color overrides on top of selected theme | Themes provide sensible palette defaults but owners still want their exact logo colors applied | LOW | Already built — existing cascade runs on top of whatever base theme sets; no new mechanism needed, just wire theme selection before cascade runs |

### Anti-Features (Commonly Requested, Often Problematic)

| Feature | Why Requested | Why Problematic | Alternative |
|---------|---------------|-----------------|-------------|
| Real-time sold-out sync from inventory | Owners want automatic 86 when stock hits zero | Inventory tracking is not in scope for v1.1; auto-sync requires inventory module + event listeners; builds hidden complexity that breaks without full inventory | Provide the manual toggle with friction-free UX; if inventory is added later, it sets `is_available` the same way |
| Live font preview (pick from full Google Fonts catalogue in admin UI) | Delightful admin UX | Requires loading Google Fonts API catalogue (1000+ fonts), real-time preview iframe, debounced fetch — significant frontend complexity for a feature used once per shop | Text input + "Apply Font" button with a preview reload; immediate, understandable |
| Per-category themes | Granular visual control | Inconsistent menu appearance breaks brand coherence; 10x the theme state surface area | One theme per shop; brand color cascade already provides per-shop uniqueness |
| Theme marketplace / user-uploaded themes | Maximum customization | CSS injection attack surface; requires sandboxing + validation; maintenance burden; not needed for current market | 3-4 curated built-in themes cover 90% of restaurant aesthetics |
| Animated sold-out transitions / confetti | Polish | Over-engineered for operational tool; POS staff need fast feedback, not animation | Instant toggle with toast notification is correct |
| AVIF conversion | Better compression than WebP | Browser support incomplete in some Oman-market Android devices; adds processing complexity | WebP at q80 is sufficient; revisit when AVIF support is universal |

---

## Feature Dependencies

```
[Theme Selection]
    └──writes──> branding.theme (Shop JSON)
                     └──read by──> layout render (app.blade.php inline <style>)
                                       └──requires──> [Branding Cascade] (already built)

[Custom Font]
    └──requires──> [Font Download Pipeline]
                       └──requires──> Google Fonts CSS2 API (external, public)
                       └──writes──> public/fonts/shops/{shop_id}/*.woff2
                       └──writes──> branding.custom_font (Shop JSON)
                                       └──read by──> layout render (inline @font-face + CSS var override)

[Image Optimization Pipeline]
    └──wraps──> [Existing ProductManager upload] (already built)
    └──requires──> intervention/image-laravel (new dependency)
    └──writes──> storage/app/public/products/ (WebP variant)
    └──preserves──> original file (keep as backup)

[Sold-Out Toggle in Admin]
    └──reuses──> is_available column (already in DB)
    └──reuses──> toggle86() method from PosDashboard (copy pattern)
    └──adds to──> ProductManager Livewire component

[Sold-Out Badge on Guest Menu]
    └──requires──> is_available on loaded products
    └──changes──> guest-menu.blade.php (show card greyed + badge instead of hiding)
    └──note──> GuestMenu queries already load is_available — no DB change needed
```

### Dependency Notes

- **Theme Selection requires Branding Cascade:** The cascade already runs and injects tokens at render time. Theme selection adds a pre-step that sets base CSS variables before cascade overrides them. The cascade should remain the final layer — brand colors override theme defaults.
- **Custom Font requires Font Download Pipeline:** The admin UI input is trivial; the non-trivial part is the server-side download. These must be built together — the admin can't save a font name without a successful download.
- **Image Optimization wraps existing upload:** ProductManager already calls `$this->image->store('products', 'public')`. The pipeline intercepts this, processes the file, stores WebP, and updates the path. No migration needed — `image_url` column already exists.
- **Sold-Out Badge changes guest menu display logic:** Currently `GuestMenu.php` filters `is_available = true` in all product queries. For the badge feature, we change the guest menu to load ALL visible products and show unavailable ones as greyed-out with a badge. This is a query-scope change + view change — no schema change.

---

## MVP Definition

### Launch With (v1.1)

All four features are v1.1 scope. Recommended order of implementation:

- [x] **Sold-Out Toggle in Admin (ProductManager)** — lowest complexity, highest operational value; unblocks Sourdough demo scenario where a baked item runs out mid-day
- [x] **Sold-Out Badge on Guest Menu** — same data, just display change; pairs naturally with toggle; completes the feature
- [x] **Image Optimization Pipeline** — medium complexity; do before theme/font work so Sourdough's real photos (when provided) load fast; Intervention Image is a clean, well-maintained dependency
- [x] **Menu Themes (3-4 presets)** — medium complexity; well-understood CSS mechanism; completes visual identity story
- [x] **Custom Google Font (self-hosted)** — highest complexity; builds on theme infrastructure; do last so font can be layered on top of theme

### Add After Validation (v1.x)

- [ ] **Sold-out indicator on POS dashboard product grid** — POS already has 86 panel; this would be per-card indicator for active ordering; add when operators request it
- [ ] **Automatic 86 from low inventory** — add when inventory module is built; just needs `is_available` flag set by inventory service
- [ ] **Additional theme variants** — add 2-3 more themes based on client feedback; CSS-only additions

### Future Consideration (v2+)

- [ ] **Custom CSS override field** — power-user escape hatch; requires sanitisation + scope; defer until there is clear demand
- [ ] **Theme preview in onboarding wizard** — valuable for conversion; deferred because onboarding wizard already complete and working
- [ ] **Bulk image re-optimization** — retroactive WebP conversion of existing product images; deferred because Sourdough photos are placeholders anyway

---

## Feature Prioritization Matrix

| Feature | User Value | Implementation Cost | Priority |
|---------|------------|---------------------|----------|
| Sold-out toggle (admin) | HIGH — operational control, daily use | LOW — 1 Livewire method + UI | P1 |
| Sold-out badge (guest) | HIGH — guest clarity, prevents frustration | LOW — query scope + CSS state | P1 |
| Image optimization pipeline | HIGH — every product photo benefits | MEDIUM — new dependency, pipeline logic | P1 |
| Menu themes (3-4 presets) | HIGH — brand identity, demo differentiation | MEDIUM — CSS + branding.theme key + admin picker | P1 |
| Custom Google Font | MEDIUM-HIGH — strong brand signal | HIGH — server-side download, woff2 parsing, file management | P2 |

**Priority key:**
- P1: Must have for v1.1 launch
- P2: Should have, delivers after core features land

---

## Competitor Feature Analysis

| Feature | Square for Restaurants | Lightspeed K-Series | Talabat (Oman) | Our Approach |
|---------|----------------------|--------------------|-----------------|-|
| Sold-out toggle | POS-side only; no admin web UI | Toggle in availability column; greyed on online menu | Vendor-managed via Talabat Partner Hub | POS already has it; add to admin product manager + show on guest menu |
| Guest sold-out display | "Unavailable" label, greyed card, add-to-cart blocked | "Currently unavailable" orange label, greyed | Item hidden from menu entirely | Show greyed card + "Sold Out" pill badge + disabled add-to-cart; better UX than hiding |
| Theme / branding | Color + logo only | Color + logo only | None (Talabat house style) | 3-4 full layout+palette+font presets + brand color override on top |
| Custom font | Not available | Not available | Not applicable | Admin types Google Font name → system self-hosts woff2; unique in this market |
| Image optimization | Handled by Square CDN automatically | Handled by Lightspeed CDN | CDN-handled | Build explicit pipeline: resize to 800px wide, WebP at q80, preserve original |

---

## Implementation Notes by Feature

### Sold-Out Toggle

- `PosDashboard.toggle86()` is the reference implementation — copy the pattern into `ProductManager`
- Guest menu currently hides unavailable items via `->where('is_available', true)` in two computed properties and two order validation checks. Change the display query to load all visible products; keep the order validation filter (cannot add unavailable item to cart)
- Visual treatment: greyed card (opacity 0.5 or muted CSS state), "Sold Out" pill badge (alert color), add-to-cart button hidden or disabled with cursor-not-allowed
- No migration needed — `is_available` column with default `true` already exists on `products` table

### Image Optimization Pipeline

- Add `intervention/image-laravel` (v1.5.8, requires PHP ^8.1, Laravel 8-13) — compatible with current stack (Laravel 12, PHP 8.2)
- Pipeline in `ProductManager.save()`: after `$this->image` passes validation, read → scale to max 800px wide (preserve aspect ratio) → encode as WebP at quality 80 → store WebP file → save path
- Preserve original by storing it at `products/originals/{filename}` before processing
- Update validation rule from `max:1024` (1MB) to `max:5120` (5MB) since we will compress on our side
- GD driver is sufficient (available on standard PHP installs); Imagick is optional but not required

### Menu Themes

- Theme = named bundle of CSS variable overrides + font-family assignments. Store as string key in `branding.theme` on Shop (e.g. `'warm'`, `'minimal'`, `'bold'`, `'dark'`)
- Theme CSS variables override the compiled `app.css` defaults but run before the per-shop branding cascade, so cascade colors always win
- Inject in `<style>` block in layout, before the cascade block that already exists
- Proposed theme personalities:
  - **Warm** (default, current look) — parchment paper, serif display font (Playfair Display), radial gradient bg, earthy accent
  - **Minimal** — pure white paper, sans-serif display, no gradient, thin borders, generous whitespace
  - **Bold** — dark paper (near-black), inverted high-contrast, large type, strong accent color pop
  - **Fresh** — cool white with green accent default, rounded cards, modern sans

### Custom Google Font

- Admin inputs a Google Font name (e.g. "Lato", "Nunito Sans") — must match exact font family name on Google Fonts
- Server-side flow: validate name → call Google Fonts CSS2 API (`https://fonts.googleapis.com/css2?family={name}&display=swap`) with a modern Chrome UA header to get woff2 responses → parse CSS response to extract woff2 URLs → download each woff2 file → store at `public/fonts/shops/{shop_id}/{variant}.woff2` → write `@font-face` declaration into branding JSON
- UA trick: Google Fonts CSS2 returns woff2 URLs when the requesting User-Agent is a modern browser UA string; PHP's default UA returns TTF fallbacks. Set `User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36` in the HTTP request
- Alternative / fallback: google-webfonts-helper API (`https://gwfh.mranftl.com/api/fonts/{slug}`) returns JSON with direct woff2 download URLs — more reliable than parsing CSS, but the third-party service could go offline. Use as primary with CSS2 as fallback
- Store resolved font name + face declarations in `branding.custom_font = ['family' => 'Lato', 'faces' => [...]]`
- Inject `@font-face` from branding in layout `<style>` block, then override `--font-body` and/or `--font-display` CSS variables to apply it
- Scope: latin + latin-ext subsets only (Arabic uses IBM Plex Sans Arabic regardless)
- Error handling: invalid font name → validation error with suggestion to check spelling; download failure → toast + do not save

---

## Sources

- Codebase examination: `app/Livewire/PosDashboard.php`, `app/Livewire/GuestMenu.php`, `app/Livewire/ProductManager.php`, `app/Livewire/ShopSettings.php`, `resources/views/layouts/app.blade.php`, `resources/css/app.css`, `app/Models/Product.php`, `app/Models/Shop.php`
- [Intervention Image v3 — image output methods](https://image.intervention.io/v3/basics/image-output) (official docs, verified `toWebp()` API)
- [intervention/image-laravel on Packagist](https://packagist.org/packages/intervention/image-laravel) — v1.5.8, PHP ^8.1, Laravel 8-13 confirmed (2026-03-20 release)
- [Google Fonts CSS API v2 documentation](https://developers.google.com/fonts/docs/css2) — `fonts.googleapis.com/css2` endpoint, family parameter syntax
- [google-webfonts-helper](https://gwfh.mranftl.com/) — third-party API returning structured font metadata with woff2 download URLs
- [Lightspeed K-Series item availability](https://k-series-support.lightspeedhq.com/hc/en-us/articles/10724827631259-Setting-up-and-using-Item-availability) — competitor reference for sold-out UX pattern
- [Square for Restaurants — mark items unavailable](https://squareup.com/help/gb/en/article/6425-managing-items-with-square-for-restaurants) — competitor reference
- [NCR Aloha Cloud item availability](https://docs.ncrvoyix.com/restaurant/aloha-cloud/implementing/menu_management/working_with_item_availability) — competitor reference

---

*Feature research for: Bite-POS v1.1 — Menu Themes, Custom Fonts, Image Optimization, Sold-Out Toggle*
*Researched: 2026-03-21*
