# Phase 5: Menu Themes - Context

**Gathered:** 2026-03-21
**Status:** Ready for planning

<domain>
## Phase Boundary

Shops can choose a visual identity for their guest menu from three distinct preset themes (warm, modern, dark). Each theme defines a layout style, color palette, and font pairing. Brand color overrides apply on top of the selected theme. Theme picker lives in shop settings with visual previews before saving. All themes must render correctly in RTL Arabic layout.

</domain>

<decisions>
## Implementation Decisions

### Theme Identity & Mood
- **D-01:** Three themes: Warm, Modern, Dark
- **D-02:** Warm — Earthy, artisan, inviting. Cream backgrounds, soft shadows, serif headers. Target: bakeries, cafes, brunch spots
- **D-03:** Modern — Clean, minimal, high-contrast. White background, sharp edges, sans-serif everything. Target: juice bars, fast-casual, health food
- **D-04:** Dark — Moody, premium, nightlife-adjacent. Dark background, light text, subtle glows. Target: cocktail bars, fine dining, evening restaurants

### Card Styling
- **D-05:** Noticeably different card character per theme — not just color swaps
- **D-06:** Warm: rounded corners + soft shadow
- **D-07:** Modern: sharp corners + no shadow + thin border
- **D-08:** Dark: no border + subtle glow effect

### Image Treatment
- **D-09:** Subtle variation by theme — not identical across all three
- **D-10:** Warm: soft vignette or rounded image edges (photo-forward, inviting)
- **D-11:** Modern: square-cropped, clean edges (efficient, minimal)
- **D-12:** Dark: slight overlay tint or gradient (moody, premium)

### Layout Structure
- **D-13:** Each theme gets a unique layout structure — maximum variety
- **D-14:** Warm: grid layout (current 2-column pattern, photo-forward)
- **D-15:** Modern: single-column list with horizontal cards (image left, text right)
- **D-16:** Dark: large hero cards (full-width, dramatic image prominence)
- **D-17:** Claude's discretion on exact breakpoints and responsive behavior within each layout

### Image Prominence
- **D-18:** Image area size varies by theme
- **D-19:** Warm: taller image area (photo-forward, good for bakeries)
- **D-20:** Modern: smaller image area (text-forward, efficient scanning)
- **D-21:** Dark: medium image area with overlay gradient

### Spacing Density
- **D-22:** Density varies per theme
- **D-23:** Warm: more breathing room (12-16px gaps, relaxed feel)
- **D-24:** Modern: tight and efficient (8px gaps, compact scanning)
- **D-25:** Dark: medium gaps with more padding inside cards (luxurious feel)

### Category Headers
- **D-26:** Different header patterns per theme — not just color swaps
- **D-27:** Warm: centered with decorative underline
- **D-28:** Modern: left-aligned, minimal (just text + subtle separator)
- **D-29:** Dark: full-width divider with subtle glow

### Font Pairings
- **D-30:** Distinct body + display font combo per theme, all self-hosted
- **D-31:** Warm: Rubik (body) + Playfair Display (headers) — existing fonts, artisan serif feel
- **D-32:** Modern: new sans-serif pairing (e.g., Inter + Inter or similar geometric sans)
- **D-33:** Dark: new pairing (e.g., DM Sans + DM Serif Display or similar contrast pair)
- **D-34:** Claude's discretion on exact font choices for Modern and Dark — must be Google Fonts available, self-hostable, and have Arabic subset or graceful fallback to IBM Plex Sans Arabic

### Brand Color Interaction
- **D-35:** Theme tokens must NOT overwrite --paper/--ink/--crema — branding cascade owns those (locked from prior research)
- **D-36:** Switching themes keeps existing brand colors completely untouched — no reset, no suggestion prompt
- **D-37:** Theme controls structural tokens (layout, card style, spacing, font pairing) and derived tokens (canvas, panel, line, ink-soft) that aren't the base 3
- **D-38:** If a shop has no custom brand colors set, the theme's recommended palette applies as the default

### Theme Picker UX
- **D-39:** Stacked vertical cards — each shows a larger preview + theme name + short description
- **D-40:** Preview is a static mockup (styled illustration with 2-3 fake products in the theme's style) — not live shop data
- **D-41:** Selected theme gets a visual highlight (border, checkmark, or similar)
- **D-42:** Claude's discretion on where in shop settings the theme picker sits relative to brand color pickers

### Claude's Discretion
- Exact font choices for Modern and Dark themes (D-32, D-33, D-34)
- Theme picker placement in shop settings (D-42)
- Responsive breakpoints within each layout (D-17)
- DB schema for storing selected theme (likely a `theme` column on shops or in branding JSON)
- CSS architecture for theme switching (CSS classes, data attributes, or separate stylesheets)
- RTL adjustments per theme (must work, approach is flexible)
- Static mockup design and fake product content
- Transition/animation when switching themes in settings

</decisions>

<specifics>
## Specific Ideas

- Warm theme should feel like the current Sourdough demo — it's the proven aesthetic
- Modern theme should feel like a health-food app (think Sweetgreen, Pressed Juicery)
- Dark theme should feel premium (think upscale cocktail menu or wine bar)
- Card character differences should be immediately obvious when switching — this is the selling point of having themes at all

</specifics>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Requirements
- `.planning/REQUIREMENTS.md` § Menu Themes — THEME-01 through THEME-05
- `.planning/ROADMAP.md` § Phase 5 — success criteria (5 items)

### Current branding system (must integrate with, not replace)
- `resources/views/layouts/app.blade.php` — Admin layout: CSS token generation via PHP linear RGB interpolation (toRgb, mix functions)
- `resources/views/components/layouts/app.blade.php` — Guest layout: simpler CSS injection (paper/ink/crema only)
- `resources/css/app.css` — All CSS tokens in `:root`, component styles for guest menu
- `app/Livewire/ShopSettings.php` — Admin color picker, hex validation, save logic
- `resources/views/livewire/shop-settings.blade.php` — Admin branding UI with color inputs

### Guest menu (primary target of themes)
- `app/Livewire/GuestMenu.php` — Guest menu component logic, locale handling
- `resources/views/livewire/guest-menu.blade.php` — Guest menu UI, product cards, category headers, grid layout
- `app/Models/Shop.php` — branding JSON column (cast as json), fillable

### Font system
- `public/fonts/` — Self-hosted fonts (Rubik variable, Playfair Display Bold)
- `resources/css/app.css` § @font-face declarations — Current font loading

### Prior decisions
- `.planning/STATE.md` § Accumulated Context — "Theme tokens must not overwrite --paper/--ink/--crema"

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- **Branding cascade (app.blade.php):** PHP `toRgb()`, `parseHexToArr()`, `mix()` functions for deriving CSS tokens from base colors — can be extended to derive theme-specific tokens
- **CSS custom properties system (app.css):** Full token-based design system already in place — themes can override/extend these tokens
- **ShopSettings component:** Has color picker UI, hex validation, save logic — extend with theme picker
- **Guest menu CSS classes:** `.menu-product-grid`, `.menu-product-card`, `.menu-product-image-area`, `.menu-category-header` — these become theme-variable

### Established Patterns
- Branding stored as JSON on `Shop.branding` column — theme selection could be added here or as a separate column
- CSS tokens injected via inline `<style>` in layout blade — theme tokens can follow same pattern
- Font files self-hosted in `public/fonts/` — new theme fonts follow same pattern
- `[dir="rtl"]` CSS selectors for Arabic — each theme needs RTL-aware rules

### Integration Points
- `Shop` model — add theme storage (JSON field or column)
- `ShopSettings.php` — add theme picker logic
- `shop-settings.blade.php` — add theme picker UI
- Guest layout (`components/layouts/app.blade.php`) — inject theme class/tokens
- `guest-menu.blade.php` — theme-conditional layout structure and card styling
- `app.css` — theme-specific style blocks
- `public/fonts/` — add Modern and Dark theme font files

</code_context>

<deferred>
## Deferred Ideas

- User-uploadable custom themes / theme marketplace — out of scope, curated presets only
- Custom CSS editor per shop — out of scope (security risk)
- Theme affecting admin views — Phase 5 targets guest menu only
- Animated theme transitions on the guest menu (e.g., fade between themes) — cosmetic, not in scope

</deferred>

---

*Phase: 05-menu-themes*
*Context gathered: 2026-03-21*
