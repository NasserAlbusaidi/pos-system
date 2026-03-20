# Phase 1: Polish - Research

**Researched:** 2026-03-20
**Domain:** Laravel 12 + Livewire 3 + Vanilla CSS — Guest menu visual overhaul
**Confidence:** HIGH (all findings from direct codebase inspection)

## Summary

Phase 1 polishes the existing `GuestMenu` Livewire component. The work is entirely within three files (`GuestMenu.php`, `guest-menu.blade.php`, `app.css`) plus one layout file (`layouts/app.blade.php`). There are no third-party library upgrades or new routes — only targeted edits to existing code, CSS additions, a font addition, and two new Livewire feature tests.

The biggest risk is the branding cascade: the current layout only emits three `--paper`, `--ink`, `--crema` RGB triplets. The six derived tokens (`--canvas`, `--panel`, `--panel-muted`, `--line`, `--ink-soft`) currently have hardcoded defaults in `app.css :root` and are NEVER overridden by the inline `<style>` block. This means cold-grey defaults leak through on every warm-palette shop. BRND-01/02/03 require extending the PHP derivation logic in `app.blade.php` to emit all six tokens.

The second-largest work area is the card layout rewrite. The current template renders a full-width card with description always visible, a full-width `btn-primary` add-to-cart button, and `object-cover` images. All of this must be replaced with: 2-column fixed grid, compact cards (photo + name + price, description hidden), `+` quick-add button, expand-in-place accordion, `object-contain` images, shimmer skeleton, and `onerror` fallback icon.

**Primary recommendation:** Touch only `guest-menu.blade.php`, `app.css`, and `layouts/app.blade.php`. Do not refactor GuestMenu.php beyond adding an `image_url` accessor if preferred, or simply fix the template `src` attribute directly.

---

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

- **D-01:** 2-column compact grid on ALL screen sizes (not responsive 1 to 2 columns)
- **D-02:** Compact cards show: photo + name + price. Description hidden by default
- **D-03:** Tap card to expand in-place — card grows to reveal description below price, other cards shift down
- **D-04:** Accordion behavior — only one card expanded at a time. Tapping another card auto-collapses the current one
- **D-05:** Small `+` icon on each compact card for quick-add to cart (no need to expand first)
- **D-06:** Expanded view shows description text only — add-to-cart is via the `+` icon on the compact card
- **D-07:** Cards have consistent min-height regardless of image presence (GMVIZ-10)
- **D-08:** Fix image URL bug — prepend `/storage/` prefix to `image_url` when rendering (currently missing)
- **D-09:** Use `object-contain` (not `object-cover`) to preserve cut-out product shapes without cropping
- **D-10:** Animated gradient shimmer (left-to-right) as skeleton placeholder while images load
- **D-11:** Broken/missing images show a subtle fork-and-knife or plate icon centered in the image area (keeps card height consistent)
- **D-12:** Product names in sentence case (not forced uppercase)
- **D-13:** Playfair Display serif font for category headers (self-hosted in public/fonts/, consistent with project constraint)
- **D-14:** Empty categories (zero visible products) hidden from guest menu
- **D-15:** Category header styling — Claude's discretion on size, weight, spacing, decorative elements
- **D-16:** All CSS tokens (--canvas, --panel, --panel-muted, --line, --ink-soft) derived from 3 brand colors (paper, ink, accent)
- **D-17:** Derivation algorithm — Claude's discretion, but must produce warm cohesive tones from Sourdough's palette (paper: #F5F0E8, accent: #C4975A, ink: #2C2520)
- **D-18:** Background gradient — subtle vertical gradient from paper to a slightly deeper warm tone (barely noticeable depth, not flat)
- **D-19:** Accent color (gold) used ONLY on buttons and interactive elements (+ icon, cart bar, CTAs). Cards stay neutral paper/panel tones
- **D-20:** Card surfaces and borders reflect shop's warm palette end-to-end (BRND-03)
- **D-21:** Feature test: product with image_url renders `<img>` with `/storage/` prefix
- **D-22:** Feature test: shop with custom branding renders derived CSS variables

### Claude's Discretion

- Exact branding derivation algorithm (tint/shade vs mix — just make it warm and cohesive)
- Category header visual treatment (size, weight, decorative line, spacing)
- Shimmer animation timing and gradient colors
- Placeholder icon choice (fork-and-knife, plate, or similar food icon)
- Exact card border-radius, shadow depth, spacing between cards
- Expand/collapse animation (smooth height transition)

### Deferred Ideas (OUT OF SCOPE)

- Image optimization pipeline (resize, WebP) — v2 requirement GMVIZ-V2-01
- Per-shop photo style (cover vs contain) configurable — v2 requirement GMVIZ-V2-02
- Per-shop name casing configurable — v2 requirement GMVIZ-V2-03
- Shop logo in guest menu header — v2 requirement BRND-V2-02
- Bottom-sheet product detail view — considered but expand-in-place chosen for simplicity
</user_constraints>

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| GMVIZ-01 | Guest menu displays product photos with correct `/storage/` URL prefix | Image `src` currently uses raw `$product->image_url` with no prefix — line 145 of guest-menu.blade.php |
| GMVIZ-02 | Product photos use `object-contain` to preserve cut-out shapes | Current class is `object-cover` on line 145 — single class swap |
| GMVIZ-03 | Product names display in sentence case (not forced uppercase) | Current h4 has `uppercase` Tailwind class — remove it |
| GMVIZ-04 | Guest menu uses 2-column compact card grid on all screen sizes | Current grid is `grid gap-4 md:grid-cols-2` (1-col mobile) — change to always 2-col |
| GMVIZ-05 | Compact cards show photo + name + price; description hidden, reveals on interaction | Full redesign of the article card; Alpine.js x-data for expand state |
| GMVIZ-06 | Category headers use Playfair Display serif font (self-hosted) | Currently `font-display` (Rubik) — add Playfair Display font files + @font-face + CSS class |
| GMVIZ-07 | Image containers show skeleton shimmer while photos download | `.skeleton` utility already defined in app.css — use it with Alpine.js `x-show` + `@load` |
| GMVIZ-08 | Broken/missing images hide gracefully via onerror fallback | Add `onerror` handler to `<img>` that swaps to placeholder SVG/icon |
| GMVIZ-09 | Empty categories (zero visible products) are hidden from guest menu | Already filtered in `GuestMenu::render()` via `.filter(fn ($cat) => $cat->products->isNotEmpty())` — ALREADY DONE |
| GMVIZ-10 | Product cards have consistent height regardless of image presence (min-height) | CSS `min-height` on image container area ensures consistent card height |
| BRND-01 | All CSS tokens (--canvas, --panel, --panel-muted, --line, --ink-soft) derived from 3 brand colors | layouts/app.blade.php currently only emits --paper, --ink, --crema — extend PHP derivation block |
| BRND-02 | Background gradient uses derived tokens instead of hardcoded/default values | body background in app.css uses --canvas and --paper (design tokens, already token-driven) but --canvas is never overridden by branding injection |
| BRND-03 | Card surfaces and borders reflect shop's warm palette end-to-end | .surface-card uses --panel and --line which are never overridden — fixed by BRND-01 |
| TEST-01 | Feature test: product with image_url renders `<img>` with `/storage/` prefix | Add to GuestMenuTest.php using `assertSeeHtml('/storage/...')` pattern |
| TEST-02 | Feature test: shop with custom branding renders derived CSS variables | Add to GuestMenuTest.php — assert layout output contains `--canvas:` or `--panel:` with overridden values |
</phase_requirements>

---

## Standard Stack

### Core (verified by direct inspection)
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Livewire | 3.x | Interactive component rendering | Project-mandated, all UI is Livewire |
| Alpine.js | bundled with Livewire 3 | Client-side expand/collapse accordion state | Already used in project; Livewire 3 ships with Alpine |
| PHPUnit | 11.x | Feature tests | Existing test suite, `phpunit.xml` confirmed |
| Vanilla CSS + Tailwind utilities | Per project | Styling | No new frameworks; project uses CSS custom properties |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| Playfair Display (Google Fonts / self-hosted) | Variable or static TTF/WOFF2 | Category header serif font | Self-host in `public/fonts/` — do NOT use Google Fonts CDN (project constraint: all fonts are self-hosted) |

### No New Packages Required
All work uses existing infrastructure. No `composer require` or `npm install` needed.

**Font acquisition:** Playfair Display can be downloaded from Google Fonts (fonts.google.com/specimen/Playfair+Display) — download as `.ttf` or `.woff2` files and place in `public/fonts/`. The `@font-face` declaration goes in `resources/css/app.css` following the existing Rubik pattern.

---

## Architecture Patterns

### Recommended File Touch List

```
resources/
├── views/
│   └── livewire/
│       └── guest-menu.blade.php      # Card layout rewrite + image fix + accordion
│   └── layouts/
│       └── app.blade.php             # Extend branding injection with derived tokens
├── css/
│   └── app.css                       # Playfair @font-face, menu card CSS classes
public/
└── fonts/
    └── PlayfairDisplay-*.ttf         # New: self-hosted Playfair Display
tests/
└── Feature/
    └── Livewire/
        └── GuestMenuTest.php          # Two new tests appended
```

No changes to `GuestMenu.php` are required for the core work. The image URL fix is in the template `src` attribute; the category filtering is already done in PHP.

### Pattern 1: Image URL Fix (GMVIZ-01)

**What:** The `image_url` database column stores paths like `products/abc123.jpg` (relative to storage). The full public URL is `/storage/products/abc123.jpg`. The current template renders `src="{{ $product->image_url }}"` — missing the `/storage/` prefix.

**Fix options:**
- Option A (preferred, template-only): Change the template to `src="/storage/{{ $product->image_url }}"` — zero PHP changes
- Option B (accessor): Add `getImageUrlAttribute()` accessor to Product model that prepends `/storage/` — cleaner but changes model behavior across all uses

Option A is safer — it keeps the fix isolated to the guest menu view and does not affect other parts of the system that might use `$product->image_url` differently.

**Example (template fix):**
```blade
{{-- Before --}}
<img src="{{ $product->image_url }}" ...>

{{-- After (Option A) --}}
<img src="/storage/{{ $product->image_url }}" ...>
```

**Test pattern (TEST-01):**
```php
// In GuestMenuTest.php
public function test_product_image_url_includes_storage_prefix(): void
{
    $shop = Shop::create(['name' => 'Sourdough', 'slug' => 'sourdough']);
    $category = Category::create(['shop_id' => $shop->id, 'name_en' => 'Bread']);
    $product = Product::forceCreate([
        'shop_id' => $shop->id,
        'category_id' => $category->id,
        'name_en' => 'Sourdough Loaf',
        'price' => 2.500,
        'image_url' => 'products/sourdough.jpg',
        'is_available' => true,
        'is_visible' => true,
    ]);

    Livewire::test(GuestMenu::class, ['shop' => $shop])
        ->assertSeeHtml('/storage/products/sourdough.jpg');
}
```

### Pattern 2: Compact Card Grid with Accordion (GMVIZ-04/05/03/07/08/09/10)

**What:** Replace the current full-width card with a 2-column compact card. Expand/collapse is client-side only (no wire:click round-trip), using Alpine.js.

**Current card structure (lines 132-175 of guest-menu.blade.php):**
- `article.surface-card` with full padding
- Always-visible `<p>` description
- `h4` with `uppercase` class
- `object-cover` image
- Full-width `btn-primary` button

**New card structure:**
```blade
{{-- Alpine parent wrapping the whole grid for accordion --}}
<div x-data="{ expanded: null }" class="grid grid-cols-2 gap-3">
    @foreach($category->products as $product)
        <article
            class="menu-product-card surface-card"
            x-data="{ loaded: false, broken: false }"
            @click="expanded = (expanded === {{ $product->id }}) ? null : {{ $product->id }}"
        >
            {{-- Image area: fixed height, shimmer while loading --}}
            <div class="menu-product-image-area">
                <div class="skeleton" x-show="!loaded && !broken"></div>
                @if($product->image_url)
                    <img
                        src="/storage/{{ $product->image_url }}"
                        alt="{{ $product->translated('name') }}"
                        class="menu-product-img"
                        x-show="loaded && !broken"
                        @load="loaded = true"
                        @error="broken = true"
                        style="display: none"
                    >
                @endif
                {{-- Placeholder icon when broken or no image --}}
                <div class="menu-product-placeholder" x-show="broken || !{{ $product->image_url ? 'true' : 'false' }}">
                    {{-- SVG fork-and-knife icon --}}
                </div>
            </div>

            {{-- Name + price row --}}
            <div class="menu-product-body">
                <p class="menu-product-name">{{ $product->translated('name') }}</p>
                <div class="flex items-center justify-between gap-2">
                    <span class="menu-product-price">...</span>
                    {{-- Quick-add + button —prevents event from bubbling to card expand --}}
                    <button
                        wire:click.stop="addToCart({{ $product->id }})"
                        class="menu-product-add"
                        type="button"
                    >+</button>
                </div>
            </div>

            {{-- Expandable description --}}
            <div
                x-show="expanded === {{ $product->id }}"
                x-collapse
                class="menu-product-description"
            >
                <p>{{ $product->translated('description') }}</p>
            </div>
        </article>
    @endforeach
</div>
```

**Key Alpine notes:**
- `x-collapse` is an Alpine plugin bundled with Livewire 3's Alpine distribution (provides smooth height transitions). Verify availability — if not bundled, use a CSS `max-height` transition approach instead (see Anti-Patterns).
- `.stop` modifier on `wire:click` prevents the card expand `@click` from firing when tapping the `+` button.
- `x-show` + `@load` / `@error` on the `<img>` handles the shimmer-to-image transition without a Livewire round-trip.
- `style="display: none"` on the `<img>` prevents flash before Alpine initialises.

### Pattern 3: Branding Token Derivation (BRND-01/02/03)

**What:** Extend the PHP block in `layouts/app.blade.php` to compute and emit all six derived tokens as inline CSS variables, overriding the `app.css :root` defaults.

**Current gap:** The inline `<style>` block only sets `--paper`, `--ink`, `--crema`. The tokens `--canvas`, `--panel`, `--panel-muted`, `--line`, `--ink-soft` are defined in `app.css :root` with hardcoded cold-grey defaults and are never overridden per-shop.

**Derivation algorithm (Claude's discretion — recommended approach):**

```php
// In layouts/app.blade.php — extend the existing @php block
@php
    // Parse hex to [r, g, b] integers
    $parseHex = function (string $hex): array {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (strlen($hex) !== 6) { return [0, 0, 0]; }
        return array_values(sscanf($hex, '%02x%02x%02x'));
    };

    // Mix two RGB arrays at a given ratio (0=a, 1=b)
    $mix = function (array $a, array $b, float $t): array {
        return [
            (int) round($a[0] + ($b[0] - $a[0]) * $t),
            (int) round($a[1] + ($b[1] - $a[1]) * $t),
            (int) round($a[2] + ($b[2] - $a[2]) * $t),
        ];
    };

    $toRgbStr = fn(array $c): string => "{$c[0]} {$c[1]} {$c[2]}";

    $paper = $parseHex($paperHex);
    $ink   = $parseHex($inkHex);
    $crema = $parseHex($cremaHex);

    // Derived tokens
    // --canvas: paper darkened slightly (mix paper toward ink at 6%)
    $canvas    = $mix($paper, $ink, 0.06);
    // --panel: paper lightened slightly (mix paper toward white at 30%)
    $panel     = $mix($paper, [255, 255, 255], 0.30);
    // --panel-muted: paper with slight ink tint (mix paper toward ink at 12%)
    $panelMuted = $mix($paper, $ink, 0.12);
    // --line: midpoint between paper-muted and ink at 18% (border colour)
    $line      = $mix($paper, $ink, 0.18);
    // --ink-soft: ink lightened toward paper at 55% (secondary text)
    $inkSoft   = $mix($ink, $paper, 0.55);
@endphp
<style>
    :root {
        --paper: {{ $toRgbStr($paper) }};
        --ink:   {{ $toRgbStr($ink) }};
        --crema: {{ $toRgbStr($crema) }};
        --canvas: {{ $toRgbStr($canvas) }};
        --panel:  {{ $toRgbStr($panel) }};
        --panel-muted: {{ $toRgbStr($panelMuted) }};
        --line:   {{ $toRgbStr($line) }};
        --ink-soft: {{ $toRgbStr($inkSoft) }};
    }
</style>
```

**Sourdough palette check (paper: #F5F0E8, ink: #2C2520, accent: #C4975A):**
- `--canvas` ≈ #EEE8DF — warm parchment tint (slightly darker than paper)
- `--panel` ≈ #FDFAF7 — near-white warm cream
- `--panel-muted` ≈ #E5DECE — warm beige (card surface variant)
- `--line` ≈ #D9D0C1 — warm tan border
- `--ink-soft` ≈ #877161 — warm mid-brown secondary text

All tokens remain warm and cohesive. No cold grey appears.

**Test pattern (TEST-02):**
```php
public function test_shop_branding_renders_derived_css_variables(): void
{
    $shop = Shop::create([
        'name' => 'Sourdough',
        'slug' => 'sourdough',
        'branding' => [
            'paper'  => '#F5F0E8',
            'ink'    => '#2C2520',
            'accent' => '#C4975A',
        ],
    ]);
    $category = Category::create(['shop_id' => $shop->id, 'name_en' => 'Bread']);
    Product::forceCreate([
        'shop_id' => $shop->id, 'category_id' => $category->id,
        'name_en' => 'Loaf', 'price' => 2.5,
        'is_available' => true, 'is_visible' => true,
    ]);

    Livewire::test(GuestMenu::class, ['shop' => $shop])
        ->assertSeeHtml('--canvas:')
        ->assertSeeHtml('--panel:')
        ->assertSeeHtml('--panel-muted:')
        ->assertSeeHtml('--line:')
        ->assertSeeHtml('--ink-soft:');
}
```

Note: Livewire test renders the component HTML including the layout. The `assertSeeHtml` checks for token names in the inline `<style>` block. This test verifies that branding injection emits derived tokens, not that specific RGB values are correct (that would couple the test to the algorithm).

### Pattern 4: Playfair Display Self-Hosting (GMVIZ-06)

**What:** Add Playfair Display for category `<h3>` headings. Project constraint: self-hosted in `public/fonts/` — no Google Fonts CDN link.

**Files to add to `public/fonts/`:**
- `PlayfairDisplay-VariableFont_wght.ttf` (covers weight 400-900, roman)
- `PlayfairDisplay-Italic-VariableFont_wght.ttf` (optional, italic)

Alternatively, subset .woff2 files per weight (400, 700) for smaller payload — but variable TTF follows the existing Rubik pattern and is simplest.

**`@font-face` in `app.css` (follows existing Rubik pattern):**
```css
@font-face {
    font-family: 'Playfair Display';
    src: url('/fonts/PlayfairDisplay-VariableFont_wght.ttf') format('truetype');
    font-weight: 400 900;
    font-style: normal;
    font-display: swap;
}
```

**Usage — category-specific selector, not global `font-display`:**
```css
.menu-category-header {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 1.5rem;
    font-weight: 700;
    /* ... decorative treatment at Claude's discretion */
}
```

**Important:** Do NOT change `tailwind.config.js`'s `fontFamily.display` to Playfair. That would affect all `font-display` usages site-wide (admin UI, KDS, POS). Use a dedicated CSS class scoped to the guest menu.

**RTL consideration:** In `[dir="rtl"]`, Playfair Display should fall back to `IBM Plex Sans Arabic` (existing pattern). Add:
```css
[dir="rtl"] .menu-category-header {
    font-family: 'IBM Plex Sans Arabic', 'Rubik', sans-serif;
}
```

### Pattern 5: Category Header — Already Filtered (GMVIZ-09)

**Status: ALREADY DONE in GuestMenu.php render().**

The render method already does:
```php
->filter(fn ($category) => $category->products->isNotEmpty())
```

No code change required for GMVIZ-09. This is a pure verification item for the test writer and planner.

### Anti-Patterns to Avoid

- **x-collapse plugin not available:** If `x-collapse` is not available in the Alpine version bundled with Livewire 3 on this project, use a CSS `grid-template-rows: 0fr / 1fr` transition instead. It is pure CSS, requires no plugin, and achieves smooth height transitions. Do not use `max-height` transition (causes delay/snap on close).

- **Changing font-display globally:** Applying Playfair Display to `tailwind.config.js`'s `display` font family would leak into admin, POS, and KDS views. Always scope via a dedicated CSS class.

- **Using accessor for image_url globally:** If an Eloquent accessor is added to Product model to prepend `/storage/`, verify it does not break admin pages that use `image_url` to build upload/delete paths.

- **Wire:click on card expand:** Card expand must be Alpine-only (`@click` / `x-data`). Using `wire:click` would cause a server round-trip per tap, making the menu feel sluggish on mobile.

- **Shimmer skeleton inside wire:loading:** The shimmer is for individual image loading (`@load` event on `<img>`), not for Livewire component loading. Keep them separate.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Accordion height animation | Custom JS height measurement | Alpine `x-collapse` (or CSS `grid-template-rows` transition) | Height transitions are subtle to get right across dynamic content |
| Color mixing / derivation | External color library | Inline PHP arithmetic (R+G+B mix) | No composer package needed; simple linear interpolation is sufficient and eliminates a dependency |
| Image error fallback | JavaScript load-state manager | `onerror` attribute + Alpine `x-show` | Browser native, zero JS overhead |
| Shimmer skeleton | Canvas-based or JS animation | CSS `@keyframes shimmer` (already defined in app.css as `.skeleton`) | Already exists in the codebase |

---

## Common Pitfalls

### Pitfall 1: `--canvas` and `--panel` not overridden by branding
**What goes wrong:** Even after adding derivation PHP, the inline `<style>` block is inside `@if(isset($shop))`. If the shop variable is not passed to the layout, derivation silently falls back to cold app.css defaults.
**Why it happens:** GuestMenu passes `$shop` to layout via `->layout('layouts.app', ['shop' => $this->shop])` — this is correct. But the `@if(isset($shop))` guard requires the variable name to match exactly. Confirm the guard and the variable passed both use `$shop`.
**How to avoid:** After implementing, render the guest menu for Sourdough and inspect the page `<style>` tag. It must contain `--canvas:`, `--panel:`, etc.

### Pitfall 2: Alpine state not resetting across Livewire re-renders
**What goes wrong:** When Livewire re-renders (e.g., after `addToCart`), Alpine `x-data` state on elements that Livewire morphs may be destroyed, collapsing any expanded card.
**Why it happens:** Livewire 3 uses DOM morphing (`morphdom`). Elements that are morphed in-place retain Alpine state; elements replaced entirely lose it.
**How to avoid:** Use `wire:key="{{ $product->id }}"` on each product `<article>` card. This tells Livewire to diff by key rather than position, preserving Alpine state on unchanged cards.

### Pitfall 3: `+` button click bubbles to card expand
**What goes wrong:** Tapping `+` to add to cart also triggers the card expand `@click`, causing the card to expand AND add to cart simultaneously.
**How to avoid:** Use `wire:click.stop` or Alpine `@click.stop` on the `+` button to stop event propagation.

### Pitfall 4: Livewire test not rendering layout
**What goes wrong:** `Livewire::test()` by default does NOT render the full layout (including the `<head>` with the branding `<style>` tag). `assertSeeHtml('--canvas:')` will fail.
**Why it happens:** Livewire's test helper renders only the component view, not the wrapping layout.
**How to avoid:** For TEST-02 (branding CSS variables), use a route-level test with `$this->get(route('guest.menu', $shop->slug))` instead of `Livewire::test()`. The HTTP response will include the full layout. Alternatively, render the component with layout via `Livewire::withQueryParams([...])->test(...)->html()` and assert on that. The cleanest approach for this test is an HTTP feature test.

### Pitfall 5: Sentence case in Blade (GMVIZ-03)
**What goes wrong:** Simply removing the `uppercase` CSS class does not change the stored name casing — `name_en` in the database may already be uppercase or title case.
**What it means:** D-12 is a presentation decision — render names as stored (no CSS `uppercase`). Sentence case means: do not FORCE uppercase via CSS. The names in the database should already be in the casing Sourdough wants. No `ucfirst()` or `strtolower()` PHP transform is needed in the template.
**How to avoid:** Remove the `uppercase` Tailwind class from the product name `<h4>` (or equivalent element). Do not add a text transform in PHP.

---

## Code Examples

### Existing shimmer skeleton (app.css — already available)
```css
/* Source: resources/css/app.css line 212 */
.skeleton {
    background: linear-gradient(90deg, rgb(var(--panel-muted)) 25%, rgb(var(--panel)) 50%, rgb(var(--panel-muted)) 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s ease-in-out infinite;
    border-radius: 0.5rem;
}

@keyframes shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}
```

This `.skeleton` class works immediately for image placeholders — no new CSS needed.

### Existing branding injection pattern (layouts/app.blade.php)
```blade
{{-- Source: resources/views/layouts/app.blade.php lines 18-44 --}}
@if(isset($shop))
    @php
        $branding = $shop->branding ?? [];
        $paperHex = $branding['paper'] ?? '#FDFCF8';
        $inkHex   = $branding['ink']   ?? '#1A1918';
        $cremaHex = $branding['accent'] ?? '#CC5500';
        $toRgb = function ($hex) { ... };
    @endphp
    <style>
        :root {
            --paper: {{ $toRgb($paperHex) }};
            --ink:   {{ $toRgb($inkHex) }};
            --crema: {{ $toRgb($cremaHex) }};
        }
    </style>
@endif
```

### Existing test pattern (GuestMenuTest.php)
```php
// Source: tests/Feature/Livewire/GuestMenuTest.php line 32
Livewire::test(GuestMenu::class, ['shop' => $shop])
    ->assertSee('Coffee')
    ->assertSeeHtml('class="omr-symbol"');
```

New tests follow this exact pattern — same `RefreshDatabase` trait, same `Product::forceCreate()` for tenant isolation, same `Livewire::test()` entry point.

### Category already filtered — no change needed
```php
// Source: app/Livewire/GuestMenu.php lines 1011-1021
$categories = $this->shop->categories()
    ->with(['products' => function ($query) {
        $query->where('is_visible', true)
              ->where('is_available', true)
              ...
    }])
    ->where('is_active', true)
    ->orderBy('sort_order')
    ->get()
    ->filter(fn ($category) => $category->products->isNotEmpty()); // GMVIZ-09 already satisfied
```

---

## State of the Art

| Old Approach | Current Approach | Notes |
|--------------|------------------|-------|
| One card per product: full-width, all info visible | Compact 2-col card: photo+name+price only, expand on tap | Talabat/Zomato UX pattern for scannable menus |
| `object-cover` for product images | `object-contain` | Necessary for cut-out product photos on transparent/white backgrounds |
| Category `h3` uses `font-display` (Rubik) | Category `h3` uses Playfair Display | Artisan aesthetic, pairs Playfair headings with Rubik body |
| Branding overrides only `--paper`, `--ink`, `--crema` | All 8 design tokens overridden | Eliminates cold grey bleed-through on warm-palette shops |

---

## Open Questions

1. **Alpine x-collapse availability**
   - What we know: Livewire 3 ships Alpine.js. `x-collapse` is a first-party Alpine plugin (`@alpinejs/collapse`).
   - What's unclear: Whether this project's Alpine build (bundled via Livewire 3 JS) includes `@alpinejs/collapse` already.
   - Recommendation: Check `resources/js/app.js` before implementing. If not present, use the CSS `grid-template-rows` transition fallback instead — it requires no plugin and is equally smooth.

2. **Playfair Display font file format**
   - What we know: Project uses `.ttf` variable fonts for Rubik. Google Fonts offers both `.ttf` and `.woff2` for Playfair Display.
   - What's unclear: Whether a variable `.ttf` is available for Playfair Display (it was added to Google Fonts variable font support).
   - Recommendation: Download the variable `.ttf` if available; otherwise use `.woff2` for weight 400 and 700 separately. Either format works.

3. **TEST-02 layout rendering in Livewire::test()**
   - What we know: `Livewire::test()` renders only the component view by default.
   - What's unclear: Whether the layout `<style>` block is included in the rendered output when using `->layout()`.
   - Recommendation: Use `$this->get(route('guest.menu', $shop->slug))` for TEST-02 to guarantee full layout rendering including the inline `<style>` tag. This is a standard Laravel HTTP feature test.

---

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | PHPUnit 11.5.50 |
| Config file | `phpunit.xml` (project root) |
| Quick run command | `php artisan test --filter=GuestMenu` |
| Full suite command | `composer test` |

### Phase Requirements to Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| GMVIZ-01 | Product with `image_url` renders `<img>` with `/storage/` prefix | Feature (HTTP) | `php artisan test --filter=test_product_image_url_includes_storage_prefix` | ❌ Wave 0 |
| GMVIZ-02 | `object-contain` class on image | Visual inspection | N/A (CSS class) | N/A |
| GMVIZ-03 | No `uppercase` CSS on product names | Visual inspection | N/A (CSS class removal) | N/A |
| GMVIZ-04 | 2-column grid rendered | Feature (assertSeeHtml grid class) | `php artisan test --filter=GuestMenu` | Extend existing |
| GMVIZ-05 | Description hidden by default | Visual inspection | N/A (Alpine x-show) | N/A |
| GMVIZ-06 | Playfair Display loaded | Visual inspection | N/A (font-face) | N/A |
| GMVIZ-07 | Shimmer shown during load | Visual inspection | N/A (Alpine @load) | N/A |
| GMVIZ-08 | Broken image shows fallback | Visual inspection | N/A (onerror) | N/A |
| GMVIZ-09 | Empty categories hidden | Feature (existing tests cover render) | `php artisan test --filter=GuestMenu` | ✅ Existing render test |
| GMVIZ-10 | Consistent card height | Visual inspection | N/A (CSS min-height) | N/A |
| BRND-01 | Derived CSS tokens in layout output | Feature (HTTP) | `php artisan test --filter=test_shop_branding_renders_derived_css_variables` | ❌ Wave 0 |
| BRND-02 | Background gradient uses tokens | Visual inspection | N/A | N/A |
| BRND-03 | Card surfaces reflect palette | Visual inspection | N/A | N/A |
| TEST-01 | Image URL prefix feature test | Feature (Livewire) | `php artisan test --filter=test_product_image_url_includes_storage_prefix` | ❌ Wave 0 |
| TEST-02 | Branding CSS variable feature test | Feature (HTTP) | `php artisan test --filter=test_shop_branding_renders_derived_css_variables` | ❌ Wave 0 |

### Sampling Rate
- **Per task commit:** `php artisan test --filter=GuestMenu`
- **Per wave merge:** `composer test`
- **Phase gate:** Full suite green before `/gsd:verify-work`

### Wave 0 Gaps
- [ ] `tests/Feature/Livewire/GuestMenuTest.php` — append `test_product_image_url_includes_storage_prefix` (TEST-01 / GMVIZ-01)
- [ ] `tests/Feature/GuestMenuBrandingTest.php` — new file for `test_shop_branding_renders_derived_css_variables` (TEST-02 / BRND-01) — uses HTTP `$this->get()` not `Livewire::test()` to capture full layout

---

## Sources

### Primary (HIGH confidence — direct codebase inspection)
- `resources/views/livewire/guest-menu.blade.php` — Current card structure, image rendering, category loop
- `resources/views/layouts/app.blade.php` — Current branding injection (3 tokens only)
- `resources/css/app.css` — Design token definitions, `.skeleton` class, `@keyframes shimmer`
- `app/Livewire/GuestMenu.php` — `render()` method, category filter, cart logic
- `app/Models/Product.php` — `image_url` field, no accessor defined
- `app/Models/Shop.php` — `branding` JSON cast confirmed
- `tests/Feature/Livewire/GuestMenuTest.php` — Test patterns (RefreshDatabase, forceCreate, Livewire::test)
- `tailwind.config.js` — `font-display` = Rubik (confirms Playfair must be scoped CSS class, not Tailwind config change)
- `public/fonts/` — Only Rubik TTF present; Playfair Display files not yet present
- `.planning/config.json` — `workflow.nyquist_validation: true` confirmed

### Secondary (MEDIUM confidence)
- CONTEXT.md canonical decisions — All locked decisions verified against current code

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — Verified by direct inspection; no new packages required
- Architecture: HIGH — All patterns derived from existing code; no guesswork
- Pitfalls: HIGH — Each pitfall is traceable to a specific line of existing code
- Branding derivation algorithm: MEDIUM — Algorithm is at Claude's discretion (D-17); the specific mix ratios are a recommendation, not a requirement

**Research date:** 2026-03-20
**Valid until:** 2026-04-20 (stable codebase; no external dependencies to go stale)
