# Project Research Summary

**Project:** Bite-POS v1.1 — Menu Customization & Polish
**Domain:** Multi-tenant SaaS restaurant POS — visual identity and operational controls layer
**Researched:** 2026-03-21
**Confidence:** HIGH

## Executive Summary

Bite-POS v1.1 adds four targeted features to a production Laravel 12 + Livewire 3 system: menu themes, custom Google Fonts, image optimization, and a sold-out toggle with guest-facing visibility. None of these features require architectural pivots — each integrates cleanly with existing mechanisms already in the codebase. The branding cascade (Shop.branding JSON → inline CSS tokens in app.blade.php), the WithFileUploads pipeline in ProductManager, the is_available column on Product, and the PosDashboard toggle86 method are all already in place. The v1.1 scope is about extending surfaces and adding one new dependency (intervention/image-laravel) — not building new infrastructure from scratch.

The recommended build order is: sold-out toggle first (lowest risk, highest immediate demo value for Sourdough), then image optimization (self-contained, improves all subsequent screenshots), then menu themes (CSS-only, no new package), then custom fonts (most moving parts, depends on theme CSS token structure being in place). This sequencing matches both feature independence and pitfall mitigation priority: each phase unlocks the next without creating rework.

The primary risks are architectural rather than technical. CSS cascade conflicts between themes and the existing branding inline block can silently break brand color application. The custom font pipeline has three security exposure points (CSS injection, path traversal, SSRF) that must be addressed before any font-name input touches storage or rendering. Image optimization must handle GD WebP support gaps and must not corrupt the existing image_url path convention. These risks are well-understood and have clear prevention strategies documented in PITFALLS.md.

---

## Key Findings

### Recommended Stack

The stack is already correct and needs only one addition. Laravel 12 + Livewire 3 + vanilla CSS custom properties handle all four features without new frontend tooling. CSS `[data-theme]` attribute selectors are the right mechanism for themes — adding a JS-driven theme library would break Livewire SSR expectations and contradict the existing pure-CSS token system. The Laravel HTTP client (bundled) handles Google Font fetching natively via `Http::sink()` for streaming woff2 downloads — no third-party font package is needed or appropriate.

**Core technologies:**
- `intervention/image:^3.11` + `intervention/image-laravel:^1.5` — image resize and WebP encoding — only new dependency; confirmed PHP 8.1–8.4 + Laravel 8–13 compatible; use GD driver (available on all standard PHP installs); v4 beta explicitly excluded (requires PHP 8.3+)
- Laravel HTTP client — Google Font woff2 download via CSS2 API — no API key required; Http::fake() enables clean test isolation
- CSS custom properties + `[data-theme]` attribute selectors — theme system — browser-native, zero build complexity, consistent with existing design token architecture
- `Shop.branding` JSON column — extended with `theme`, `font_family`, `font_path` keys — zero-migration approach; all new keys read with null-coalesce fallbacks

**Critical version note:** intervention/image v4 is blocked — it requires PHP 8.3+. Stay on v3 until project locks to 8.3 minimum.

### Expected Features

**Must have (table stakes):**
- Sold-out badge on guest menu — every major QR menu platform (Square, Lightspeed, Talabat) surfaces unavailability visually; silently hiding items is the wrong UX; change is query scope + CSS state, not new data
- Sold-out toggle in admin product manager — POS already has toggle86; this exposes the same operation to admin/managers for catalog-level control
- Image optimization pipeline — large JPEG uploads on slow Oman mobile networks directly hurt conversion; 40–70% file size reduction with WebP at quality 82

**Should have (competitive differentiators):**
- Menu themes (3 presets for v1.1: default/warm, modern, dark) — no competitor in the Oman mid-market offers full palette+font+layout presets; strong visual identity signal for the Sourdough demo
- Custom Google Font (self-hosted, admin types name) — font choice signals brand personality; self-hosting avoids GDPR and CDN performance issues; unique in this market segment

**Defer to v1.x:**
- Sold-out indicator on POS product grid (nice-to-have, operators haven't requested it yet)
- Automatic 86 from low inventory (requires inventory module, not in scope)
- Additional theme variants beyond 3 (add based on client feedback)

**Defer to v2+:**
- Custom CSS override field (requires CSS injection sanitization + scoping)
- Bulk image re-optimization of existing uploads (Sourdough photos are placeholders anyway)
- AVIF conversion (browser support still incomplete on Oman-market Android devices)

### Architecture Approach

All four features extend the existing architecture without new abstractions beyond two service classes. The branding cascade in app.blade.php gains two new read paths: `branding.theme` drives a `data-theme` attribute on the guest menu root div, and `branding.font_path` drives a `@font-face` block emitted inline. ProductManager gains a `toggleAvailability()` method mirroring the existing `toggle86` pattern. Two new services are introduced: `ImageOptimizationService` (intercepts the existing `$this->image->store()` call with resize + WebP encoding) and `FontFetchService` (handles Google Fonts CSS2 API fetch, woff2 download, and file storage in isolation from the Livewire component).

**Major components:**
1. `ShopSettings.php` (modified) — gains theme picker, font name input; calls FontFetchService on font save
2. `FontFetchService.php` (new) — encapsulates external HTTP, woff2 parsing, filesystem write; returns typed result or throws; keeps ShopSettings thin and testable
3. `ImageOptimizationService.php` (new) — wraps Intervention Image GD pipeline; called from ProductManager and OnboardingWizard; returns path string as drop-in for existing store() return value
4. `ProductManager.php` (modified) — gains `toggleAvailability()` method; image upload path delegates to ImageOptimizationService
5. `GuestMenu.php` + `guest-menu.blade.php` (modified) — remove is_available filter from render query; add sold-out card overlay; addToCart() guard unchanged
6. `layouts/app.blade.php` (modified) — emit `data-theme` attribute; emit `@font-face` block from branding.font_path
7. `resources/css/themes/` (new CSS files) — one file per theme with scoped custom property overrides; imported into app.css

### Critical Pitfalls

1. **CSS cascade collision between theme tokens and branding inline block** — themes must only define structural tokens (radius, spacing, font-family, shadow) that the existing branding cascade does not touch; the cascade owns `--paper`, `--ink`, `--crema` and their derived tokens; these two concerns must never overwrite the same token name
2. **Font name as unvalidated input written into CSS and file paths** — validate font name with `^[A-Za-z0-9 ]+$` before any processing; resolve to canonical Google Fonts family name before storage; never interpolate raw admin input into `@font-face` declarations or filesystem paths; SSRF risk — restrict HTTP calls to `fonts.googleapis.com` and `fonts.gstatic.com` only
3. **GD WebP support absent on production server** — check `imagetypes() & IMG_WEBP` at boot or in a startup health check; if unsupported, fall back to JPEG at quality 85 rather than storing unprocessed originals; never fail silently
4. **Synchronous image optimization blocking Livewire admin save** — a 3MB iPhone photo can cause 2–5 second inline processing; acceptable for v1.1 with a size guard (max 5MB input), but plan the async escape hatch (OptimizeProductImage queued job) if operator complaints emerge
5. **Branding JSON null-coalesce gaps** — all new keys (`theme`, `font_family`, `font_path`) must be read with `?? 'default'` fallbacks; existing shops have none of these keys; a missing null-coalesce causes PHP warnings and unstyled guest menus in production

---

## Implications for Roadmap

Based on combined research, four implementation phases are suggested, ordered by risk, dependency, and demo value.

### Phase 1: Sold-Out Toggle (Admin + Guest Menu)

**Rationale:** Lowest complexity of the four features; no new dependencies; immediately valuable for the Sourdough demo scenario (baked item runs out mid-day). Should be first because it delivers operational value standalone and validates the is_available surface area before it becomes a theme/layout consideration in later phases.

**Delivers:** Admin can toggle product availability from ProductManager; guest menu shows greyed "Sold Out" badge instead of hiding items; addToCart guard remains.

**Addresses:** Table stakes — sold-out badge on guest menu; sold-out toggle in admin.

**Avoids:** Pitfall 6 (stale cart UX) — implement the cart item auto-remove on submit error as part of this phase, not as a later fix.

**Research flag:** None needed — standard Livewire pattern; PosDashboard.toggle86 is the reference implementation.

---

### Phase 2: Image Optimization Pipeline

**Rationale:** Self-contained feature with one new package dependency. Should run before themes so product card images in the themed guest menu already look correct (WebP, correctly sized). Intervention Image v3 is a mature library with no surprises.

**Delivers:** All new product uploads are resized to max 800px, encoded as WebP at quality 82, stored with originals preserved; ProductManager and OnboardingWizard both use the new pipeline.

**Uses:** `intervention/image:^3.11` + `intervention/image-laravel:^1.5` (GD driver, with WebP fallback to JPEG).

**Implements:** `ImageOptimizationService` — called from ProductManager::save() and OnboardingWizard; returns path string as drop-in for existing store() call.

**Avoids:** Pitfall 4 (silent WebP failure) — verify GD WebP support before shipping; Pitfall 5 (broken image_url convention) — output must follow the existing `products/{hash}.webp` path convention.

**Research flag:** None needed — well-documented library with clear integration pattern. Verify GD WebP support on production before deployment.

---

### Phase 3: Menu Themes (3 Presets)

**Rationale:** CSS-only feature with no new PHP packages. Should come after image optimization so product cards in all themes render with optimized WebP images from the start. Theme CSS token structure must be established before the custom font phase, because custom fonts target `--font-display` and `--font-body` variables that themes define.

**Delivers:** Three built-in themes (default/warm, modern, dark) selectable per shop in ShopSettings; theme applied via `data-theme` on guest menu root; brand colors cascade on top of theme defaults.

**Addresses:** Differentiator — selectable menu themes with full palette + font pairing.

**Avoids:** Pitfall 1 (CSS cascade collision) — themes own structural tokens only; Pitfall 7 (undefined branding key) — `$branding['theme'] ?? 'default'` fallback with centralized constant; Pitfall 8 (Arabic RTL font breaking) — every theme must define `--font-display-ar` and `--font-body-ar` tokens; RTL screenshot required before merge.

**Research flag:** RTL testing is a hard requirement per-theme. Every theme must be verified in Arabic mode (letter-spacing rules must be scoped to `[dir="ltr"]`).

---

### Phase 4: Custom Google Fonts (Self-Hosted)

**Rationale:** Most complex feature — involves external HTTP, binary file download, CSS generation, and security validation. Must come after themes because it overrides font tokens (`--font-display`, `--font-body`) that the theme system defines. External HTTP dependency means more failure modes to test.

**Delivers:** Admin types a Google Font family name; system fetches and self-hosts the woff2 file; guest menu uses the font without any Google CDN request; invalid/misspelled names caught at save with a clear error.

**Implements:** `FontFetchService` — HTTP to Google Fonts CSS2 API with modern UA, regex woff2 URL extraction, Http::sink() download, branding JSON update.

**Avoids:** Pitfall 2 (font fetch at render time) — download happens at admin save, stored to disk; guest menu reads pre-built CSS fragment from branding; Pitfall 3 (CSS/path injection) — `^[A-Za-z0-9 ]+$` validation before any processing; SSRF prevention via domain allowlist; Pitfall 9 (live HTTP in tests) — Http::fake() in all font-related tests.

**Research flag:** Needs attention — Google Fonts CSS2 woff2 URL format is stable but not contractually guaranteed; parser regex must be tested against real API responses. Confidence is MEDIUM on long-term URL stability.

---

### Phase Ordering Rationale

- Sold-out toggle first because it is the highest-value, lowest-risk change and can ship independently as an operational improvement
- Image optimization second because it is self-contained and its output (WebP product images) should be in place before theme visual review
- Themes before fonts because font customization targets CSS variables (`--font-display`, `--font-body`) that the theme system must define first; building fonts on an undefined token structure creates rework
- Fonts last because they carry the most risk (external HTTP, security surface area, filesystem writes) and benefit from all previous phases being stable

### Research Flags

Needs deeper attention during implementation:
- **Phase 3 (Menu Themes):** RTL Arabic compatibility — every theme must be QA'd in Arabic mode before merge; letter-spacing and font fallback tokens are not optional
- **Phase 4 (Custom Fonts):** Google Fonts CSS2 API woff2 URL format — verify regex against live API responses during implementation; maintain a fallback strategy if the URL pattern changes

Standard patterns (no additional research needed):
- **Phase 1 (Sold-Out Toggle):** Direct Livewire method + query scope change — PosDashboard.toggle86 is the established pattern
- **Phase 2 (Image Optimization):** Intervention Image v3 is well-documented; GD driver + WebP is verified on this machine; integration point is a single method call replacement

---

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | HIGH | Codebase examined directly; Packagist versions confirmed; intervention/image v3 official docs verified; only new dependency well-understood |
| Features | HIGH | Codebase examined directly; existing capabilities confirmed; competitor analysis grounded in documented product behavior |
| Architecture | HIGH | Integration points audited in actual source files (GuestMenu.php, ProductManager.php, ShopSettings.php, app.blade.php); patterns match existing codebase conventions |
| Pitfalls | HIGH | Code-first analysis; all 10 pitfalls verified against actual code paths; security risks confirmed via established vulnerability patterns |

**Overall confidence:** HIGH

### Gaps to Address

- **Google Fonts woff2 URL format stability (MEDIUM):** The CSS2 API response format is stable but not formally documented as a versioned contract. The regex extraction approach works today but should be tested against live responses during Phase 4 implementation. Mitigation: add a comment documenting the expected format and a fallback to the google-webfonts-helper API as a secondary source.
- **Production server GD WebP support (unverified until deploy):** GD WebP availability is confirmed on the development machine but has not been verified on the production host. Resolution: create a startup check (`php artisan bite:check-webp-support`) in Phase 2 before the pipeline ships.
- **Queue decision for image optimization (deferred):** Synchronous processing is acceptable for v1.1 (max 5MB input, typical food photos are 1–3MB). If admin complaints about slow product saves emerge, the async path (OptimizeProductImage queued job) is the planned escape hatch — queue infrastructure already exists in the codebase.

---

## Sources

### Primary (HIGH confidence)
- Codebase examination — `app/Livewire/GuestMenu.php`, `ProductManager.php`, `ShopSettings.php`, `PosDashboard.php`, `app/Models/Shop.php`, `app/Models/Product.php`, `resources/views/layouts/app.blade.php`, `resources/css/app.css` — all feature integration points verified
- Intervention Image v3 official docs (https://image.intervention.io/v3) — WebP encode API, GD driver, scaleDown behavior
- Packagist: `intervention/image` (v3.11.x), `intervention/image-laravel` (v1.5.8, 2026-03-20) — version compatibility confirmed
- Google Fonts CSS2 API (https://developers.google.com/fonts/docs/css2) — endpoint format, parameter syntax
- MDN CSS Custom Properties — `[data-theme]` attribute selector pattern

### Secondary (MEDIUM confidence)
- Google Fonts woff2 URL format — CSS2 API with modern User-Agent returns woff2 consistently; widely documented in community resources but not formally versioned by Google
- google-webfonts-helper (https://gwfh.mranftl.com/) — viable fallback for woff2 download but third-party service with availability risk

### Tertiary (LOW confidence)
- RTL CSS styling behavior with Latin fonts — standard Web knowledge but not verified against every Arabic character range in the guest menu

---

*Research completed: 2026-03-21*
*Ready for roadmap: yes*
