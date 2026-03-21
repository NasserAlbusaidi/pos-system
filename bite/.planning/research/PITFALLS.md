# Pitfalls Research

**Domain:** Adding menu themes, Google Font self-hosting, image optimization pipeline, and item availability toggle to an existing Laravel/Livewire multi-tenant POS with bilingual RTL support.
**Researched:** 2026-03-21
**Confidence:** HIGH (code examined first-hand; web research confirms known failure modes)

---

## Critical Pitfalls

### Pitfall 1: CSS Custom Property Collision Between Theme Base and Branding Cascade

**What goes wrong:**
You introduce a theme (e.g., `theme-minimal`) that sets its own `--panel`, `--ink`, `--crema` values in a `.theme-*` class or data attribute. Then the inline `<style>` block in `layouts/app.blade.php` that derives the branding cascade overwrites those theme values on `:root`, because it runs unconditionally on every page load. Shops that chose the "minimal" theme see their brand colors anyway, or shops that changed their brand colors lose the theme's visual intent.

**Why it happens:**
The current branding cascade writes directly to `:root` in an inline `<style>` block. Themes applied via a class on `<html>` or `<body>` live in the compiled CSS file, which loads before the inline block. `:root` always wins over a descendant class when specificity is equal, so the inline block silently resets whatever theme variables the external stylesheet set.

**How to avoid:**
Apply the theme class to `<html>` and move the brand-cascade inline `<style>` to inject variables **scoped under that theme class** (e.g., `html.theme-warm { --paper: ...; }`) rather than `:root`. Alternatively, keep the cascade on `:root` and have themes only define layout/structural tokens that the cascade does not touch (grid, border-radius, font-family, shadow depth). Never let themes and the cascade overwrite the same token name.

**Warning signs:**
- Brand color pickers appear to have no effect after a theme is selected.
- Two shops with the same theme look identical despite different brand colors.
- Toggling themes in the admin panel has no visual change.

**Phase to address:** Menu Themes phase, before any theme CSS is written.

---

### Pitfall 2: Google Font Fetch at Guest Menu Render Time (Latency + Failure)

**What goes wrong:**
The admin types "Crimson Pro" and saves. The guest menu at `/menu/{slug}` fetches that font name, calls the Google Fonts CSS2 API in real time on first load, downloads the WOFF2 files, and stores them. That synchronous HTTP call inside the Blade render adds 200–800ms to the first guest page load. If Google Fonts is unreachable (network partition, rate limit, DNS failure) or the font name is misspelled/does not exist, the guest menu crashes or shows a blank fallback.

**Why it happens:**
Packages like `spatie/laravel-google-fonts` fetch-on-first-use by design. They fall back to a Google CDN `<link>` tag on failure. But this project deliberately self-hosts and has no Google CDN fallback as policy. If the font fetch is triggered inline at render, not via a background job, any network glitch during the guest session degrades the page.

**How to avoid:**
Trigger the font download as a queued job when the admin saves the font name in ShopSettings, not at guest menu render time. Store the downloaded WOFF2 in `public/fonts/shops/{shop_id}/` and the generated `@font-face` CSS fragment in the `branding` JSON column. The guest menu reads the pre-built CSS fragment from the database; it never calls Google Fonts at render time. If the job has not run yet, fall back to the system sans-serif, not to Google CDN. Validate the font name against the Google Fonts API metadata endpoint before queuing the job, so invalid names are caught at admin save, not silently at download time.

**Warning signs:**
- Guest menu TTFB increases noticeably after a font is saved.
- Admin saves a font and the guest menu shows a different font (fetch failed, fell back to CDN).
- Workers backed up: font download job queued but not yet consumed.

**Phase to address:** Custom Fonts phase.

---

### Pitfall 3: Font Name as Unvalidated Input Written Into CSS / File Paths

**What goes wrong:**
The admin types a font name. That string flows from `branding['font_name']` into a `@font-face` `font-family:` declaration or into a file path `public/fonts/shops/{shop_id}/{font_name}.woff2`. If the string contains `../`, CSS property injections (e.g., `; background: url(evil.com/x.js)`), or shell metacharacters, you get path traversal or stored CSS injection. The guest menu renders the `@font-face` block inline, which means any shop admin can inject arbitrary CSS that runs for every guest visitor of their shop.

**Why it happens:**
This project already validates hex color inputs via `normalizeHex()` and regex in ShopSettings to prevent CSS injection. But a font name field has no equivalent sanitization in place yet. Font names from Google Fonts are generally safe, but the admin input is unvalidated before it reaches storage and rendering.

**How to avoid:**
Validate that the font name matches a strict allowlist: only letters, spaces, digits — `^[A-Za-z0-9 ]+$`. Reject anything else at ShopSettings save. Resolve the font name to a canonical form by querying the Google Fonts API metadata (`https://www.googleapis.com/webfonts/v1/webfonts?key=...`) and storing the exact family name returned by Google, not the admin's raw input. Never interpolate `branding['font_name']` directly into a `<style>` block; always use the validated canonical name.

**Warning signs:**
- Font name field accepts characters like `/`, `'`, `;`, `<`.
- No test exists that inputs `../../evil` as a font name and verifies rejection.
- CSS is generated by string concatenation rather than a structured builder.

**Phase to address:** Custom Fonts phase, before any rendering logic is written.

---

### Pitfall 4: WebP Conversion Fails Silently When GD/Imagick Lacks WebP Support

**What goes wrong:**
The optimization pipeline converts uploads to WebP using Intervention Image 3 with the GD driver. On the production server, GD was compiled without `--with-webp` (common on older Ubuntu/Debian setups). The `->toWebp()` call throws an exception or produces a corrupt file. The error is caught somewhere, `$imageUrl` stays null or the original is stored without optimization, and the admin sees no error. Product images silently do not optimize.

**Why it happens:**
GD's WebP support is compile-time. Many shared hosting environments and default OS packages ship GD without WebP. Imagick is more reliable for WebP but requires the `libwebp` delegate compiled in. Neither package gives a clear "WebP not supported" user-facing error.

**How to avoid:**
Check WebP support at application startup (or in a boot-time health check) using `imagetypes() & IMG_WEBP` for GD, or `Imagick::queryFormats('WEBP')` for Imagick. Log a clear warning if unsupported. Use Imagick as the preferred driver (it handles more formats and is more reliable for WebP). In the pipeline, if WebP encoding fails, fall back to re-encoding as JPEG at quality 85 with metadata stripped, rather than storing the raw original. Write a test that uploads a real PNG and asserts the stored file is valid WebP.

**Warning signs:**
- `php -r "var_dump(imagetypes() & IMG_WEBP);"` returns `0`.
- `php -r "echo implode(',', Imagick::queryFormats('WEBP'));"` returns empty.
- ProductManager stores uploads but files in `/storage/products/` are still PNG/JPEG at original size after the pipeline runs.

**Phase to address:** Image Optimization phase, before pipeline implementation.

---

### Pitfall 5: Image Optimization Breaks the Existing `image_url` Column Format

**What goes wrong:**
The current `ProductManager::save()` stores the return value of `$this->image->store('products', 'public')` directly in `image_url`. That returns a relative path like `products/abc123.jpg`. The guest menu uses `/storage/{{ $product->image_url }}` to construct the full URL. After adding optimization, the pipeline saves a WebP file with a different path (e.g., `products/optimized/abc123.webp`) or a different extension. Now old product images (still `products/abc123.jpg`) render fine but new images break because the path format changed, or vice versa.

**Why it happens:**
The column convention (`products/filename.ext` as the relative path under `/storage/`) is implicit — there are no types, no schema constraints, and no documented contract. A new pipeline that changes the naming pattern is easy to introduce without realizing it breaks existing path lookups.

**How to avoid:**
Define and document the `image_url` convention explicitly: "always a relative path from `storage/app/public/`". The optimization pipeline must output to the same `products/` directory and update the `image_url` to the new WebP path. Keep originals under a separate `products/originals/` path or in a parallel column (`image_url_original`), not under the main path. Never change the convention of how the guest menu constructs the URL. Write a test asserting that after optimization, `/storage/{{ $product->image_url }}` resolves to a 200 response with `Content-Type: image/webp`.

**Warning signs:**
- Guest menu images 404 after the first upload with the new pipeline.
- `image_url` values in the database have mixed formats (`products/x.jpg` vs `products/x.webp` vs `optimized/products/x.webp`).
- The shimmer skeleton never resolves (image broken, triggers `onerror` fallback).

**Phase to address:** Image Optimization phase.

---

### Pitfall 6: Sold-Out Toggle Does Not Flush Active Guest Carts

**What goes wrong:**
A staff member marks "Croissant" as sold out. At that moment, three guests browsing the menu have "Croissant" in their in-memory Livewire `$cart` array. When they tap "Place Order", `GuestMenu::submitOrder()` does re-verify availability — it fetches products with `->where('is_available', true)`. Those three guests get a hard error mid-checkout: "The following items are no longer available: Croissant." The cart is not cleared, the modal stays open showing an error, and the guest is confused.

This is the `is_available` guard already working correctly on order submission, but the UX impact is not addressed.

**Why it happens:**
`is_available` is checked correctly at submit time (confirmed in `GuestMenu.php` lines 674–691). But there is no reactive mechanism to inform open guest sessions before they reach checkout. The error message `guest.items_unavailable` is defined in the lang files, but the UX around it (auto-remove items, clear notice, offer alternatives) is not defined.

**How to avoid:**
Two parts: first, the hard guard at submit is correct and must remain. Second, add a soft advisory path: when a guest clicks "Review Order", check availability client-side (or via a `#[Computed]` property) and show an inline warning per item before they hit submit. The "Order" button should be disabled with a tooltip if unavailable items exist in cart. The error at submit should auto-remove the sold-out item from cart, not just display an error and leave the item in place.

**Warning signs:**
- Users report "I got an error and couldn't order" after toggling a product sold-out.
- `orderError` is set but the cart still shows the unavailable item.
- No test covers the flow: "product marked unavailable mid-session, guest submits order".

**Phase to address:** Item Availability phase.

---

### Pitfall 7: Theme Selection Stored in Branding JSON Without Migration Safety

**What goes wrong:**
You add `branding['theme']` as the key for the selected theme. Existing shops have `branding` JSON without this key. When the guest menu reads `$branding['theme']` directly (array access without null-check), you get a PHP warning (`Undefined array key "theme"`), which in production with display_errors off is silent but may trigger Sentry noise. Worse: the theme CSS class applied to `<html>` is `null` or empty string, which produces `class="theme-"` on `<html>`, matching no CSS rule and leaving the page unstyled.

**Why it happens:**
The `branding` column is a freeform JSON blob (cast as `'json'` in Shop model). Existing rows have only `paper`, `ink`, `accent`, `receipt_header`, `language`, `whatsapp_number`, `whatsapp_notifications_enabled`. Any new key must be read with a null-coalesce fallback. This has been done correctly for existing keys (see `layouts/app.blade.php` line 21: `$branding['paper'] ?? '#FDFCF8'`) but is easy to forget for new ones.

**How to avoid:**
Always read new branding keys with `?? 'default_value'`. Add a fallback theme constant (`'default'` or `'warm'`) in a config file, not hardcoded in the Blade template. Write a test that creates a shop with the old branding JSON schema (no `theme` key) and asserts the guest menu renders without error and applies the default theme class.

**Warning signs:**
- Sentry reports `Undefined array key "theme"` on guest menu pages.
- Existing shops see no theme applied (no class on `<html>`).
- `$branding['theme']` appears in Blade without `??`.

**Phase to address:** Menu Themes phase, before any theme CSS class is rendered.

---

### Pitfall 8: Theme Font Conflicts With Bilingual RTL Layout

**What goes wrong:**
A theme defines `font-family: 'Crimson Pro', serif` for display text. The guest menu in Arabic mode relies on IBM Plex Sans Arabic for RTL glyphs (loaded from Google CDN currently). "Crimson Pro" has no Arabic glyphs. The browser falls back to the OS default Arabic font, which looks inconsistent with the English theme typography. Worse: `letter-spacing` applied in the theme's display rules (common for LTR display fonts) causes Arabic text to render with disconnected letterforms, which is visually broken for Arabic.

**Why it happens:**
Latin fonts do not contain Arabic Unicode ranges. `letter-spacing` is an LTR-first CSS property; positive values create visible gaps between Arabic connected letters. Themes designed without RTL testing will apply these rules globally.

**How to avoid:**
Every theme must define a separate `--font-display-ar` and `--font-body-ar` token. In RTL mode (`[dir="rtl"]`), set `font-family: var(--font-display-ar), ...` rather than the Latin display font. In theme CSS, scope any `letter-spacing` rules to `[dir="ltr"]` only. Test every theme by switching to Arabic in the guest menu before marking the theme complete.

**Warning signs:**
- Arabic text in `<h3>` category headers shows gaps between letters.
- Arabic menu items render in a different visual weight than English items on the same theme.
- No RTL screenshot is captured in theme QA.

**Phase to address:** Menu Themes phase, RTL test before accepting any theme as done.

---

### Pitfall 9: Queued Font Download Job Not Configured for Sync Driver in Tests

**What goes wrong:**
The font download dispatches a queued job (`DownloadGoogleFont`). Tests run with the `sync` queue driver (confirmed in `phpunit.xml`). In tests, the job executes inline. But the job makes an HTTP call to Google Fonts API, which means tests make real outbound network requests, become slow (or fail in CI with no network), and are non-deterministic.

**Why it happens:**
`phpunit.xml` sets `QUEUE_CONNECTION=sync` for tests so Livewire actions that dispatch jobs run in-band. But jobs with external HTTP calls need Http::fake() to intercept them. Without it, every test that saves a font name makes a live API call.

**How to avoid:**
In the font download job, use Laravel's `Http` facade (not bare `file_get_contents` or `curl`), which allows `Http::fake()` in tests. Write tests that fake both the Google Fonts metadata endpoint and the WOFF2 download endpoint. Never call `file_get_contents` for remote URLs in the job — use the Http client.

**Warning signs:**
- Tests that save a custom font name take 1–3 seconds instead of milliseconds.
- Tests fail when run in CI with `CURLOPT_TIMEOUT` errors.
- The job uses `file_get_contents($url)` instead of `Http::get($url)`.

**Phase to address:** Custom Fonts phase, test infrastructure setup.

---

### Pitfall 10: Image Optimization Blocks Livewire Upload Response

**What goes wrong:**
`ProductManager` uses `Livewire\WithFileUploads`. In v3, file uploads are handled as temporary files. After the form is submitted, `save()` calls `$this->image->store()`. If the optimization pipeline (resize + WebP encoding) runs synchronously inside `save()`, a 3MB iPhone photo causes the Livewire action to run for 2–5 seconds. During that time, the admin UI shows a spinner but no feedback, and on slow connections the Livewire wire request can time out.

**Why it happens:**
Intervention Image 3 processes images synchronously. A resize + WebP encode of a full-resolution image (4032x3024) takes 1–3 seconds on a standard VPS. There is no queued processing in the current `ProductManager::save()` — it's all inline.

**How to avoid:**
Store the original upload immediately (fast), then dispatch a `OptimizeProductImage` job to process it asynchronously. The product's `image_url` points to the original until the job completes, then updates to the WebP. In the guest menu, the existing shimmer skeleton already handles images that are not yet available — no additional frontend work needed. Alternatively, if synchronous processing is preferred for simplicity, add a server-side timeout guard and reduce uploaded image resolution early (first-pass size limit to 2048px max dimension) before the full pipeline runs.

**Warning signs:**
- Saving a product with a large image triggers a Livewire timeout or the browser shows the error "Request took too long".
- Admin panel freezes for several seconds when uploading product photos.
- No feedback between upload and form save confirmation.

**Phase to address:** Image Optimization phase.

---

## Technical Debt Patterns

| Shortcut | Immediate Benefit | Long-term Cost | When Acceptable |
|----------|-------------------|----------------|-----------------|
| Store theme as a simple string key in `branding` JSON (no migration) | No new migration needed, fast to ship | Growing branding JSON becomes unstructured; future schema changes get harder | Acceptable for v1.1; revisit if branding JSON exceeds ~12 keys |
| Synchronous font download in admin save (no queue) | Simpler code, no job infrastructure needed | Guest menu first-load will be slow if font is fetched at render; blocks the admin save response | Never for guest-facing paths; acceptable only if triggered exclusively at admin save time |
| Keep originals as-is, only store WebP path | Avoids dual-file storage complexity | Can never regenerate at higher quality later; original lost if WebP pipeline had a bug | Never — always keep originals |
| Single `is_available` flag at submit check, no live guest feedback | Already implemented and correct | Guests hit confusing checkout error mid-flow when items go sold-out | Acceptable for v1.1; add proactive guest warning in v1.2 |
| Skip Arabic font declaration in themes (rely on browser fallback) | Saves one design decision per theme | Arabic mode looks broken on any theme that specifies a non-Arabic display font | Never — must specify Arabic font fallback per theme |

---

## Integration Gotchas

| Integration | Common Mistake | Correct Approach |
|-------------|----------------|------------------|
| Google Fonts CSS2 API | Fetching `fonts.googleapis.com/css2?family=...` with PHP's default `file_get_contents` — fails in environments with `allow_url_fopen=false` | Use Laravel `Http` facade; handle `RequestException`; validate font name via metadata API before downloading |
| Intervention Image 3 + Laravel Storage | Calling `Image::read($path)->toWebp()->save($path)` — overwrites original in-place | Read from temp path, encode to WebP, `Storage::put()` to a new path; delete original separately only after confirming write success |
| Livewire WithFileUploads + optimization | Running heavy processing inside `save()` method — blocks the Livewire response cycle | Dispatch a job from `save()`; store original immediately; update `image_url` via job callback |
| CSS variable override (theme + cascade) | Defining `--panel` in both `:root` (branding cascade) and `.theme-*` class — cascade always wins and theme has no effect | Separate concerns: cascade owns brand tokens, themes own structural tokens (layout, radius, shadow) |
| `branding` JSON new keys | Direct array access `$branding['theme']` on existing rows that lack the key | Always `$branding['theme'] ?? 'default'`; centralize defaults in a `Shop::getBrandingDefaults()` method |

---

## Performance Traps

| Trap | Symptoms | Prevention | When It Breaks |
|------|----------|------------|----------------|
| Font WOFF2 file served from `public/fonts/shops/{id}/` without cache headers | Every guest page load re-downloads the font file | Set `Cache-Control: public, max-age=31536000, immutable` on `/fonts/` path via Nginx config or Laravel middleware | From the first shop with a custom font |
| Image optimization runs synchronously per upload | Admin product save takes 2–5 seconds on large images | Queue the job; store original immediately | Any upload over ~1MB |
| Theme CSS in a single large `app.css` with all themes | Guests download CSS for all themes on every page load | Scope themes correctly so inactive theme rules are not painted; all 3-4 themes in one file is fine at this scale | At 10+ themes — not a concern for v1.1 |
| `branding` JSON fetched on every guest menu render for CSS generation | Extra DB read or JSON decode per request | Already in memory from the Shop model load; no extra query needed — just avoid calling `$shop->fresh()` inside the template | Not a concern at this scale |

---

## Security Mistakes

| Mistake | Risk | Prevention |
|---------|------|------------|
| Accepting arbitrary font name string and writing it into `@font-face { font-family: "..." }` | Stored CSS injection — shop admin injects arbitrary CSS into guest pages viewed by all guests | Validate font name against strict regex `^[A-Za-z0-9 ]+$`; then resolve against Google Fonts API canonical name; only store canonical name |
| Storing downloaded font files under a path derived from user input (`public/fonts/{font_name}.woff2`) | Path traversal — `../` in font name writes outside `public/fonts/` | Use `shop_id` + a hash as the directory/filename, never the raw font name string |
| Fetching arbitrary URLs from the font download job based on user input | SSRF (Server-Side Request Forgery) — admin crafts a font name that resolves to an internal network address | Only allow HTTP requests to `fonts.googleapis.com` and `fonts.gstatic.com`; validate domain before any HTTP call in the job |
| `image_url` path written from user-supplied filename | Path traversal on file read | Use `$this->image->store()` which generates a UUID-based filename; never preserve the original upload filename |

---

## UX Pitfalls

| Pitfall | User Impact | Better Approach |
|---------|-------------|-----------------|
| Sold-out product silently absent from menu (hidden by `is_available` filter in `render()`) | Guest searches for a specific item, can't find it, doesn't know it's sold out vs. never existed | Show sold-out items with a greyed-out "Sold Out" badge; keep them visible in the menu grid |
| Theme preview only visible after save and full page reload | Admin can't evaluate how a theme looks without committing to it | Show a live preview panel or use Alpine.js class toggling on the settings page before save |
| Custom font applied globally (body + headings) without testing Arabic glyphs | Arabic text in guest menu renders in the wrong font or with broken letterforms | Define per-locale font fallback tokens in every theme; test Arabic mode before marking theme done |
| Optimization pipeline strips EXIF data silently | Shop owner loses metadata (shoot date, camera info) if they care about original provenance | Document that originals are preserved; make originals accessible to the admin |

---

## "Looks Done But Isn't" Checklist

- [ ] **Menu Themes:** Theme selected in admin, guest menu reloaded — verify the `<html>` element has the correct theme class AND brand colors are still applied correctly on top of it.
- [ ] **Menu Themes (RTL):** Switch guest menu to Arabic — verify category headers and product names do not have visible letter gaps, and the display font is the theme's Arabic variant, not the Latin face.
- [ ] **Custom Fonts:** Admin saves a valid font name — verify a WOFF2 file exists in `public/fonts/shops/{id}/`, `branding['font_css']` is populated, and the guest menu uses that font without calling `fonts.googleapis.com`.
- [ ] **Custom Fonts:** Admin types an invalid/misspelled font name — verify a validation error is shown at save and no download job is queued.
- [ ] **Image Optimization:** Upload a 4MB JPEG product photo — verify (a) a WebP file exists in storage, (b) the original JPEG is preserved, (c) the guest menu loads the WebP, (d) the shimmer skeleton resolves correctly, (e) the broken-image fallback does not trigger.
- [ ] **Image Optimization:** Check server GD/Imagick WebP support before shipping — run `php artisan bite:check-webp-support` (to be created) or equivalent.
- [ ] **Item Availability:** Mark a product sold-out — verify it appears with a "Sold Out" badge in the guest menu (not hidden), cannot be added to cart, and is excluded from active POS product lists.
- [ ] **Item Availability:** Add a product to cart, mark it sold-out in another browser tab, then submit the order — verify the checkout error is displayed, the sold-out item is removed from cart, and the user can re-submit with remaining items.

---

## Recovery Strategies

| Pitfall | Recovery Cost | Recovery Steps |
|---------|---------------|----------------|
| CSS cascade conflict (theme + branding overwrite same tokens) | MEDIUM | Audit all CSS token names; rename theme-owned tokens with a `--theme-` prefix; redeploy |
| Font name CSS injection reached production | HIGH | Purge branding JSON for affected shops; audit stored font names against regex; force re-save via artisan command; rotate CSP headers |
| WebP conversion broke `image_url` paths | MEDIUM | Run a one-off artisan command that scans `products` table for broken image_url values, re-paths them to correct location, and re-verifies with `Storage::exists()` |
| Original images deleted before backup confirmed | HIGH | Restore from backup; this is why originals must always be preserved |
| Sold-out toggle UX caused guest complaints | LOW | Add "Sold Out" badge display (frontend-only change); no data migration needed |

---

## Pitfall-to-Phase Mapping

| Pitfall | Prevention Phase | Verification |
|---------|------------------|--------------|
| CSS cascade theme + branding collision | Menu Themes | Screenshot comparison: brand colors applied + theme layout active simultaneously |
| Font fetch at render time (latency/failure) | Custom Fonts | Load test guest menu with no queued jobs running; measure TTFB before vs after font save |
| Font name CSS/path injection | Custom Fonts | Input `../../../evil` and `; background: red` as font name; verify 422 validation error |
| WebP GD/Imagick driver lacking WebP support | Image Optimization | `php artisan bite:check-webp-support` passes on production before pipeline ships |
| Optimization breaks existing `image_url` convention | Image Optimization | Upload test image; assert `GET /storage/{{ $product->image_url }}` returns 200 WebP |
| Sold-out mid-session stale cart UX | Item Availability | Integration test: mark sold-out mid-session, submit cart, assert graceful error + item removal |
| `branding['theme']` undefined on existing shops | Menu Themes | Test with existing shop fixture (no theme key in branding); assert no PHP warning and default theme renders |
| Arabic letterform breaking with themed fonts | Menu Themes | RTL QA screenshot for every theme before merge |
| Font download job makes live HTTP in tests | Custom Fonts | Run test suite in CI (no network); all font-related tests must pass with `Http::fake()` |
| Synchronous optimization blocks admin save | Image Optimization | Upload 4MB image in admin; assert response returns within 2 seconds |

---

## Sources

- Codebase examined directly: `app/Livewire/GuestMenu.php`, `app/Livewire/ProductManager.php`, `app/Livewire/ShopSettings.php`, `app/Models/Shop.php`, `app/Models/Product.php`, `resources/views/layouts/app.blade.php`, `resources/css/app.css`
- [spatie/laravel-google-fonts — GitHub](https://github.com/spatie/laravel-google-fonts) — fetch-on-first-use behavior and WOFF2 format limitations
- [Intervention Image 3 + Laravel Storage — SaaSykit](https://saasykit.com/blog/building-an-image-optimization-pipeline-in-laravel-intervention-package) — WebP pipeline patterns
- [Optimistic UI Tricks for Livewire and Alpine — Tighten](https://tighten.com/insights/optimistic-ui-tips-livewire-alpine/) — optimistic UI edge case handling
- [Google Fonts CSS2 API — Google for Developers](https://developers.google.com/fonts/docs/css2) — variable font axis syntax and format support
- [RTL Styling 101](https://rtlstyling.com/posts/rtl-styling/) — letter-spacing and Arabic font fallback rules
- [ImageMagick Setup for Laravel 2026 — NiharDaily](https://www.nihardaily.com/198-how-to-setup-imagemagick-for-laravel-the-complete-server-guide) — WebP delegate compilation requirements

---
*Pitfalls research for: Bite-POS v1.1 — menu themes, custom fonts, image optimization, item availability*
*Researched: 2026-03-21*
