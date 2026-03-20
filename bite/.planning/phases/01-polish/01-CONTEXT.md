# Phase 1: Polish - Context

**Gathered:** 2026-03-20
**Status:** Ready for planning

<domain>
## Phase Boundary

Fix the guest menu so it renders product photos correctly, looks warm and artisan, and behaves well on mobile. Covers image URL bug fix, visual overhaul to 2-column compact grid, branding cascade from 3 shop colors to all CSS tokens, Playfair Display for category headers, and two regression tests. No new features — pure polish of existing guest menu.

</domain>

<decisions>
## Implementation Decisions

### Card layout & interaction
- **D-01:** 2-column compact grid on ALL screen sizes (not responsive 1→2 columns)
- **D-02:** Compact cards show: photo + name + price. Description hidden by default
- **D-03:** Tap card to expand in-place — card grows to reveal description below price, other cards shift down
- **D-04:** Accordion behavior — only one card expanded at a time. Tapping another card auto-collapses the current one
- **D-05:** Small `+` icon on each compact card for quick-add to cart (no need to expand first)
- **D-06:** Expanded view shows description text only — add-to-cart is via the `+` icon on the compact card
- **D-07:** Cards have consistent min-height regardless of image presence (GMVIZ-10)

### Product images
- **D-08:** Fix image URL bug — prepend `/storage/` prefix to `image_url` when rendering (currently missing)
- **D-09:** Use `object-contain` (not `object-cover`) to preserve cut-out product shapes without cropping
- **D-10:** Animated gradient shimmer (left-to-right) as skeleton placeholder while images load
- **D-11:** Broken/missing images show a subtle fork-and-knife or plate icon centered in the image area (keeps card height consistent)
- **D-12:** Product names in sentence case (not forced uppercase)

### Category headers
- **D-13:** Playfair Display serif font for category headers (self-hosted in public/fonts/, consistent with project constraint)
- **D-14:** Empty categories (zero visible products) hidden from guest menu
- **D-15:** Category header styling — Claude's discretion on size, weight, spacing, decorative elements

### Branding cascade
- **D-16:** All CSS tokens (--canvas, --panel, --panel-muted, --line, --ink-soft) derived from 3 brand colors (paper, ink, accent)
- **D-17:** Derivation algorithm — Claude's discretion, but must produce warm cohesive tones from Sourdough's palette (paper: #F5F0E8, accent: #C4975A, ink: #2C2520)
- **D-18:** Background gradient — subtle vertical gradient from paper to a slightly deeper warm tone (barely noticeable depth, not flat)
- **D-19:** Accent color (gold) used ONLY on buttons and interactive elements (+ icon, cart bar, CTAs). Cards stay neutral paper/panel tones
- **D-20:** Card surfaces and borders reflect shop's warm palette end-to-end (BRND-03)

### Testing
- **D-21:** Feature test: product with image_url renders `<img>` with `/storage/` prefix
- **D-22:** Feature test: shop with custom branding renders derived CSS variables

### Claude's Discretion
- Exact branding derivation algorithm (tint/shade vs mix — just make it warm and cohesive)
- Category header visual treatment (size, weight, decorative line, spacing)
- Shimmer animation timing and gradient colors
- Placeholder icon choice (fork-and-knife, plate, or similar food icon)
- Exact card border-radius, shadow depth, spacing between cards
- Expand/collapse animation (smooth height transition)

</decisions>

<specifics>
## Specific Ideas

- Sourdough's palette: paper #F5F0E8 (warm cream), accent #C4975A (warm gold), ink #2C2520 (dark brown)
- "Talabat UX parity" — the 2-column compact grid is modeled after food delivery app browse UX
- Photos are product cut-outs on transparent/white backgrounds — `object-contain` prevents awkward cropping
- 33 menu items means the grid must be scannable — compact cards with quick-add `+` keeps browsing fast

</specifics>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Guest menu component
- `app/Livewire/GuestMenu.php` — Current component (1000+ lines), mount logic, cart state, modifier modal
- `resources/views/livewire/guest-menu.blade.php` — Current Blade template with product rendering, layout classes

### CSS & branding
- `resources/css/app.css` — Design token definitions (`:root` custom properties), component classes, font-face declarations
- `resources/views/layouts/app.blade.php` — Branding injection (hex→RGB conversion, inline style tag overriding `:root` vars)

### Models
- `app/Models/Shop.php` — Branding JSON column structure (accent, paper, ink)
- `app/Models/Product.php` — image_url field, HasTranslations trait usage

### Fonts
- `public/fonts/` — Currently has Rubik only. Playfair Display needs to be added here

### Existing tests
- `tests/Feature/Livewire/GuestMenuTest.php` — Existing Livewire feature tests (test patterns to follow)
- `tests/Feature/Livewire/GuestMenuModifierTest.php` — Modifier test patterns

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- **Branding injection in layout:** `layouts/app.blade.php` already converts hex→RGB and injects as CSS vars — extend this for derived tokens
- **HasTranslations trait:** Products and categories use `translated('name')` for locale-aware display — already working
- **Surface-card class:** `resources/css/app.css` defines `.surface-card` with backdrop blur and border — base for compact cards
- **Modifier modal:** Already a functioning bottom-sheet pattern in GuestMenu for product modifiers — proven UI pattern

### Established Patterns
- **CSS custom properties:** All theming via `--token-name` in `:root`, overridden by inline `<style>` from branding
- **Livewire 3 component pattern:** Wire events for cart operations, Alpine.js for client-side interactivity
- **Self-hosted fonts:** `@font-face` declarations in app.css, files in `public/fonts/`
- **No Tailwind in production CSS:** Project uses vanilla CSS with custom properties (Tailwind classes in Blade are from Breeze scaffolding)

### Integration Points
- **Image URL fix:** Product model's `image_url` → Blade template `<img src="">` — prefix `/storage/` in the accessor or template
- **Branding derivation:** `layouts/app.blade.php` inline `<style>` → add derived token computation (PHP or JS)
- **Playfair Display:** Add font files to `public/fonts/`, add `@font-face` to `app.css`, apply to category `<h3>` elements

</code_context>

<deferred>
## Deferred Ideas

- Image optimization pipeline (resize, WebP) — v2 requirement GMVIZ-V2-01
- Per-shop photo style (cover vs contain) configurable — v2 requirement GMVIZ-V2-02
- Per-shop name casing configurable — v2 requirement GMVIZ-V2-03
- Shop logo in guest menu header — v2 requirement BRND-V2-02
- Bottom-sheet product detail view — considered but expand-in-place chosen for simplicity

</deferred>

---

*Phase: 01-polish*
*Context gathered: 2026-03-20*
