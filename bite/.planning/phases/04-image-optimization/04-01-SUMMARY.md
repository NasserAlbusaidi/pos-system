---
phase: 04-image-optimization
plan: 01
subsystem: image-processing
tags: [image, webp, intervention-image, service, livewire]
dependency_graph:
  requires: []
  provides: [ImageService, productImage-helper, image-optimization-pipeline]
  affects: [ProductManager, OnboardingWizard, product-images]
tech_stack:
  added: [intervention/image:^3.11, intervention/image-laravel:^1.5]
  patterns: [service-layer, global-helper, TDD-red-green-refactor]
key_files:
  created:
    - app/Services/ImageService.php
    - app/Helpers/image.php
    - tests/Feature/ImageServiceTest.php
    - tests/Feature/ProductManagerImageTest.php
  modified:
    - app/Livewire/ProductManager.php
    - app/Livewire/OnboardingWizard.php
    - composer.json
decisions:
  - "WebP quality at 80, JPEG fallback at 85 — good balance for food photos"
  - "saveVariant() extracted as protected method to enable test subclass overriding without Mockery"
  - "Static $webpSupported cache per-process to avoid repeated gd_info() calls"
metrics:
  duration: 297s
  completed: 2026-03-21
  tasks_completed: 2
  files_modified: 7
---

# Phase 04 Plan 01: Image Optimization Pipeline Summary

**One-liner:** WebP image pipeline with three size variants (200/400/800px) via intervention/image v3 GD driver, with JPEG fallback and atomic original deletion.

## What Was Built

### ImageService (app/Services/ImageService.php)

The core image processing service. Three public methods:

- `processUpload(string $storedPath, string $disk = 'public'): string` — resizes to thumb/card/full, encodes WebP (quality 80) or JPEG (quality 85) fallback, deletes original only after all 3 variants succeed, returns full-size variant path
- `deleteVariants(string $imageUrl, string $disk = 'public'): void` — removes all 3 variant files by deriving baseName from the `-full.ext` suffix convention
- `supportsWebp(): bool` — checks `gd_info()['WebP Support']`, cached in static property

**Key safety guarantee:** The original file is only deleted after the entire foreach loop completes. If any variant save throws, the original survives for caller fallback.

### productImage() Blade helper (app/Helpers/image.php)

Global function registered in `composer.json` autoload.files. Takes a nullable Product and variant name ('thumb', 'card', 'full'), returns `/storage/{path}` URL or null if no image. Example: `productImage($product, 'card')` → `/storage/products/abc123-card.webp`.

### ProductManager integration (app/Livewire/ProductManager.php)

- Validation updated: `mimes:jpeg,png,jpg|max:5120` (5MB, JPEG/PNG only — removed gif and webp per D-11)
- Create path: stores file, processes through ImageService, falls back to raw path if processing fails
- Edit path: captures old image_url before store, processes new upload, deletes old variants after success
- All processUpload calls wrapped in try-catch with `report($e)` — processing failure never blocks the save

### OnboardingWizard hooks (app/Livewire/OnboardingWizard.php)

Added ImageService import and D-05 hook at both product creation paths:
1. AI extraction path (`saveExtractedMenu()`) — `Product::forceCreate()` now captured as `$product`, hook runs after
2. Manual entry path (`saveMenuItems()`) — same pattern

Both hooks are conditional on `$product->image_url` being set — currently products are created without images, so the hook is dormant but present for when image upload is added.

## Tests

### ImageServiceTest (10 tests)
1. processUpload creates 3 WebP variants
2. processUpload deletes original after variants
3. processUpload creates JPEG variants when WebP unsupported
4. deleteVariants removes all 3 files
5. deleteVariants does not throw when files missing
6. productImage() returns card URL
7. productImage() returns null when no image
8. productImage() returns null for null product
9. processUpload resizes to correct dimensions
10. Original survives when variant save fails (subclass override of saveVariant)

### ProductManagerImageTest (5 tests)
1. Saving with JPEG creates -full.webp image_url
2. Saving with PNG creates -full.webp image_url
3. Editing with new image deletes old variant files
4. Validation rejects files over 5MB
5. Validation rejects GIF files

Total: 15 new tests. Full suite: 173 tests passing.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 2 - Lint] Apply pint code style fixes after implementation**
- **Found during:** Post-task verification
- **Issue:** Pint reported style violations: concat_space, unary_operator_spaces, not_operator_with_successor_space, ordered_imports, new_with_parentheses, braces_position, no_unused_imports
- **Fix:** Ran `./vendor/bin/pint` on all new/modified files
- **Files modified:** app/Helpers/image.php, app/Services/ImageService.php, tests/Feature/ImageServiceTest.php, tests/Feature/ProductManagerImageTest.php
- **Commit:** 2b1823e

**2. [Rule 2 - Testing] Added saveVariant() as protected method**
- **Found during:** Task 1 implementation
- **Issue:** Test 9 (original survives on failure) required overriding variant save behavior. Pure Mockery on a non-interface service is heavy; anonymous class extension is cleaner
- **Fix:** Extracted `saveVariant(string $absolutePath, string $data): void` as protected method, enabling test subclasses to override it without touching processUpload logic

## Self-Check Verification

- [x] app/Services/ImageService.php exists
- [x] app/Helpers/image.php exists
- [x] tests/Feature/ImageServiceTest.php exists (10 tests)
- [x] tests/Feature/ProductManagerImageTest.php exists (5 tests)
- [x] composer.json contains intervention/image and image.php in autoload.files
- [x] All 173 tests pass
- [x] Pint lint passes
- [x] Commits f68fe9b, 47b38ce, 2b1823e exist
