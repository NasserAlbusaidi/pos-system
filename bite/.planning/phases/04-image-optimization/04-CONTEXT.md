# Phase 4: Image Optimization - Context

**Gathered:** 2026-03-21
**Status:** Ready for planning

<domain>
## Phase Boundary

Uploaded product images are automatically resized and converted to WebP with three size variants (thumbnail 200px, card 400px, full 800px). Originals are NOT preserved (save disk space). Guest menu and all views use optimized variants with lazy loading. Existing images are backfilled via artisan command.

</domain>

<decisions>
## Implementation Decisions

### Storage structure
- **D-01:** Convention-based naming, no schema change. Single `image_url` column stays. Variants derived from filename: `products/abc123-thumb.webp`, `products/abc123-card.webp`, `products/abc123-full.webp`
- **D-02:** Original upload is deleted after variants are generated — save disk space, don't archive originals
- **D-03:** A Blade helper or model accessor resolves the right variant path (e.g., `productImage($product, 'card')`)

### Where optimization runs
- **D-04:** All views use optimized variants — guest menu, POS dashboard, admin/product manager. Not just guest-facing
- **D-05:** Optimization hooks into both ProductManager (`saveProduct()`) and OnboardingWizard product creation
- **D-06:** Products with no image (`image_url` is null) are skipped — no processing, fallback SVG continues to work

### Existing images
- **D-07:** Backfill existing images via a one-time artisan command (`php artisan images:optimize` or similar)
- **D-08:** When admin replaces a product image, old variants are cleaned up (deleted from disk)

### Upload experience
- **D-09:** Optimization is invisible to the admin — no toast, no size feedback, just works silently
- **D-10:** Enforce 5MB max upload size with a clear validation error message
- **D-11:** Accept JPEG and PNG only. Reject GIF, BMP, HEIC, etc. with a clear error
- **D-12:** Processing happens synchronously during the save request (~1-2s for large photos). No queue job

### Claude's Discretion
- Image quality setting for WebP conversion (balance between file size and visual quality)
- Whether `image_url` column value changes to point to the full-size variant (e.g., `products/abc123-full.webp`) or stays as original path with variants alongside
- Exact implementation of the image helper/accessor
- Error handling when GD fails mid-processing (e.g., corrupt image)
- Whether to use intervention/image v3 or raw GD functions

</decisions>

<specifics>
## Specific Ideas

No specific design references — standard image optimization pipeline. Key constraint: must work with GD (not Imagick), and GD WebP support is confirmed locally but must have a runtime fallback per success criteria SC-4.

</specifics>

<canonical_refs>
## Canonical References

No external specs — requirements are fully captured in REQUIREMENTS.md and decisions above.

### Requirements
- `.planning/REQUIREMENTS.md` § Image Optimization — IMG-01 through IMG-04
- `.planning/ROADMAP.md` § Phase 4 — success criteria (4 items)

### Prior research
- `.planning/STATE.md` § Accumulated Context — intervention/image v3 only (v4 blocked by PHP 8.2), GD WebP support unverified on production

</canonical_refs>

<code_context>
## Existing Code Insights

### Upload paths (both must be hooked)
- `app/Livewire/ProductManager.php` lines 120-122 (edit) and 144-146 (create): `$this->image->store('products', 'public')`
- OnboardingWizard product creation (separate Livewire component)

### Image rendering
- `resources/views/livewire/guest-menu.blade.php` line 151: `src="/storage/{{ $product->image_url }}"`
- `resources/views/livewire/product-manager.blade.php`: similar `/storage/` prefix pattern
- POS dashboard: also references `image_url`

### Product model
- `app/Models/Product.php`: `image_url` in `$fillable`, single string column, stores relative path like `products/abc123.jpg`

### Filesystem
- Storage disk: `public` (local filesystem at `storage/app/public/`)
- Symlink: `public/storage` → `storage/app/public`
- Existing images: `storage/app/public/products/*.{jpg,png}`

### GD capabilities (verified locally)
- GD 2.3.3 with WebP, AVIF, JPEG, PNG, GIF, BMP support
- Production GD WebP support unverified — needs runtime check with JPEG fallback

### Established Patterns
- Livewire file upload via `WithFileUploads` trait — `$this->image` is a `TemporaryUploadedFile`
- `$this->image->store('products', 'public')` returns relative path
- Validation: `$this->validate(['image' => 'nullable|image|max:2048'])` — currently 2MB, needs bump to 5MB

### Integration Points
- ProductManager: hook into `saveProduct()` after `$this->image->store()`
- OnboardingWizard: hook into product creation flow
- Guest menu blade: replace raw `image_url` references with variant-aware helper
- Product manager blade: same
- POS dashboard blade: same

</code_context>

<deferred>
## Deferred Ideas

- On-the-fly variant generation for non-standard sizes — v2 (IMGADV-01)
- CDN integration for image delivery — v2 (IMGADV-02)
- AVIF format support (GD supports it, but browser support still patchy) — future consideration

</deferred>

---

*Phase: 04-image-optimization*
*Context gathered: 2026-03-21*
