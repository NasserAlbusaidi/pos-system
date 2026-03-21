# Phase 5: Menu Themes - Research

**Researched:** 2026-03-21
**Domain:** CSS custom property theming, multi-theme architecture, RTL font pairing, Livewire 3 live preview
**Confidence:** HIGH

---

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

**Theme Identity**
- D-01: Three themes: Warm, Modern, Dark
- D-02: Warm — Earthy, artisan, inviting. Cream backgrounds, soft shadows, serif headers
- D-03: Modern — Clean, minimal, high-contrast. White background, sharp edges, sans-serif everything
- D-04: Dark — Moody, premium, nightlife-adjacent. Dark background, light text, subtle glows

**Card Styling**
- D-05: Noticeably different card character per theme — not just color swaps
- D-06: Warm: rounded corners + soft shadow
- D-07: Modern: sharp corners + no shadow + thin border
- D-08: Dark: no border + subtle glow effect

**Image Treatment**
- D-09: Subtle variation by theme
- D-10: Warm: soft vignette or rounded image edges
- D-11: Modern: square-cropped, clean edges
- D-12: Dark: slight overlay tint or gradient

**Layout Structure**
- D-13: Each theme gets a unique layout structure
- D-14: Warm: grid layout (current 2-column pattern)
- D-15: Modern: single-column list with horizontal cards (image left, text right)
- D-16: Dark: large hero cards (full-width, dramatic image prominence)

**Image Prominence**
- D-18: Image area size varies by theme
- D-19: Warm: taller image area
- D-20: Modern: smaller image area
- D-21: Dark: medium image area with overlay gradient

**Spacing Density**
- D-22: Density varies per theme
- D-23: Warm: more breathing room (12-16px gaps)
- D-24: Modern: tight and efficient (8px gaps)
- D-25: Dark: medium gaps with more padding inside cards

**Category Headers**
- D-26: Different header patterns per theme
- D-27: Warm: centered with decorative underline
- D-28: Modern: left-aligned, minimal (text + subtle separator)
- D-29: Dark: full-width divider with subtle glow

**Font Pairings**
- D-30: Distinct body + display font combo per theme, all self-hosted
- D-31: Warm: Rubik (body) + Playfair Display (headers) — existing fonts, already in public/fonts/
- D-32: Modern: new sans-serif pairing (Claude's discretion — must be Google Fonts, self-hostable)
- D-33: Dark: new contrast pairing (Claude's discretion — must be Google Fonts, self-hostable)
- D-34: Modern and Dark fonts must have Arabic subset or graceful fallback to IBM Plex Sans Arabic

**Brand Color Interaction**
- D-35: Theme tokens must NOT overwrite --paper/--ink/--crema (branding cascade owns those)
- D-36: Switching themes keeps existing brand colors completely untouched
- D-37: Theme controls structural tokens (layout, card style, spacing, font pairing) and derived tokens (canvas, panel, line, ink-soft) that aren't the base 3
- D-38: If shop has no custom brand colors, theme's recommended palette applies as default

**Theme Picker UX**
- D-39: Stacked vertical cards — each shows a larger preview + theme name + short description
- D-40: Preview is a static mockup (styled illustration with 2-3 fake products in the theme's style)
- D-41: Selected theme gets a visual highlight (border, checkmark, or similar)

### Claude's Discretion
- Exact font choices for Modern and Dark themes (D-32, D-33, D-34)
- Theme picker placement in shop settings (D-42)
- Responsive breakpoints within each layout (D-17)
- DB schema for storing selected theme (likely a `theme` column on shops or in branding JSON)
- CSS architecture for theme switching (CSS classes, data attributes, or separate stylesheets)
- RTL adjustments per theme (must work, approach is flexible)
- Static mockup design and fake product content
- Transition/animation when switching themes in settings

### Deferred Ideas (OUT OF SCOPE)
- User-uploadable custom themes / theme marketplace
- Custom CSS editor per shop
- Theme affecting admin views (guest menu only)
- Animated theme transitions on the guest menu itself
</user_constraints>

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| THEME-01 | Shop can select from 3 preset themes, each defining layout style, color palette, and font pairing | CSS class-based theming on `<html>` tag; theme stored in branding JSON; PHP injects `data-theme` or body class |
| THEME-02 | Theme picker available in shop settings with visual preview of each theme | Extend ShopSettings Livewire component; static mockup previews via inline HTML/CSS in blade |
| THEME-03 | Shop can override brand colors (paper/ink/accent) on top of selected theme | Cascade order: theme defaults in CSS block → branding inline `<style>` overrides; D-35/D-36 locked |
| THEME-04 | All 3 themes render correctly in RTL (Arabic) layout | `[dir="rtl"]` selector blocks per theme; letter-spacing: 0 enforced; IBM Plex Sans Arabic as Arabic fallback for all themes |
| THEME-05 | Theme picker shows a live preview before saving | Alpine.js x-on:click sets a local `previewTheme` variable; CSS class applied to an isolated preview container — no Livewire round-trip needed |
</phase_requirements>

---

## Summary

The theming system extends the existing CSS custom property architecture without replacing it. The current system injects `--paper`, `--ink`, and `--crema` as overrides via an inline `<style>` block in the guest layout. Themes layer beneath that: each theme defines its own structural CSS (layout, card shape, spacing, font pairing) scoped to a `[data-theme="warm"]` / `[data-theme="modern"]` / `[data-theme="dark"]` attribute on the `<html>` element. The branding inline style always wins for color tokens because it appears later in the cascade — this is the mechanism that satisfies D-35/D-36.

Theme selection is stored as a key in the existing `branding` JSON column on `Shop`, with no migration required (the column already exists and is cast to `json`). The guest layout reads `$shop->branding['theme'] ?? 'warm'` and injects `data-theme` on `<html>`. ShopSettings Livewire component adds a `$theme` property alongside the existing color properties, saves it with `array_merge` into the branding JSON, and provides a live preview entirely via Alpine.js (no server round-trips for the preview interaction itself).

Font pairings are the only new infrastructure needed: Modern and Dark themes require two new font families to be downloaded in WOFF2 format and placed in `public/fonts/`. Inter (body) + Inter Display or Inter itself at heavier weights works for Modern because Inter has no Arabic subset and gracefully falls back to IBM Plex Sans Arabic via the existing RTL rule. DM Sans (body) + DM Serif Display (headers) works for Dark for the same reason. Neither font family has Arabic subset on Google Fonts; the existing `[dir="rtl"]` rule overriding to `IBM Plex Sans Arabic` covers that path cleanly.

**Primary recommendation:** Use `data-theme` attribute on `<html>`, store theme in branding JSON, scope all theme CSS via attribute selectors, keep brand color overrides in the inline `<style>` that already exists in the guest layout. This requires zero new infrastructure, only extension of existing patterns.

---

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| CSS Custom Properties | Native | Token-based theming, theme overrides | Already the project's design system; zero dependency |
| Laravel Blade | 12.x | PHP-side theme injection (`data-theme` on `<html>`) | Already used; injects branding tokens |
| Livewire 3 | 3.x | ShopSettings component — add `$theme` property, picker UI | Already the architecture for settings |
| Alpine.js | 3.x (bundled with Livewire) | Live preview: toggle CSS class on isolated container without a Livewire round-trip | Bundled; already used in guest-menu.blade.php |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| Google WebFonts Helper (gwfh.mranftl.com) | N/A (web tool) | Download WOFF2 subsets for Inter and DM Sans self-hosting | One-time during Wave 1 font download step |
| IBM Plex Sans Arabic | Current (already loaded via Google Fonts CDN in app.css) | RTL fallback font for all three themes | Already active for `[dir="rtl"]` — no change needed |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| `data-theme` attribute on `<html>` | Body CSS class (`.theme-warm`) | Both work identically; `data-theme` is semantically clearer and conventional for multi-theme systems — signals "only one value applies at a time" |
| Branding JSON key (`branding.theme`) | Dedicated `theme` column on `shops` table | JSON avoids migration; theme is not queried/filtered in SQL so no index benefit; consistent with how `language`, `receipt_header`, `whatsapp_number` are stored |
| Inter for Modern | System-ui / -apple-system | System fonts require no download but look identical on all OSes — Inter provides the neutral geometric personality needed |
| DM Sans + DM Serif Display for Dark | DM Sans + Playfair Display | Playfair is already used in Warm; DM Sans + DM Serif creates the contrast pairing without font reuse across themes |

**Installation (font files — download once, place in public/fonts/):**

Inter (Modern theme body + headers — use same family, different weight):
```bash
# Download from gwfh.mranftl.com — latin subset, weights 400 + 600 + 700, WOFF2 format
# Files: inter-v13-latin-regular.woff2, inter-v13-latin-600.woff2, inter-v13-latin-700.woff2
# Place in: public/fonts/
```

DM Sans + DM Serif Display (Dark theme):
```bash
# Download from gwfh.mranftl.com — latin subset, WOFF2 format
# DM Sans: weights 400 + 500, variable preferred
# DM Serif Display: weight 400 (display/italic for headers)
# Place in: public/fonts/
```

---

## Architecture Patterns

### Recommended Project Structure Extension

```
resources/
├── css/
│   └── app.css                 # Add theme blocks under @layer components
├── views/
│   ├── components/
│   │   └── layouts/
│   │       └── app.blade.php   # Inject data-theme on <html>, extend <style> block
│   └── livewire/
│       ├── guest-menu.blade.php  # Conditional layout blocks per theme
│       └── shop-settings.blade.php  # Theme picker section + Alpine preview
├── fonts/                      # public/fonts/ — add inter and dm-sans woff2 files
public/
└── fonts/
    ├── [existing Rubik + Playfair files]
    ├── Inter-Regular.woff2     # NEW — Modern theme body
    ├── Inter-SemiBold.woff2    # NEW — Modern theme emphasis
    ├── DMSans-VariableFont.woff2   # NEW — Dark theme body
    └── DMSerifDisplay-Regular.woff2  # NEW — Dark theme headers
app/
└── Livewire/
    └── ShopSettings.php        # Add $theme property, updatedTheme(), mount/save
database/
└── migrations/                 # No new migration needed (branding JSON already exists)
```

### Pattern 1: data-theme Attribute Injection in Guest Layout

**What:** Guest layout reads the shop's theme from branding JSON and sets `data-theme` on `<html>`. CSS scoped to `[data-theme="X"]` selectors handles all structural variation.

**When to use:** Every guest menu page load. Server-rendered, zero JavaScript required for initial render.

```php
// resources/views/components/layouts/app.blade.php — extend existing @php block
@php
    $branding = $shop->branding ?? [];
    $theme = in_array($branding['theme'] ?? '', ['warm', 'modern', 'dark'])
        ? $branding['theme']
        : 'warm';   // safe default
@endphp
<html lang="{{ $currentLocale ?? app()->getLocale() }}"
      dir="{{ $direction ?? 'ltr' }}"
      data-theme="{{ $theme }}">
```

This approach requires no JavaScript, renders correctly in SSR, and works with Livewire's DOM diffing.

### Pattern 2: CSS Token Cascade — Theme Defaults Underneath Brand Overrides

**What:** Theme tokens (structural values + derived color defaults) live in `[data-theme="X"] {}` blocks in `app.css`. The brand color `<style>` block in the layout (already present) injects `--paper`, `--ink`, `--crema` last — overriding any theme defaults for those three tokens only.

**When to use:** Whenever a theme needs a default palette (D-38) but must not disturb a shop's custom brand colors (D-35).

```css
/* In app.css — theme default palettes (apply only when no custom branding overrides) */
[data-theme="warm"] {
    /* Structural tokens — always apply */
    --theme-card-radius: 12px;
    --theme-card-shadow: 0 4px 20px -6px rgb(0 0 0 / 0.15);
    --theme-card-border: 1px solid rgb(var(--line) / 0.8);
    --theme-grid-cols: repeat(2, 1fr);
    --theme-grid-gap: 12px;
    --theme-image-height: 140px;
    --theme-body-font: 'Rubik', system-ui, sans-serif;
    --theme-display-font: 'Playfair Display', Georgia, serif;
    --theme-card-padding: 10px;

    /* Default palette — overridden by inline <style> when shop has custom branding */
    --paper: 245 240 230;
    --ink: 44 37 32;
    --crema: 196 151 90;
}

[data-theme="modern"] {
    --theme-card-radius: 0px;
    --theme-card-shadow: none;
    --theme-card-border: 1px solid rgb(var(--line));
    --theme-grid-cols: 1fr;       /* single column */
    --theme-grid-gap: 8px;
    --theme-image-height: 80px;
    --theme-body-font: 'Inter', system-ui, sans-serif;
    --theme-display-font: 'Inter', system-ui, sans-serif;
    --theme-card-padding: 8px;

    --paper: 255 255 255;
    --ink: 15 15 15;
    --crema: 20 20 200;  /* example modern accent */
}

[data-theme="dark"] {
    --theme-card-radius: 8px;
    --theme-card-shadow: 0 0 24px -8px rgb(var(--crema) / 0.25);  /* glow */
    --theme-card-border: none;
    --theme-grid-cols: 1fr;       /* full-width hero */
    --theme-grid-gap: 16px;
    --theme-image-height: 200px;
    --theme-body-font: 'DM Sans', system-ui, sans-serif;
    --theme-display-font: 'DM Serif Display', Georgia, serif;
    --theme-card-padding: 16px;

    --paper: 14 14 18;
    --ink: 240 238 234;
    --crema: 200 160 80;
}
```

The guest layout's inline `<style>` overrides `--paper`, `--ink`, `--crema` AFTER `app.css` loads — satisfying D-35 without any special logic.

### Pattern 3: Theme-Conditional Layout in guest-menu.blade.php

**What:** The three layout structures (grid, list, hero) differ structurally in their CSS classes. Use Blade `@if($theme === 'X')` to switch between layout variants in the product card loop.

**When to use:** For layout changes that require different HTML structure (not just CSS values).

```php
// In GuestMenu.php render() — pass theme to view
public function render()
{
    $shop = $this->shop;
    $theme = $shop->branding['theme'] ?? 'warm';
    // ...
    return view('livewire.guest-menu', [
        'theme' => $theme,
        // ... other vars
    ]);
}
```

```html
{{-- guest-menu.blade.php — product grid section --}}
@if($theme === 'modern')
    <div class="menu-list-modern">   {{-- single-column horizontal list --}}
@elseif($theme === 'dark')
    <div class="menu-grid-dark">     {{-- full-width hero cards --}}
@else
    <div class="menu-product-grid">  {{-- existing 2-column warm grid --}}
@endif
```

This avoids a single mega-template with many conditional attributes and keeps CSS class names clean.

### Pattern 4: Alpine Live Preview (No Server Round-Trip)

**What:** In shop-settings.blade.php, the theme picker uses `x-data` to hold a `previewTheme` string. Clicking a theme card sets `previewTheme`. A preview container uses `:data-theme="previewTheme"` to apply CSS classes instantly. No Livewire network call until `save()`.

**When to use:** Theme picker UX — satisfies THEME-05 (live preview before saving).

```html
{{-- In shop-settings.blade.php — theme picker section --}}
<div x-data="{ previewTheme: '{{ $theme }}' }">

    {{-- Theme picker cards --}}
    <div class="theme-picker-grid">
        @foreach(['warm', 'modern', 'dark'] as $t)
        <button
            type="button"
            x-on:click="previewTheme = '{{ $t }}'; $wire.set('theme', '{{ $t }}')"
            :class="previewTheme === '{{ $t }}' ? 'theme-card--selected' : ''"
            class="theme-card"
        >
            {{-- Static mockup preview --}}
            <div class="theme-mockup" data-theme="{{ $t }}">
                {{-- Fake product cards styled per-theme via data-theme --}}
            </div>
            <p class="theme-card-name">{{ ucfirst($t) }}</p>
        </button>
        @endforeach
    </div>

    {{-- Live preview strip showing the selected theme's character --}}
    <div class="theme-live-preview" :data-theme="previewTheme">
        {{-- Mini product card in preview zone --}}
    </div>
</div>
```

Note: `$wire.set('theme', ...)` syncs the Livewire property so `save()` picks it up. The visual preview is instant (Alpine); the data sync is a lightweight Livewire property update.

### Pattern 5: RTL Scoping Per Theme

**What:** Every theme-specific CSS block needs an RTL override sub-block to handle font override, letter-spacing zeroing, and layout mirroring.

**When to use:** After defining any `[data-theme="X"]` structural rules.

```css
/* Modern theme RTL */
[data-theme="modern"] [dir="rtl"],
[dir="rtl"] [data-theme="modern"] {
    font-family: 'IBM Plex Sans Arabic', system-ui, sans-serif;
    letter-spacing: 0;
}

[data-theme="modern"] [dir="rtl"] .menu-category-header,
[dir="rtl"] [data-theme="modern"] .menu-category-header {
    letter-spacing: 0;
}

/* Modern horizontal card RTL: image flips from left to right */
[data-theme="modern"] [dir="rtl"] .menu-card-modern {
    flex-direction: row-reverse;
}
```

The selector order matters: `data-theme` is on `<html>`, `dir` is also on `<html>`, so `[data-theme="X"][dir="rtl"]` or `[dir="rtl"] [data-theme="X"]` both work. Use `[data-theme="X"][dir="rtl"]` when both attributes are on the same element (`<html>`).

### Anti-Patterns to Avoid

- **Separate CSS files per theme:** Don't create `warm.css`, `modern.css`, `dark.css` — they require conditional `<link>` injection, flash of unstyled content risk, and complicate Vite builds. Scope selectors in one `app.css` instead.
- **Overwriting --paper/--ink/--crema in the inline `<style>` block:** The inline `<style>` must ONLY output those three tokens (plus derived tokens). Theme default palettes go in CSS attribute selectors in `app.css`, not in the inline style. This is how D-35 is enforced structurally.
- **Using `wire:model` on theme selection for live preview:** A `wire:model` change triggers a Livewire round-trip and full component re-render. Use Alpine `x-on:click` + `$wire.set()` instead — preview is instant, sync is cheap.
- **Applying `letter-spacing` to any Arabic text element:** Arabic letters must be connected. Any CSS class that sets `letter-spacing > 0` must be wrapped in a `[dir="ltr"]` scope. The existing `app.css` already does this for Tailwind tracking utilities — new theme CSS must follow the same pattern.
- **Using `object-fit: cover` for product images:** The project decision (from v1.0) is `object-contain` to preserve Sourdough cut-out photos. All three themes must honor this.
- **Using a migration for theme storage:** The `branding` column is already `json`-cast on `Shop`. Store `theme` as `branding['theme']`. No migration needed.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Live preview without page reload | Custom JS to patch CSS vars on picker click | Alpine.js `x-on:click` + `:data-theme` binding | Alpine is already bundled with Livewire; 3 lines replaces a custom event system |
| Font subsetting/optimization | Manual Python fonttools subsetting | Google WebFonts Helper (gwfh.mranftl.com) | Generates correct @font-face + WOFF2 per subset in 10 seconds |
| RTL letter-spacing fix | Per-element `letter-spacing: 0` on every Arabic element | Blanket `[dir="rtl"] * { letter-spacing: 0; }` or per-class RTL block | Already established pattern in existing `app.css` — follow it |
| Theme validation in save() | Custom PHP allowlist check | `'required|in:warm,modern,dark'` Laravel validation rule | One line, already the pattern for `language` in ShopSettings |
| CSS injection protection | Sanitize theme name before output | Validation + allowlist ensures only literal strings `warm`/`modern`/`dark` reach Blade | Theme is from an allowlist, not user-typed free text — no CSS injection risk |

**Key insight:** The existing CSS custom property system plus the existing `data-theme`/body class conventions mean zero new infrastructure is required. This is an extension sprint, not a foundation sprint.

---

## Common Pitfalls

### Pitfall 1: Theme Tokens Overwriting Brand Colors
**What goes wrong:** If `[data-theme="warm"]` sets `--paper: 245 240 230` and the inline `<style>` also sets `--paper` (from branding), the last declaration wins. If the inline style is inside `<head>` before `app.css` loads, the CSS file wins and brand colors are lost.

**Why it happens:** CSS cascade depends on order of `<link>` vs `<style>` in `<head>`. Vite loads `app.css` via `<link>` which appears before the inline `<style>`. Inline `<style>` after `<link>` wins for same-specificity declarations.

**How to avoid:** Keep the existing pattern — `@vite(...)` first, inline `<style>` after. Theme defaults in `app.css` via attribute selector have lower cascade priority than the inline `<style>` block that follows. This is the correct order already.

**Warning signs:** Guest menu shows theme's default palette even after admin saves custom brand colors.

### Pitfall 2: letter-spacing on Arabic Breaks Connective Script
**What goes wrong:** CSS classes with `tracking-[X]` or `letter-spacing` applied to Arabic text visually disconnect Arabic letters, making text unreadable.

**Why it happens:** Arabic is a connected script — adding space between characters breaks the visual ligatures that make Arabic readable.

**How to avoid:** All non-zero `letter-spacing` values must be scoped to `[dir="ltr"]`. The pattern already exists in `app.css` for Tailwind tracking utilities. New theme CSS with spacing must follow the same scoping. Use `letter-spacing: 0` in all `[dir="rtl"]` blocks.

**Warning signs:** Arabic text in the Modern or Dark theme looks like disconnected individual letters.

### Pitfall 3: Modern Theme Horizontal Layout Breaking in RTL
**What goes wrong:** The Modern theme's horizontal card (image-left, text-right) becomes image-right, text-left in RTL — which is actually correct for Arabic reading direction. But if implemented with `flex-direction: row`, the image may overlap text or create awkward spacing in RTL.

**Why it happens:** `flex-direction: row` places items in writing-direction order. In RTL, `flex-start` is the right side. Use `flex-direction: row-reverse` in LTR and `flex-direction: row` in RTL — OR use CSS logical properties.

**How to avoid:** Use `flex-direction: row` in a `[dir="rtl"]` block for the Modern horizontal card, or use `margin-inline-start`/`margin-inline-end` instead of `margin-left`/`margin-right`. Test with Arabic content before merge.

**Warning signs:** Arabic Modern theme shows image on wrong side, or text and image overlap.

### Pitfall 4: Static Mockup Previews Reflecting Real Shop Data
**What goes wrong:** If the mockup preview mistakenly uses `$shop->name` or real product data, switching themes in settings changes the preview to show real data inconsistently, or breaks when shop has no products.

**Why it happens:** Blade templates share scope; it's easy to accidentally reference surrounding Livewire props.

**How to avoid:** All three theme mockups use hardcoded fake data: shop name "Spice Garden", fake product names and prices. No `$shop`, `$categories`, or `$products` references inside the mockup HTML.

**Warning signs:** Theme preview shows empty state when shop has no products.

### Pitfall 5: Font Loading FOUT (Flash of Unstyled Text)
**What goes wrong:** New fonts (Inter, DM Sans, DM Serif Display) load after initial paint, causing text to reflow from fallback to custom font.

**Why it happens:** WOFF2 files for new themes only load when that theme is active, but there's no preload hint.

**How to avoid:** Add `font-display: swap` on all new `@font-face` declarations (already used for Rubik/Playfair). Consider adding `<link rel="preload">` for the active theme's fonts only — but this requires knowing the active theme at layout render time (which we do, since PHP sets `data-theme`). The guest layout can conditionally preload the active theme's fonts.

**Warning signs:** Visible text reflow 200-500ms after page load on first visit to a Modern or Dark theme menu.

### Pitfall 6: Livewire Re-render Resetting Alpine Preview State
**What goes wrong:** When the user clicks a theme card and `$wire.set('theme', ...)` triggers a Livewire network request, Livewire's DOM diffing may reset the Alpine `previewTheme` state if the element is morphed.

**Why it happens:** Livewire 3 uses Morphdom for DOM diffing. Alpine state survives if the element identity is preserved, but aggressive diffing can reset `x-data`.

**How to avoid:** Use `wire:key` on the theme picker container, or use `$wire.set()` only in the picker card button (not a reactive property that triggers re-render). Better: use `$wire.theme = 'modern'` (direct property assignment) without calling an action, so Livewire defers the round-trip. Or wrap the picker in `x-data` and use `wire:ignore` on the preview zone.

**Warning signs:** Clicking a theme card shows the preview briefly then reverts to the previously saved theme.

---

## Code Examples

Verified patterns from existing codebase and established conventions:

### Branding JSON — Add theme key (ShopSettings mount)
```php
// app/Livewire/ShopSettings.php — mount()
$this->theme = $branding['theme'] ?? 'warm';
```

### Branding JSON — Save theme (ShopSettings save)
```php
// app/Livewire/ShopSettings.php — save()
$shop->update([
    'branding' => array_merge($branding, [
        'paper' => $paper,
        'ink' => $ink,
        'accent' => $accent,
        'theme' => $this->theme,   // NEW
        'receipt_header' => $this->receipt_header ?? '',
        'language' => $this->language,
        'whatsapp_number' => $this->whatsapp_number ?? '',
        'whatsapp_notifications_enabled' => (bool) $this->whatsapp_notifications_enabled,
    ]),
]);
```

### Validation Rule for Theme
```php
// app/Livewire/ShopSettings.php — validate()
'theme' => 'required|in:warm,modern,dark',
```

### data-theme injection in guest layout
```php
// resources/views/components/layouts/app.blade.php
@php
    $branding = $shop->branding ?? [];
    $theme = in_array($branding['theme'] ?? '', ['warm', 'modern', 'dark'])
        ? $branding['theme']
        : 'warm';
    // ... existing $toRgb, $paperHex, etc. code
@endphp
<html lang="{{ $currentLocale ?? app()->getLocale() }}"
      dir="{{ $direction ?? 'ltr' }}"
      data-theme="{{ $theme }}">
```

### Font face declarations for new fonts (to add to app.css)
```css
/* Inter — Modern theme */
@font-face {
    font-family: 'Inter';
    src: url('/fonts/Inter-Regular.woff2') format('woff2');
    font-weight: 400;
    font-style: normal;
    font-display: swap;
}

@font-face {
    font-family: 'Inter';
    src: url('/fonts/Inter-SemiBold.woff2') format('woff2');
    font-weight: 600;
    font-style: normal;
    font-display: swap;
}

/* DM Sans — Dark theme body */
@font-face {
    font-family: 'DM Sans';
    src: url('/fonts/DMSans-VariableFont.woff2') format('woff2');
    font-weight: 300 700;
    font-style: normal;
    font-display: swap;
}

/* DM Serif Display — Dark theme headers */
@font-face {
    font-family: 'DM Serif Display';
    src: url('/fonts/DMSerifDisplay-Regular.woff2') format('woff2');
    font-weight: 400;
    font-style: normal;
    font-display: swap;
}
```

### RTL selector for inter (no Arabic subset — fallback pattern)
```css
/* Inter has no Arabic subset. IBM Plex Sans Arabic fallback already handles RTL globally. */
/* No additional rule needed — existing [dir="rtl"] { font-family: 'IBM Plex Sans Arabic'... } covers it. */
/* But: letter-spacing must be explicitly zeroed for modern theme elements that set it */
[data-theme="modern"][dir="rtl"] .menu-category-header,
[data-theme="modern"][dir="rtl"] .menu-product-name {
    letter-spacing: 0;
}
```

### GuestMenu component — pass theme to view
```php
// app/Livewire/GuestMenu.php — render()
public function render()
{
    // ... existing query logic
    return view('livewire.guest-menu', [
        'shop' => $this->shop,
        'categories' => $categories,
        'theme' => $this->shop->branding['theme'] ?? 'warm',
        // ... other props
    ]);
}
```

### Existing test pattern to follow (from GuestMenuBrandingTest.php)
```php
// tests/Feature/MenuThemeTest.php — pattern to follow
$shop = Shop::create([
    'name' => 'Test Shop',
    'slug' => 'test-shop',
    'branding' => ['theme' => 'dark', 'paper' => '#0E0E12', ...],
]);

$response = $this->get(route('guest.menu', $shop->slug));
$response->assertStatus(200);
$response->assertSee('data-theme="dark"', false);
```

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Multiple CSS files per theme, conditional `<link>` injection | Single CSS file with scoped `[data-theme="X"]` selectors | 2021-2023 shift | No FOUC, simpler build, cascade is predictable |
| Theme stored in localStorage only (JS) | Theme stored server-side (DB) + rendered into HTML attribute | Always the right approach for SSR | Works without JS; no theme flash on load; correct for Livewire |
| Separate RTL stylesheet | Logical CSS properties + `[dir="rtl"]` selectors in one file | 2022+ | Less duplication, single source of truth |
| Google Fonts CDN for self-hosted fonts | WOFF2 in `public/fonts/` + `@font-face` | Phase-defined (all fonts self-hosted in this project) | Privacy, no external dependency, better performance |

**Deprecated/outdated:**
- WOFF/EOT/SVG font formats: WOFF2 only for all modern browsers (2016+). All new `@font-face` declarations use WOFF2 only.
- `prefers-color-scheme` for multi-theme: This project uses explicit theme selection, not OS dark mode detection. Do not add `@media (prefers-color-scheme: dark)` rules.
- `@import` for font loading: Already not used — Vite handles the CSS file; `@font-face` declarations are all inline in `app.css`.

---

## Open Questions

1. **Font file variable vs static for Inter**
   - What we know: Inter has a variable font (woff2) available — `Inter-VariableFont_slnt,wght.woff2` (~250KB). Static subset WOFF2 for weight 400+600 is ~40KB total.
   - What's unclear: Whether the variable font's size overhead is acceptable given only 1-2 weights are needed for Modern theme.
   - Recommendation: Use static subsets (400 and 600 weights, latin subset only) for smaller payload. Variable font only if Phase 6 (custom fonts) later needs weight animation.

2. **Default theme for new shops**
   - What we know: D-38 says if shop has no custom brand colors, theme's recommended palette applies. Nothing specifies the default theme for brand-new shops.
   - What's unclear: Whether new shops should default to Warm (matches current look) or be asked to choose.
   - Recommendation: Default to `'warm'` in PHP fallback (`$branding['theme'] ?? 'warm'`). This preserves the current guest menu appearance for existing shops with no theme set.

3. **Dark theme background: pure dark or near-dark**
   - What we know: D-04 says "Dark background, light text." The default `--paper` for dark would be something like `#0E0E12`.
   - What's unclear: Whether `body` background gradient (the current radial + linear gradient in `app.css`) needs to be suppressed or themed for dark.
   - Recommendation: Dark theme should override the `body` gradient in `.guest-menu-bg` to use a flat dark background or a subtle dark-to-dark gradient. Scope this override to `[data-theme="dark"] .guest-menu-bg`.

---

## Validation Architecture

`workflow.nyquist_validation` is `true` in `.planning/config.json` — this section is required.

### Test Framework
| Property | Value |
|----------|-------|
| Framework | PHPUnit (via Laravel) |
| Config file | `phpunit.xml` |
| Quick run command | `php artisan test --filter=MenuTheme` |
| Full suite command | `composer test` |

### Phase Requirements → Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| THEME-01 | Selecting warm/modern/dark persists to branding JSON | unit | `php artisan test --filter=ShopSettingsThemeTest` | ❌ Wave 0 |
| THEME-02 | Theme picker renders in settings with 3 option cards | feature (Livewire) | `php artisan test --filter=ShopSettingsThemeTest` | ❌ Wave 0 |
| THEME-03 | Brand colors survive theme switch (paper/ink/accent unchanged) | feature (Livewire) | `php artisan test --filter=ShopSettingsThemeTest` | ❌ Wave 0 |
| THEME-04 | Guest menu emits correct `data-theme` attribute | feature (HTTP) | `php artisan test --filter=MenuThemeRenderTest` | ❌ Wave 0 |
| THEME-04 | RTL: no letter-spacing on Arabic elements | manual screenshot | n/a — manual | N/A |
| THEME-05 | Live preview: Alpine state change (no round-trip) | manual | n/a — browser test | N/A |

### Sampling Rate
- **Per task commit:** `php artisan test --filter=MenuTheme`
- **Per wave merge:** `composer test`
- **Phase gate:** Full suite green before `/gsd:verify-work`

### Wave 0 Gaps
- [ ] `tests/Feature/MenuThemeRenderTest.php` — covers THEME-01, THEME-04 (HTTP: assert `data-theme` in HTML)
- [ ] `tests/Feature/ShopSettingsThemeTest.php` — covers THEME-01, THEME-02, THEME-03 (Livewire: save theme, assert branding, assert brand colors unchanged)
- [ ] No new conftest/fixtures needed — `ShopFactory`, `RefreshDatabase`, and existing Livewire testing patterns are sufficient

*(Existing `GuestMenuBrandingTest.php` and `ShopSettingsTest.php` provide the patterns to follow.)*

---

## Sources

### Primary (HIGH confidence)
- Existing codebase: `resources/views/components/layouts/app.blade.php` — confirmed cascade order (Vite link before inline style)
- Existing codebase: `resources/css/app.css` — confirmed `[dir="rtl"]` selector pattern, existing letter-spacing overrides
- Existing codebase: `app/Livewire/ShopSettings.php` — confirmed `array_merge` + `branding` JSON save pattern
- Existing codebase: `app/Models/Shop.php` — confirmed `branding` cast as `json`, no new migration needed
- Existing codebase: `public/fonts/` — confirmed Rubik variable + Playfair Display Bold already self-hosted
- MDN Web Docs: CSS Custom Properties cascade behavior — specificity + source order

### Secondary (MEDIUM confidence)
- WebSearch + [MDN letter-spacing](https://developer.mozilla.org/en-US/docs/Web/CSS/Reference/Properties/letter-spacing) — Arabic letter-spacing must be 0
- WebSearch + [rtlstyling.com](https://rtlstyling.com/posts/rtl-styling/) — RTL flex-direction patterns
- [Google WebFonts Helper](https://gwfh.mranftl.com/fonts/inter) — Inter WOFF2 self-hosting tool confirmed available
- [IBM Plex GitHub](https://github.com/IBM/plex/blob/master/packages/plex-sans-arabic/fonts/complete/woff2/IBMPlexSansArabic-Regular.woff2) — IBM Plex Sans Arabic WOFF2 available (already loaded via CDN in existing app.css)
- [CSS Custom Properties theming guide 2025](https://ronaldsvilcins.com/2025/03/30/a-practical-guide-to-css-custom-properties-for-theming/) — data-theme attribute as current best practice
- Livewire docs (laravel-livewire.com/docs/3.x/alpine) — Alpine bundled with Livewire 3, `$wire.set()` pattern

### Tertiary (LOW confidence)
- WebSearch: DM Sans / DM Serif Display Arabic subset — not confirmed available. Both fonts are Latin-only on Google Fonts. Confirmed by absence of Arabic in official GitHub (googlefonts/dm-fonts). IBM Plex Sans Arabic fallback covers this gap.
- WebSearch: Inter Arabic subset — not available on Google Fonts. Inter supports 147 languages but Latin script only. Same IBM Plex Sans Arabic fallback strategy applies.

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — All based on existing codebase patterns; no new infrastructure
- Architecture: HIGH — data-theme + CSS cascade is the established 2025 pattern; verified against existing guest layout structure
- Font choices: MEDIUM — Inter (Modern) and DM Sans + DM Serif Display (Dark) well-known fonts but Arabic subset absence is inferred, not directly confirmed from official source
- Pitfalls: HIGH — Letter-spacing Arabic pitfall confirmed by MDN + rtlstyling.com; cascade pitfall confirmed from codebase inspection; Alpine/Livewire interaction is established behavior
- RTL patterns: HIGH — Based on existing `app.css` patterns already proven to work in production

**Research date:** 2026-03-21
**Valid until:** 2026-04-21 (stable CSS/Livewire domain; font availability may shift but unlikely)
