# Stack Research

**Domain:** SaaS POS — v1.1 Customization & Polish (menu themes, Google Fonts, image optimization, sold-out toggle)
**Researched:** 2026-03-21
**Confidence:** HIGH

## Context: What Already Exists

This is a subsequent-milestone research document. The following are already in production and do NOT need re-evaluation:

- Laravel 12 + Livewire 3 (full-stack, no separate frontend)
- Vanilla CSS with CSS custom properties (design tokens via `--paper`, `--ink`, `--crema`, etc.)
- Branding cascade: pure PHP in `resources/views/layouts/app.blade.php` — reads `shop.branding` JSON, derives 8 CSS tokens via linear RGB interpolation, emits inline `<style>` block
- Self-hosted fonts: Rubik (variable TTF), Playfair Display (woff2) in `public/fonts/`
- JetBrains Mono + IBM Plex Sans Arabic loaded via Google Fonts CDN in `app.css` (currently not self-hosted)
- `products.is_available` column (boolean, default true) exists in MySQL
- `PosDashboard` already implements the `toggleAvailability` action against `is_available`
- Laravel's `WithFileUploads` (Livewire) already handles product image uploads via `$image->store('products', 'public')`

---

## Feature 1: Image Optimization (Resize + WebP on Upload)

### Recommended Addition

| Library | Version | Purpose | Why |
|---------|---------|---------|-----|
| `intervention/image` | `^3.11` | PHP image manipulation — resize, convert to WebP | Stable, PHP 8.1+, works with GD or Imagick; WebP encode via `toWebp(quality)` confirmed in v3 docs |
| `intervention/image-laravel` | `^1.5` | Laravel service provider + facade for intervention/image | Provides `ImageManager` DI, config publish, response macros; latest 1.5.8 (2026-03-20) supports L8–L13 |

**v4 beta is explicitly excluded:** v4 requires PHP 8.3+; this project targets PHP 8.2. Stay on v3.

### Driver Decision: GD vs Imagick

Use **GD** as the default driver:

- GD is bundled with PHP (`php8.x-gd` package) on all common Ubuntu/Debian hosting stacks
- WebP support in GD is compiled in on modern Ubuntu LTS (22.04+) packages (`php8.2-gd` includes libwebp by default on Ubuntu 22.04+)
- Imagick produces marginally better quality but adds a non-standard system dependency (`libmagickcore`) — over-engineering for resize-and-compress use case
- Intervention v3 provides runtime `supports()` check so a graceful fallback can be coded if needed

Configure with a runtime capability check:

```php
// config/image.php (after vendor:publish)
'driver' => Intervention\Image\Drivers\Gd\Driver::class,
```

Add a `GD_WEBP_SUPPORTED` check in the image service: if GD reports WebP unsupported, fall back to storing JPEG. This prevents silent failures on edge-case hosting.

### Integration Point

Current flow in `ProductManager.php` (line 120):
```php
$imageUrl = $this->image->store('products', 'public');
```

Replace with a call to a new `ImageOptimizationService`:
```php
$imageUrl = app(ImageOptimizationService::class)->processUpload($this->image);
```

The service:
1. Reads the uploaded temp file with `ImageManager::make()`
2. Scales to max 800px wide (preserves aspect ratio, never upscales)
3. Encodes to WebP at quality 82
4. Stores as `products/{uuid}.webp` in the `public` disk
5. Returns the path string (drop-in replacement for the existing `$imageUrl`)

Keep originals: Livewire `TemporaryUploadedFile` is discarded after processing — no separate storage needed.

### Installation

```bash
composer require intervention/image:^3.11 intervention/image-laravel:^1.5
php artisan vendor:publish --provider="Intervention\ImageServiceProvider"
```

---

## Feature 2: Custom Google Fonts (Admin Types Name, System Self-Hosts)

### No New Library Required

Use Laravel's built-in HTTP client (already a framework dependency) + the Google Fonts CSS API v2.

**Pattern:**

1. Admin types a font name (e.g., "Lora") into the shop settings form
2. A new `GoogleFontService` fires `Http::withHeaders(['User-Agent' => 'Mozilla/5.0 ...'])->get('https://fonts.googleapis.com/css2?family={name}&display=swap')`
3. Parse the CSS response with a regex to extract all `src: url(...)` woff2 links
4. Download each woff2 file using `Http::sink($localPath)->get($url)` — streams directly to disk, no memory spike
5. Generate a `@font-face` CSS snippet and store it in `storage/app/public/fonts/shops/{shop_id}/{family-slug}.css`
6. Store the font slug in `shop.branding['custom_font']`

**Why no third-party library:**

- The Google Fonts CSS2 API is stable and well-documented
- The CSS format is predictable: `src: url(https://fonts.gstatic.com/s/...)` woff2 entries
- Laravel `Http::sink()` handles streaming to disk natively (available since Laravel 8)
- Zero new dependencies, fewer supply-chain attack surfaces

**User-Agent requirement (MEDIUM confidence):** Google Fonts serves woff2 (vs older formats) when a modern browser User-Agent is sent. Sending `Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36` reliably returns woff2. This is a widely-documented technique.

### Schema Change

Add `custom_font` key to the existing `shop.branding` JSON column — no migration needed:

```json
{
  "paper": "#FDFCF8",
  "ink": "#1A1918",
  "accent": "#CC5500",
  "custom_font": "lora",
  "custom_font_display": "Lora"
}
```

### Integration Point

In `resources/views/layouts/app.blade.php`, after the existing `<style>` block:

```blade
@if(!empty($branding['custom_font']))
    <link rel="stylesheet" href="{{ asset('storage/fonts/shops/' . $shop->id . '/' . $branding['custom_font'] . '.css') }}">
    <style>
        :root { --font-display: '{{ $branding['custom_font_display'] }}', 'Playfair Display', Georgia, serif; }
        .font-display { font-family: var(--font-display); }
    </style>
@endif
```

The existing `app.css` declares `Playfair Display` as the `font-display` / `font-display` Tailwind config — the custom font overrides it via cascade priority.

**Font validation:** Before downloading, validate the font name by checking the HTTP response code (404 = not found on Google Fonts). Return a user-facing error if invalid.

---

## Feature 3: Menu Themes (3-4 Full Themes with Layout + Colors + Fonts)

### No New Library Required

Pure CSS custom properties + a data attribute on the root element. This is the standard browser-native pattern.

**Pattern:**

```css
/* In app.css or a dedicated themes.css */
[data-theme="bakery"] {
    --font-display: 'Playfair Display', Georgia, serif;
    --font-sans: 'Rubik', system-ui, sans-serif;
    --paper: 247 242 233;
    --ink: 40 28 18;
    --crema: 186 108 58;
    /* layout variant token */
    --card-radius: 0.75rem;
    --header-style: 'condensed';
}

[data-theme="modern"] {
    --font-display: 'Inter', system-ui, sans-serif;
    --font-sans: 'Inter', system-ui, sans-serif;
    --paper: 248 250 252;
    --ink: 15 23 42;
    --crema: 99 102 241;
    --card-radius: 0.25rem;
}
```

In `app.blade.php`, emit `data-theme` on `<html>`:

```blade
<html data-theme="{{ $branding['theme'] ?? 'default' }}" ...>
```

Brand color overrides emitted in the inline `<style>` block still apply — they cascade on top of the theme defaults because they're in `:root` (same specificity, later in document order).

**Why not a JS theme library:** The existing system is pure CSS custom properties with PHP-emitted inline styles. Adding a JS-driven theme library (e.g., Panda CSS, Stitches) would require a JS build step change, a Vite config change, and Livewire interop complexity — none of which are justified for 3-4 static themes.

### Schema Change

Add `theme` key to `shop.branding` JSON:

```json
{ "theme": "bakery" }
```

No migration. The `branding` column is already `JSON` / nullable.

### Theme Count Recommendation

Build exactly 3 themes for v1.1:

| Theme ID | Character | Best For |
|----------|-----------|----------|
| `default` | Warm artisan (current Sourdough palette) | Bakeries, cafes |
| `modern` | Clean, slate-based neutrals | Fast casual, tech-forward |
| `dark` | Dark ink canvas with bright accent | Evening dining, bars |

A 4th theme can ship in v1.2 once client feedback is collected. Building 4+ upfront without user signal is waste.

---

## Feature 4: Sold-Out Toggle (Manual per Product)

### No New Library, No New Column

`products.is_available` (boolean, default `true`) already exists. The POS dashboard already calls `$product->update(['is_available' => !$product->is_available])`.

What's missing is **surface area**, not infrastructure:

1. **Guest menu**: Currently filters out unavailable products (`->where('is_available', true)` — GuestMenu.php lines 492, 672, 856, 1014). Change to: load all visible products, display unavailable ones with a "Sold Out" overlay badge, disable tap-to-add interaction.

2. **Menu builder / admin**: Expose the toggle in `ProductManager` UI so admins can set availability from the catalog management screen (not just from the POS).

**No schema changes. No new dependencies.**

---

## Recommended Stack: Summary Table

### Core Technologies (Existing — No Change)

| Technology | Version | Status |
|------------|---------|--------|
| Laravel | 12.x | Existing |
| Livewire | 3.6.x | Existing |
| Vite + laravel-vite-plugin | 7.x / 2.x | Existing |
| MySQL 8.0 | 8.0 | Existing |
| CSS Custom Properties | Native | Existing |
| Laravel HTTP Client | Bundled with L12 | Existing — used for Google Font download |

### New PHP Dependencies

| Library | Version | Purpose | Installation |
|---------|---------|---------|-------------|
| `intervention/image` | `^3.11` | Image resize + WebP encode | `composer require intervention/image:^3.11` |
| `intervention/image-laravel` | `^1.5` | Laravel integration layer | `composer require intervention/image-laravel:^1.5` |

### No New JS Dependencies

All theme switching, sold-out display, and font loading are CSS/PHP-only. The existing Vite + Alpine.js setup is unchanged.

---

## Installation

```bash
# Image optimization
composer require intervention/image:^3.11 intervention/image-laravel:^1.5
php artisan vendor:publish --provider="Intervention\ImageServiceProvider"
```

After publishing, update `config/image.php`:

```php
'driver' => \Intervention\Image\Drivers\Gd\Driver::class,
```

GD is already available if PHP was installed via `apt` on Ubuntu 22.04+ (php8.4-gd is included). Verify WebP support:

```bash
php -r "echo gd_info()['WebP Support'] ? 'WebP OK' : 'WebP MISSING';"
```

---

## Alternatives Considered

| Recommended | Alternative | Why Not |
|-------------|-------------|---------|
| `intervention/image` v3 | `intervention/image` v4 beta | v4 requires PHP 8.3+; this project runs PHP 8.4 (dev) but targets 8.2 minimum; v4 API is still unstable |
| Laravel HTTP facade for font download | `majodev/google-webfonts-helper` (third-party API) | External service dependency; the helper API has availability risk; Google Fonts CSS API is first-party and stable |
| CSS `data-theme` attribute pattern | JS-driven theme library (Panda CSS, Stitches) | Introduces JS build complexity, breaks Livewire SSR expectations, and is architecturally inconsistent with the existing pure-CSS token system |
| GD driver | Imagick driver | Imagick requires additional system package (`php8.x-imagick` + `libmagickcore`), unnecessary for resize+WebP use case; GD is simpler and universally available |
| Extend `shop.branding` JSON | New `shop_themes` or `shop_fonts` table | The `branding` JSON column already handles arbitrary shop config; a new table adds join complexity for one-to-one data |

---

## What NOT to Use

| Avoid | Why | Use Instead |
|-------|-----|-------------|
| `spatie/image` | Wraps Imagick/GD but adds another abstraction layer; heavier than needed | `intervention/image` v3 — purpose-built for this use case |
| `cloudinary/cloudinary_php` | Adds external CDN dependency, monthly cost, network latency for Oman | On-server processing with intervention/image |
| Google Fonts CDN link tags for custom fonts | Leaks visitor IPs to Google; GDPR concern; fonts flicker on first load | Self-host via `GoogleFontService` pattern described above |
| Tailwind theme variants (`dark:`, custom variants) | The project explicitly bans Tailwind for feature CSS; Tailwind deps in package.json are Breeze scaffolding leftovers | CSS `[data-theme]` attribute selectors with custom properties |
| Multiple `@font-face` declarations in `app.css` for custom fonts | Custom fonts are per-shop and unknown at build time; they cannot be in a compiled CSS bundle | Runtime-generated CSS files stored in `storage/app/public/fonts/shops/` |

---

## Version Compatibility

| Package | Compatible With | Notes |
|---------|-----------------|-------|
| `intervention/image:^3.11` | PHP 8.1–8.4 | Confirmed. v4 requires PHP 8.3+; avoid v4 until project locks to 8.3+ |
| `intervention/image-laravel:^1.5` | Laravel 8–13 | Confirmed via Packagist (latest 1.5.8, 2026-03-20) |
| GD driver + WebP | PHP 8.2+ on Ubuntu 22.04+ | WebP is compiled into `php8.x-gd` on Ubuntu 22.04 LTS and later; verify with `gd_info()` at deploy |

---

## Stack Patterns by Variant

**If hosting on shared PHP (cPanel, Plesk):**
- WebP support in GD is not guaranteed; code the fallback to JPEG/PNG explicitly
- Check `gd_info()['WebP Support']` at upload time; if false, store as JPEG at quality 85 instead

**If the custom font name contains spaces:**
- Normalize to kebab-case for the filename: `"Playfair Display"` → `playfair-display.css`
- The CSS font-family name is preserved as-is in the `@font-face` declaration

**If a shop selects a theme AND has brand color overrides:**
- Theme sets the default token values via `[data-theme]` selector
- Brand overrides are injected in `:root` via the existing inline `<style>` block in `app.blade.php`
- `:root` specificity equals `[data-theme]` specificity — but `:root` overrides appear later in the document, so they win. This is intentional: brand colors always beat theme defaults.

---

## Sources

- Intervention Image v3 official docs (https://image.intervention.io/v3) — format support, WebP encode, GD/Imagick drivers — HIGH confidence
- Packagist `intervention/image` (https://packagist.org/packages/intervention/image) — latest stable v3.11.x, v4 requires PHP 8.3+ — HIGH confidence
- Packagist `intervention/image-laravel` (https://packagist.org/packages/intervention/image-laravel) — latest 1.5.8, L8–L13 support — HIGH confidence
- Google Fonts CSS2 API docs (https://developers.google.com/fonts/docs/css2) — endpoint format `fonts.googleapis.com/css2?family=Name` — HIGH confidence
- Laravel HTTP `sink` method (https://laravel-news.com/http-sink) — stream to disk pattern — HIGH confidence
- MDN CSS Custom Properties (https://developer.mozilla.org/en-US/docs/Web/CSS/Using_CSS_custom_properties) — `[data-theme]` pattern — HIGH confidence
- Codebase audit (`app/Livewire/ProductManager.php`, `GuestMenu.php`, `PosDashboard.php`, `resources/views/layouts/app.blade.php`) — existing is_available column and branding cascade confirmed — HIGH confidence

---

*Stack research for: Bite-POS v1.1 — menu themes, custom fonts, image optimization, sold-out toggle*
*Researched: 2026-03-21*
