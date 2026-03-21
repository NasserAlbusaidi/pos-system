# Architecture Research

**Domain:** Laravel 12 + Livewire 3 SaaS POS — v1.1 customization features
**Researched:** 2026-03-21
**Confidence:** HIGH (existing code read directly; external integrations MEDIUM)

---

## Context: What This Research Covers

Four features integrate with the existing Bite-POS architecture:

1. **Menu Themes** — 3-4 full themes (layout + colors + font pairing) with shop-level selection
2. **Custom Google Fonts** — admin types a font name, system fetches and self-hosts the woff2 file
3. **Image Optimization** — on-upload pipeline: resize + WebP conversion, keep originals
4. **Sold-Out Toggle** — manual `is_available` toggle per product, visible in guest menu

Each feature integrates differently. Themes and fonts extend the existing branding cascade. Image optimization hooks into `ProductManager`. Sold-out toggle is partially built (exists in POS dashboard) but not exposed in the catalog admin or guest menu.

---

## Existing Architecture Relevant to These Features

### Branding Cascade (themes touch this directly)

The branding system lives entirely in `layouts/app.blade.php`. When a `$shop` is passed to the layout, PHP generates inline CSS custom properties from three hex colors (`paper`, `ink`, `accent`). The derived token set (`--canvas`, `--panel`, `--panel-muted`, `--line`, `--ink-soft`) is computed via linear RGB interpolation.

```
Shop.branding JSON
  { paper: "#FDF...", ink: "#1A1...", accent: "#CC5..." }
         ↓
  layouts/app.blade.php (PHP inline <style> block)
         ↓
  :root { --paper: R G B; --ink: R G B; --crema: R G B; ... }
         ↓
  All CSS references rgb(var(--paper)), rgb(var(--ink)), etc.
```

There are no per-feature stylesheets — all CSS custom properties are on `:root`. Themes will extend this pattern.

### Image Storage (image optimization touches this directly)

`ProductManager` uses `Livewire\WithFileUploads`. On save, the uploaded file is stored via:

```php
$imageUrl = $this->image->store('products', 'public');
// Stores raw uploaded file to storage/app/public/products/{random}.jpg
// Returns path like "products/abc123.jpg"
// Served at /storage/products/abc123.jpg
```

The `products.image_url` column stores this path. No transformation happens — raw file is stored as-is.

### Product Availability (sold-out toggle partially built)

`is_available` column exists on `products`. Toggle logic (`toggle86`) exists in `PosDashboard.php` — it's a kitchen/POS-facing 86 button. What does NOT exist yet:

- Toggle in `ProductManager` (the catalog admin)
- "Sold out" visual indicator in `guest-menu.blade.php`
- Products filtered to `is_available = true` in `GuestMenu::render()` (they are, but no badge shown to guests)

The data model and POS toggle are already correct. The gap is: a second toggle surface in the catalog, and a guest-facing indicator.

---

## System Overview: How the Four Features Fit

```
┌────────────────────────────────────────────────────────────────┐
│                    Admin (ShopSettings)                        │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐ │
│  │ Theme Picker │  │  Font Input  │  │ Product Avail Toggle │ │
│  │ (new UI)     │  │  (new UI)    │  │ (new in ProductMgr)  │ │
│  └──────┬───────┘  └──────┬───────┘  └──────────┬───────────┘ │
│         │                 │                      │             │
│  Shop.branding JSON (extended)          Product.is_available   │
└─────────┼─────────────────┼────────────────────────────────────┘
          │                 │
          ↓                 ↓
┌────────────────────────────────────────────────────────────────┐
│                  layouts/app.blade.php                         │
│  Branding cascade reads: paper, ink, accent (existing)         │
│  NEW: also reads theme slug → injects theme CSS class          │
│  NEW: also reads font_family → outputs @font-face block        │
└─────────────────────────────┬──────────────────────────────────┘
                              ↓
┌────────────────────────────────────────────────────────────────┐
│                    GuestMenu component                         │
│  Renders with data-theme="[slug]" on root div                  │
│  Shows "sold out" badges for is_available=false items          │
│  (is_available=false items are hidden from cart, shown greyed) │
└────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────┐
│               ProductManager (image upload path)               │
│  WithFileUploads → ImageOptimizationService (NEW)              │
│  → resize to 800px max + encode WebP → store optimized         │
│  → store original at products/originals/{name}                 │
│  → image_url stores WebP path                                  │
└────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────┐
│                 FontFetchService (NEW)                         │
│  Admin inputs font name → HTTP request to Google Fonts CSS2 API│
│  → parse woff2 URL from CSS response                           │
│  → download woff2 to public/fonts/{slug}.woff2                 │
│  → persist {font_family, font_path} to Shop.branding           │
└────────────────────────────────────────────────────────────────┘
```

---

## Feature 1: Menu Themes

### Recommended Pattern: data-theme CSS classes

Use `data-theme="[slug]"` on the guest menu root `<div>`, not on `:root`. Each theme overrides only the tokens it changes. Brand colors (`--paper`, `--ink`, `--crema`) layer on top via the existing inline `<style>` block which always wins due to specificity.

```css
/* resources/css/themes/classic.css  (no override = uses branding cascade) */
[data-theme="classic"] {
    /* layout-specific tokens only */
    --card-radius: 8px;
    --card-padding: 1.25rem;
}

/* resources/css/themes/modern.css */
[data-theme="modern"] {
    --card-radius: 0px;
    --card-padding: 1.5rem;
    --font-display: 'Inter', sans-serif;
    --font-body: 'Inter', sans-serif;
}

/* resources/css/themes/warm.css */
[data-theme="warm"] {
    --card-radius: 16px;
    --card-padding: 1rem;
    --font-display: 'Playfair Display', serif;
}
```

The inline `:root` block in `app.blade.php` sets brand colors last (inline style > stylesheet), so branding always overrides theme defaults. This is the correct cascade order.

### Schema Change

Add `theme` key to the existing `Shop.branding` JSON column. No new migration needed.

```php
// Shop.branding JSON — extended
{
  "paper": "#FDFCF8",
  "ink": "#1A1918",
  "accent": "#CC5500",
  "theme": "classic",         // NEW: "classic" | "modern" | "warm" | "bold"
  "font_family": null,        // NEW: null = use theme default
  "font_path": null           // NEW: public/fonts/{slug}.woff2 path
}
```

### Layout Change

`layouts/app.blade.php` passes `$branding['theme']` to the guest-menu div via the layout's `$slot`, or more practically, the `GuestMenu` component adds `data-theme` directly to its root element:

```html
{{-- guest-menu.blade.php root div --}}
<div class="guest-menu-bg ..."
     data-theme="{{ $shop->branding['theme'] ?? 'classic' }}">
```

### New Files

- `resources/css/themes/` — one CSS file per theme, imported into `app.css`
- No new PHP classes — theme is a CSS-only concern once the slug is in `branding`

### Modified Files

- `ShopSettings.php` — add `$theme` property, save to `branding`
- `resources/views/livewire/shop-settings.blade.php` — theme picker UI (radio cards)
- `resources/views/livewire/guest-menu.blade.php` — add `data-theme` attribute
- `resources/css/app.css` — import theme CSS files

---

## Feature 2: Custom Google Fonts

### Recommended Pattern: FontFetchService

A dedicated service handles the network operation, file write, and branding update. This keeps `ShopSettings` thin and makes the font fetching testable in isolation.

```
Admin types "Lora" in ShopSettings
         ↓
ShopSettings::saveFont() calls FontFetchService::fetch("Lora", $shop)
         ↓
FontFetchService:
  1. Build CSS2 API URL:
     https://fonts.googleapis.com/css2?family=Lora:wght@400;700&display=swap
     (HTTP GET with a modern User-Agent header to get woff2 instead of ttf)
  2. Parse CSS response — extract woff2 URL(s) from @font-face blocks
  3. Download woff2 file via HTTP
  4. Save to public/fonts/shop-{$shop->id}-{$slug}.woff2
  5. Return ['font_family' => 'Lora', 'font_path' => '/fonts/shop-1-lora.woff2']
         ↓
ShopSettings merges into Shop.branding
         ↓
layouts/app.blade.php reads branding.font_path:
  If set → outputs @font-face { font-family: "...", src: url("...") }
  This overrides the theme's default font
```

### Why CSS2 API (not Developer API)

The Developer API requires an API key and returns TTF URLs, not woff2. The CSS2 API (`fonts.googleapis.com/css2`) returns woff2 URLs when called with a modern `User-Agent` header (e.g., `Chrome/120`). No API key needed for CSS2. The CSS response is ~10 lines and trivially parseable with a regex.

```php
// FontFetchService — core logic sketch
$css = Http::withHeaders([
    'User-Agent' => 'Mozilla/5.0 (compatible; Bite-POS/1.1; Chrome/120)'
])->get("https://fonts.googleapis.com/css2?family={$encoded}:wght@400;700&display=swap")
  ->body();

// Extract first woff2 URL
preg_match('/url\((https:\/\/fonts\.gstatic\.com\/[^)]+\.woff2)\)/', $css, $m);
$woff2Url = $m[1] ?? null;
```

### Validation and Constraints

- Font name is free-form but must match a Google Fonts family name exactly
- Validation: attempt the HTTP request; if 400 or empty CSS, return error
- File naming: `public/fonts/shop-{id}-{slug}.woff2` where slug = `Str::slug($fontFamily)`
- Collision: overwrite on re-save (same shop, new font replaces old file)
- Fallback: if `font_path` is null, the theme's default font applies
- Max one custom font per shop (sufficient for v1.1; matches the "admin types a name" spec)

### New Files

- `app/Services/FontFetchService.php`

### Modified Files

- `ShopSettings.php` — add `$customFontFamily` property, call FontFetchService on save
- `resources/views/livewire/shop-settings.blade.php` — font name input + current font display
- `layouts/app.blade.php` — read `branding.font_path` → output `@font-face` block

---

## Feature 3: Image Optimization

### Recommended Pattern: ImageOptimizationService, called from ProductManager

The upload path in `ProductManager::save()` currently does:

```php
$imageUrl = $this->image->store('products', 'public');
```

Replace this with a service call that processes the image before storing:

```php
$imageUrl = app(ImageOptimizationService::class)->store($this->image, 'products');
```

### ImageOptimizationService Responsibilities

```
Input: Livewire TemporaryUploadedFile
Output: path string (e.g. "products/abc123.webp")

Steps:
1. Read image via Intervention Image (GD driver — confirmed available)
2. Scale down: if width > 800px OR height > 800px, scaleDown(800, 800)
   (scaleDown preserves aspect ratio, never upscales)
3. Encode as WebP quality 82
4. Store optimized at storage/app/public/products/{hash}.webp
5. Store original at storage/app/public/products/originals/{hash}.{ext}
   (keep original for potential re-processing later)
6. Return "products/{hash}.webp" path
```

### Why Intervention Image v3 (GD driver)

- GD is confirmed available on this machine (`php -m | grep gd` returned `gd`)
- PHP 8.2 is supported (requires PHP >= 8.1)
- `intervention/image-laravel` is the official Laravel integration package
- Imagick would be better for large images but is not confirmed installed; GD is reliable
- 800px max dimension is appropriate for mobile-first restaurant menus (Retina: 400px display width)

### Installation

```bash
composer require intervention/image-laravel
php artisan vendor:publish --provider="Intervention\Image\Laravel\ServiceProvider"
```

Config file `config/image.php` uses GD by default — no config change needed.

### WebP Quality Trade-Off

Quality 82 is the sweet spot for food photography:
- 82 preserves enough JPEG artifact detail in food photos to look sharp
- Typically 60-75% smaller than original JPEG
- Quality 75 is too lossy for product cards where texture matters

### Original Preservation

Originals are stored under `products/originals/` so re-optimization (e.g., higher quality later) is always possible without asking the shop owner to re-upload. This is a one-way-door prevention strategy.

### New Files

- `app/Services/ImageOptimizationService.php`

### Modified Files

- `ProductManager.php` — replace `$this->image->store(...)` with service call (both create and edit paths)
- `OnboardingWizard.php` — same replacement (also uses `WithFileUploads` for product images)
- `composer.json` — add `intervention/image-laravel`

---

## Feature 4: Sold-Out Toggle in Catalog Admin

### Current State

The `is_available` toggle already exists in `PosDashboard` (the `toggle86` method). It is POS-staff facing — designed for real-time 86ing during service. What is missing:

1. Toggle in `ProductManager` — the catalog admin (admin/manager facing, persistent configuration)
2. "Sold out" badge in `guest-menu.blade.php` — so guests can see unavailable items as greyed/labelled

### Recommended Pattern: Expose toggle in ProductManager product list

The catalog toggle is semantically different from the POS toggle (pre-service vs. during-service), but the underlying operation is identical: `product->update(['is_available' => !$product->is_available])`. Rather than a new service, add a `toggleAvailability(int $productId)` method to `ProductManager`.

Tenant-safety: same pattern as `toggle86` — always scope the query to `shop_id`.

```php
public function toggleAvailability(int $productId): void
{
    $product = Product::where('shop_id', $this->shop->id)
        ->findOrFail($productId);

    $product->update(['is_available' => ! $product->is_available]);

    $this->dispatch('toast',
        message: $product->is_available ? "{$product->name_en} is now available." : "{$product->name_en} marked as sold out.",
        variant: $product->is_available ? 'success' : 'warning'
    );
}
```

### Guest Menu: Show Unavailable Items as Greyed

Currently `GuestMenu::render()` filters `is_available = true`:

```php
->where('is_available', true)
```

The v1.1 UX decision: show unavailable items as greyed with "Sold Out" label, but block adding to cart. This is better UX than hiding them (guests can see the item exists, understand it's unavailable). Change the query to show all visible products regardless of availability, then handle the "add to cart" guard in `addToCart()` (which already filters by `is_available`).

```php
// In render(), change:
->where('is_available', true)
// To:
// (remove this filter — show all visible products)
// addToCart() already guards: ->where('is_available', true)->find($productId)
```

The guest-menu card then shows a "Sold Out" overlay when `!$product->is_available`.

### Modified Files

- `ProductManager.php` — add `toggleAvailability()` method
- `resources/views/livewire/product-manager.blade.php` — add toggle button in product list row
- `GuestMenu::render()` — remove `is_available` filter from the `products` query
- `resources/views/livewire/guest-menu.blade.php` — sold-out visual treatment on product cards

---

## Component Responsibilities

| Component | Responsibility | New vs Existing |
|-----------|---------------|-----------------|
| `ShopSettings.php` | Branding config, theme selection, font name input | Modified |
| `FontFetchService.php` | Fetch, download, store Google Font woff2 file | New |
| `ImageOptimizationService.php` | Resize + WebP encode uploaded product images | New |
| `ProductManager.php` | Product CRUD + availability toggle | Modified |
| `GuestMenu.php` | Guest ordering — show sold-out items | Modified |
| `layouts/app.blade.php` | Emit theme data-attr, custom @font-face block | Modified |
| `resources/css/themes/` | Per-theme CSS token overrides | New (CSS files) |

---

## Data Flow Changes

### Theme Selection

```
Admin selects theme in ShopSettings
    → ShopSettings::save() merges {theme: "warm"} into branding JSON
    → Shop.branding updated in DB
    → Guest visits /menu/{slug}
    → GuestMenu renders with data-theme="warm" on root div
    → theme CSS overrides kick in; branding inline :root block overrides colors
```

### Font Fetching

```
Admin types "Lora" → clicks Save
    → ShopSettings::saveFont() called
    → FontFetchService::fetch("Lora", $shop)
        → HTTP GET fonts.googleapis.com/css2?family=Lora:wght@400;700
        → Parse woff2 URL from CSS
        → HTTP GET fonts.gstatic.com/.../lora-400.woff2
        → Write to public/fonts/shop-1-lora.woff2
        → Return {font_family: "Lora", font_path: "/fonts/shop-1-lora.woff2"}
    → ShopSettings merges into branding JSON
    → Guest visits /menu/{slug}
    → layouts/app.blade.php detects branding.font_path
    → Outputs: @font-face { font-family: "Lora"; src: url("/fonts/shop-1-lora.woff2") }
    → CSS overrides --font-display / --font-body with "Lora"
```

### Image Upload

```
Admin uploads product image in ProductManager
    → Livewire WithFileUploads stores temp file
    → ProductManager::save() calls ImageOptimizationService::store($image, 'products')
    → ImageOptimizationService:
        → Read temp file with Intervention Image
        → scaleDown(800, 800) preserving aspect ratio
        → encode as WebP quality 82
        → store to storage/app/public/products/{hash}.webp
        → store original to storage/app/public/products/originals/{hash}.jpg
        → return "products/{hash}.webp"
    → Product::image_url = "products/{hash}.webp"
    → Served at /storage/products/{hash}.webp
```

### Sold-Out Toggle

```
Admin clicks toggle in ProductManager
    → ProductManager::toggleAvailability($productId) fires
    → Product::update(['is_available' => !$current])
    → Toast dispatched

Guest views /menu/{slug}
    → GuestMenu::render() fetches all is_visible products (is_available no longer filtered)
    → Blade shows sold-out overlay on cards where !$product->is_available
    → addToCart() still guards: only available products can be added
```

---

## Architectural Patterns

### Pattern 1: Branding JSON as the Single Config Bag

**What:** All per-shop presentation config (colors, theme, font) lives in one JSON column on `Shop`. No separate tables.

**When to use:** When config keys are numerous but each shop has at most one value per key. Avoids table proliferation.

**Trade-offs:** Adding a new branding key is zero-migration — just read/write a new key in the JSON. The downside is no FK constraints on JSON values, so validation must happen in PHP (already done in `ShopSettings::save()`).

**For v1.1:** Add `theme`, `font_family`, `font_path` to the JSON bag. No migration.

### Pattern 2: CSS Custom Properties as the Theme Interface

**What:** Themes only override CSS custom properties. No theme-specific HTML structure. The theme `data-` attribute is on the outermost guest-menu div; theme CSS uses attribute selectors.

**When to use:** When themes share layout and differ only in spacing, radius, fonts, and default colors.

**Trade-offs:** Cannot change fundamental layout (grid columns, header structure) via a theme. For v1.1's 3-4 themes, this is fine — the difference is visual polish, not layout restructuring.

**Example:**
```css
[data-theme="bold"] {
    --card-radius: 0;
    --font-display: 'Barlow Condensed', sans-serif;
    --font-body: 'Barlow', sans-serif;
}
```

### Pattern 3: Service Extraction for External I/O

**What:** Any operation involving HTTP requests or filesystem writes outside the standard upload path lives in a dedicated Service class, not directly in the Livewire component.

**When to use:** Font fetching, image processing, any operation that should be independently testable or reusable.

**Trade-offs:** Slightly more files. The gain is: Livewire components stay thin, services are mockable in tests, error handling is isolated.

---

## Anti-Patterns

### Anti-Pattern 1: Theme as a Separate DB Table

**What people do:** Create a `shop_themes` table with rows per theme definition.

**Why it's wrong:** Themes are CSS files deployed with the code, not database content. A table just duplicates what's already in the CSS. Adds migration and query overhead for a static enum.

**Do this instead:** Store only the selected slug in `branding.theme`. The CSS files are the source of truth.

### Anti-Pattern 2: Font Downloads in the Livewire Component

**What people do:** Put the HTTP client call and file write directly in `ShopSettings::save()`.

**Why it's wrong:** Makes the component untestable (real HTTP in Livewire component), bloats the method, and means any failure (network timeout, bad font name) is not handled in isolation.

**Do this instead:** `FontFetchService` with a clear interface. `ShopSettings` calls it and handles the returned error/success.

### Anti-Pattern 3: Filtering Unavailable Products Before Rendering

**What people do:** Keep `->where('is_available', true)` in `GuestMenu::render()`.

**Why it's wrong:** Guests can't see that an item exists but is sold out. They might not know to ask. Hiding items creates confusion ("I saw this on the PDF menu but it's not in the app").

**Do this instead:** Show the item grayed with a "Sold Out" badge. The `addToCart()` guard prevents adding it. Best practice for restaurant digital menus.

### Anti-Pattern 4: Replacing Existing Image URLs on Re-optimization

**What people do:** Overwrite the same filename when re-processing images.

**Why it's wrong:** Browser cache will serve the old image. Storage path collisions can corrupt data.

**Do this instead:** Always generate a new hash-based filename. Keep originals at `products/originals/` with their own hash. Old images are orphaned (cheap storage) rather than silently replaced.

---

## Build Order

Dependencies between the four features determine the correct build order:

```
1. Sold-Out Toggle (product catalog + guest display)
   — Smallest scope, no new dependencies
   — Unblocks: demo shows are accurate before adding themes

2. Image Optimization
   — Add intervention/image-laravel, new service
   — No dependencies on themes or fonts
   — Can go in parallel with #1, but done second for safety

3. Menu Themes
   — CSS-only (no new PHP package)
   — Needs to be built AFTER image opt so product cards in themes look correct with WebP images
   — Touches layout/app.blade.php and ShopSettings — do after other features to avoid merge conflicts

4. Custom Google Fonts
   — Depends on themes being done (font overrides need the --font-display/--font-body variables set by themes)
   — New service, new UI in ShopSettings
   — External HTTP dependency — most likely to need retries or error handling iteration
```

**Rationale for this order:**
- Sold-out toggle is low-risk and immediately valuable for the Sourdough demo
- Image optimization is self-contained and improves all subsequent demo screenshots
- Themes must precede fonts because theme CSS defines the font token variables that the custom font override will target
- Fonts last because they have the most moving parts (external HTTP, file writes, CSS injection)

---

## Integration Points

### External Services

| Service | Integration Pattern | Notes |
|---------|---------------------|-------|
| Google Fonts CSS2 API | HTTP GET with modern User-Agent, regex parse CSS response | No API key required for CSS2. Rate limit: not documented; one request per save is safe. Confidence: MEDIUM (CSS2 format is stable but Google can change woff2 URL pattern) |
| fonts.gstatic.com | HTTP GET to download woff2 binary | Same request context as CSS2 fetch. Store to `public/fonts/` — served directly, no auth |
| Intervention Image (GD) | PHP library, synchronous, in-process | GD confirmed available. 800px resize + WebP encode is fast enough synchronously (< 500ms for typical food photo) |

### Internal Boundaries

| Boundary | Communication | Notes |
|----------|---------------|-------|
| ShopSettings ↔ FontFetchService | Direct `app()` call, returns array or throws | FontFetchService should throw typed exceptions for "font not found" vs "network error" |
| ProductManager ↔ ImageOptimizationService | Direct `app()` call, returns path string | Same pattern as existing service calls in ShopSettings |
| GuestMenu render ↔ Product availability | Eloquent query change — remove `is_available` filter | addToCart() guard remains unchanged, preventing unavailable items from entering cart |
| layouts/app.blade.php ↔ Shop.branding | Direct array read — `$branding['theme']`, `$branding['font_path']` | Already the pattern for paper/ink/accent |

---

## Scaling Considerations

| Scale | Architecture Adjustments |
|-------|--------------------------|
| 0-100 shops | Synchronous image processing in-request is fine; font downloads are one-off admin actions |
| 100-1k shops | Move image optimization to a queued job (Laravel Queue + database driver). Font fetching is already infrequent; queue when > 50 simultaneous admin saves |
| 1k+ shops | CDN for product images (swap storage disk to S3 + CloudFront), deduplicate shared font files |

For v1.1, synchronous processing is the right call. The queue infrastructure exists (it's used elsewhere) but the load does not justify it yet.

---

## Sources

- Existing codebase: `app/Livewire/GuestMenu.php`, `app/Livewire/ProductManager.php`, `app/Livewire/ShopSettings.php`, `resources/views/layouts/app.blade.php` — read directly (HIGH confidence)
- [Intervention Image v3 Installation](https://image.intervention.io/v3/getting-started/installation) — official docs (HIGH confidence)
- [Google Fonts CSS2 API](https://developers.google.com/fonts/docs/css2) — official docs (HIGH confidence for API format; MEDIUM for woff2 URL stability)
- [Google Fonts Developer API](https://developers.google.com/fonts/docs/developer_api) — official docs, confirms API key requirement (HIGH confidence)
- [CSS custom properties theming pattern](https://www.frontendtools.tech/blog/css-variables-guide-design-tokens-theming-2025) — community (MEDIUM confidence, confirmed by existing codebase pattern)
- PHP GD extension: confirmed present on this machine (`php -m | grep gd`)

---

*Architecture research for: Bite-POS v1.1 — Menu Themes, Custom Fonts, Image Optimization, Sold-Out Toggle*
*Researched: 2026-03-21*
