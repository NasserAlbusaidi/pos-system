---
phase: 01-polish
plan: "01"
subsystem: ui
tags: [css, design-tokens, branding, fonts, livewire, blade, php]

# Dependency graph
requires: []
provides:
  - Full 8-token CSS branding cascade derived from 3 shop brand colors (paper, ink, crema)
  - Self-hosted Playfair Display Bold font (WOFF2, 23KB) at public/fonts/
  - All .menu-* CSS component classes ready for Plan 02 Blade template rewrite
  - .guest-menu-bg warm gradient class for guest menu wrapper
  - RTL overrides for .menu-category-header
affects:
  - 01-02 (guest menu Blade template rewrite consumes all .menu-* classes)
  - All shop guest menu views (branding cascade now covers all 8 tokens)

# Tech tracking
tech-stack:
  added:
    - Playfair Display Bold (WOFF2, self-hosted) — category header serif font
  patterns:
    - Linear RGB interpolation via $mix() for deriving CSS tokens from brand colors
    - $parseHexToArr + $mix + $toRgbStr PHP helpers in app.blade.php @php block
    - @font-face declarations following existing Rubik pattern in app.css

key-files:
  created:
    - public/fonts/PlayfairDisplay-Bold.woff2
    - .planning/phases/01-polish/01-01-SUMMARY.md
  modified:
    - resources/views/layouts/app.blade.php
    - resources/css/app.css

key-decisions:
  - "Linear RGB interpolation chosen for token derivation (not HSL adjustments) — predictable warm-toned results with simple PHP math"
  - "Playfair Display Bold only (weight 700) self-hosted — single weight per UI-SPEC constraint, WOFF2 format from gstatic CDN"
  - "guest-menu-bg placed inside @layer components to follow project CSS architecture pattern"
  - "menu-product-description uses max-height CSS transition as fallback to x-collapse (Alpine plugin may not be available)"

patterns-established:
  - "Derived token pattern: $parseHexToArr → $mix → $toRgbStr — use this chain for any future color derivation in blade layouts"
  - "Font self-hosting: download WOFF2 from gstatic using CSS API to get URL, place in public/fonts/, declare @font-face after existing Rubik declarations"

requirements-completed:
  - BRND-01
  - BRND-02
  - BRND-03
  - GMVIZ-06

# Metrics
duration: 2min
completed: "2026-03-20"
---

# Phase 01 Plan 01: Branding Cascade + Playfair Display Font Summary

**Extended CSS branding cascade to derive all 8 tokens from 3 shop colors using PHP linear RGB interpolation, and self-hosted Playfair Display Bold WOFF2 with all .menu-* component classes for the guest menu rewrite**

## Performance

- **Duration:** 2 min
- **Started:** 2026-03-20T18:29:30Z
- **Completed:** 2026-03-20T18:31:58Z
- **Tasks:** 2
- **Files modified:** 3 (app.blade.php, app.css, PlayfairDisplay-Bold.woff2 created)

## Accomplishments

- Full branding cascade: all 8 CSS tokens (paper, ink, crema, canvas, panel, panel-muted, line, ink-soft) are now emitted in the inline `<style>` block when a shop has branding set — cold grey defaults from app.css :root are overridden for every branded shop
- Self-hosted Playfair Display Bold (WOFF2, 23KB) with @font-face declaration following existing Rubik pattern
- 15 new .menu-* CSS classes ready for Plan 02 Blade template consumption, including grid, card, image area, skeleton overlay, product name/price, add button, description expand/collapse, and category header
- RTL support for .menu-category-header (Arabic fallback font, mirrored accent bar position)

## Task Commits

Each task was committed atomically:

1. **Task 1: Extend branding cascade with derived token computation** - `7628f4b` (feat)
2. **Task 2: Add Playfair Display font and menu component CSS classes** - `e013831` (feat)

**Plan metadata:** (docs commit follows)

## Files Created/Modified

- `resources/views/layouts/app.blade.php` - Added $parseHexToArr, $mix, $toRgbStr PHP helpers; emits all 8 CSS tokens in inline style block
- `resources/css/app.css` - Added @font-face Playfair Display; added 15 .menu-* component classes; added RTL overrides; added .guest-menu-bg
- `public/fonts/PlayfairDisplay-Bold.woff2` - Self-hosted Playfair Display Bold, 23KB, valid WOFF2

## Decisions Made

- Linear RGB interpolation (not HSL) for token derivation — simple PHP math, predictable warm results
- Downloaded WOFF2 directly from Google's gstatic CDN by first fetching CSS API with Chrome UA to get the real URL
- Kept $toRgb (hex-string → CSS triplet) alongside new $toRgbStr (array → CSS triplet) for backwards compatibility with the 3 base tokens

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

- Google Fonts download page returns HTML when accessed without browser UA; resolved by fetching CSS API with Chrome user-agent to extract the actual gstatic WOFF2 URL, then downloading that directly.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- All .menu-* CSS classes defined and waiting — Plan 02 can rewrite the guest-menu.blade.php template immediately
- Branding cascade complete — any shop with branding set will now have all derived tokens computed warm, no cold grey defaults
- Playfair Display font ready at /fonts/PlayfairDisplay-Bold.woff2

---
*Phase: 01-polish*
*Completed: 2026-03-20*
