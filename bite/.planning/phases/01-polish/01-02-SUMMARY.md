---
phase: 01-polish
plan: "02"
subsystem: ui
tags: [blade, livewire, alpine, css, guest-menu, mobile-grid, accordion, shimmer]

# Dependency graph
requires:
  - phase: 01-polish
    plan: "01"
    provides: All .menu-* CSS component classes, guest-menu-bg gradient, menu-category-header Playfair Display, Playfair Display Bold font
provides:
  - Rewritten guest-menu.blade.php with 2-column compact card grid
  - Photo display with /storage/ prefix and object-contain (image URL bug fixed)
  - Alpine accordion: tap card to expand description, one at a time
  - Shimmer skeleton inside image area during loading
  - Fork-knife SVG placeholder for broken/missing images
  - Quick-add + button (wire:click.stop, does not expand card)
  - Category headers using .menu-category-header (Playfair Display + gold bar)
  - Sentence case product names (no uppercase CSS)
affects:
  - 01-03 (Sourdough demo shop data populates this template)

# Tech tracking
tech-stack:
  added: []
  patterns:
    - Alpine x-data expanded state on grid wrapper for single-card accordion
    - x-on:load / x-on:error pattern for image shimmer + broken fallback
    - wire:click.stop to prevent event bubbling from child button to parent click handler
    - wire:key on repeating article elements to preserve Alpine state across Livewire re-renders

key-files:
  created:
    - .planning/phases/01-polish/01-02-SUMMARY.md
  modified:
    - resources/views/livewire/guest-menu.blade.php

key-decisions:
  - "Image shimmer uses absolute-positioned .skeleton div inside .menu-product-image-area, controlled by Alpine x-show — no x-collapse plugin needed"
  - "Accordion state held in x-data on grid wrapper (not global store) — simpler, scoped to each category section"
  - "displayPrice @php variable computed per card to unify time-priced vs sale vs regular price logic in one place"

patterns-established:
  - "Product card image loading: x-data loaded/broken booleans + x-on:load/x-on:error + skeleton div + placeholder div — use this pattern for any future image-displaying card"
  - "Quick-add vs expand: wire:click.stop on the add button inside a clickable card parent prevents double-firing"

requirements-completed:
  - GMVIZ-01
  - GMVIZ-02
  - GMVIZ-03
  - GMVIZ-04
  - GMVIZ-05
  - GMVIZ-07
  - GMVIZ-08
  - GMVIZ-09
  - GMVIZ-10

# Metrics
duration: 8min
completed: "2026-03-20"
---

# Phase 01 Plan 02: Guest Menu Compact Card Grid Summary

**Rewrote guest-menu.blade.php product section to a 2-column compact card grid with Alpine accordion, image shimmer, broken-image fallback, and fixed /storage/ URL prefix — closing all 9 GMVIZ requirements**

## Performance

- **Duration:** ~8 min
- **Started:** 2026-03-20T18:29:30Z
- **Completed:** 2026-03-20T18:37:18Z
- **Tasks:** 1
- **Files modified:** 1 (guest-menu.blade.php)

## Accomplishments

- Fixed critical image display bug: all product photos now use `src="/storage/{{ $product->image_url }}"` prefix
- Replaced full-width single-column cards with 2-column `.menu-product-grid` layout using CSS classes from Plan 01
- Replaced static category header divs with `.menu-category-header` using Playfair Display and gold accent bar
- Added shimmer loading overlay inside `.menu-product-image-area` via Alpine `loaded`/`broken` state
- Added fork-knife SVG placeholder shown on broken images or missing image_url
- Added Alpine accordion on grid wrapper — tapping a card expands description, only one open at a time
- Added quick-add `+` button with `wire:click.stop` to fire `addToCart` without expanding the card
- Updated skeleton section to use `menu-product-grid` matching compact card proportions
- Applied `guest-menu-bg` warm gradient to root wrapper
- All 13 existing GuestMenu tests pass — zero regression

## Task Commits

1. **Task 1: Rewrite category headers and product card grid** - `5533962` (feat)

**Plan metadata:** (docs commit follows)

## Files Created/Modified

- `resources/views/livewire/guest-menu.blade.php` - Root wrapper class, skeleton grid, category headers, full product card grid rewrite with accordion/shimmer/fallback

## Decisions Made

- Image shimmer uses absolute-positioned `.skeleton` div inside `.menu-product-image-area` rather than wrapping the `<img>` — simpler, works without x-collapse Alpine plugin
- Accordion state scoped to each category section's grid `x-data` wrapper — no cross-category interference and simpler than a global store
- `$displayPrice` computed per card in `@php` to consolidate time-priced / on-sale / regular price logic, keeping the template clean

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None — CSS classes from Plan 01 were all in place and ready. The rewrite was clean and all tests passed on first run.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Guest menu template is fully rewritten with all GMVIZ requirements satisfied
- Template is ready for Sourdough Oman demo shop data (Plan 03) — product photos, bilingual names, and 33-item menu will render in the new compact grid immediately
- The /storage/ prefix fix means any existing products with image_url already set will display correctly

---
*Phase: 01-polish*
*Completed: 2026-03-20*
