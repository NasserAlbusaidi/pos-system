---
phase: 04-image-optimization
plan: 02
subsystem: image-views-and-backfill
tags: [image, webp, artisan-command, blade, lazy-loading, tdd]
dependency_graph:
  requires: [ImageService, productImage-helper]
  provides: [images:optimize-command, optimized-image-views]
  affects: [guest-menu, product-manager, existing-product-images]
tech_stack:
  added: []
  patterns: [global-helper-in-blade, TDD-red-green-refactor, artisan-command]
key_files:
  created:
    - app/Console/Commands/OptimizeImages.php
    - tests/Feature/OptimizeImagesCommandTest.php
  modified:
    - resources/views/livewire/guest-menu.blade.php
    - resources/views/livewire/product-manager.blade.php
decisions:
  - "POS dashboard confirmed text-only — zero img tags and zero image_url references, D-04 satisfied without changes"
  - "productImage() helper called for both truthiness check and src — single helper call pattern, no double-prefixing"
metrics:
  duration: 157s
  completed: 2026-03-21
  tasks_completed: 2
  files_modified: 4
---

# Phase 04 Plan 02: View Updates and Backfill Command Summary

**One-liner:** Updated guest-menu and product-manager Blade views to serve WebP variants via productImage() with lazy loading, and created images:optimize artisan command to backfill all existing unoptimized product images.

## What Was Built

### View Updates

**guest-menu.blade.php** — Three targeted changes:
1. Alpine `x-data` loaded flag: `$product->image_url ? 'false' : 'true'` → `productImage($product) ? 'false' : 'true'`
2. Image conditional and `src`: `@if($product->image_url)` / `src="/storage/{{ $product->image_url }}"` → `@if(productImage($product, 'card'))` / `src="{{ productImage($product, 'card') }}"` plus `loading="lazy"` attribute
3. Placeholder `x-show` flag: same `image_url` truthiness → `productImage($product)` pattern

**product-manager.blade.php** — One targeted change:
- Product list thumbnail: `@if($product->image_url)` / `asset('storage/' . $product->image_url)` → `@if(productImage($product, 'thumb'))` / `productImage($product, 'thumb')`

**pos-dashboard.blade.php** — No changes. Confirmed zero `<img>` tags and zero `image_url` references. D-04 satisfied: all views that render product images now use the helper.

### images:optimize Artisan Command (app/Console/Commands/OptimizeImages.php)

Backfill command for existing products with unoptimized images:

- Queries all products with non-null `image_url`
- **Skips** products where `image_url` already contains `-full.` (already processed)
- **Skips** products where source file doesn't exist on disk (logs `MISS:` warning)
- Calls `ImageService::processUpload()` to generate thumb/card/full variants
- Updates product's `image_url` to the new `-full.webp` path
- Reports `Processed / Skipped / Failed` counts on completion
- `--dry-run` flag shows what would be processed without making changes

## Tests

### OptimizeImagesCommandTest (5 tests)
1. Command processes a product with an unoptimized image — updates image_url to `-full.*`
2. Command skips products with null image_url
3. Command skips products with already-processed images (containing `-full.`) — outputs `SKIP`
4. Command reports counts at end — outputs `Done.`
5. Command handles missing source files gracefully — outputs `MISS`, image_url unchanged, exits 0

Total: 178 tests passing (5 new + 173 from Plan 01).

## Deviations from Plan

None — plan executed exactly as written.

## Self-Check Verification

- [x] resources/views/livewire/guest-menu.blade.php contains productImage( (4 occurrences)
- [x] guest-menu.blade.php contains loading="lazy" on product image tag
- [x] guest-menu.blade.php has zero raw src="/storage/{{ $product->image_url }}" references
- [x] resources/views/livewire/product-manager.blade.php contains productImage( (2 occurrences)
- [x] product-manager.blade.php has zero asset('storage/' . $product->image_url) references
- [x] pos-dashboard.blade.php: zero img tags and zero image_url references
- [x] app/Console/Commands/OptimizeImages.php exists with signature images:optimize
- [x] tests/Feature/OptimizeImagesCommandTest.php exists (5 tests)
- [x] All 178 tests pass
- [x] Pint lint passes
- [x] Commits 8dc213e, 4a27f8e, 9b81221 exist

## Self-Check: PASSED
