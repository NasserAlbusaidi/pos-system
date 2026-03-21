---
phase: 05-menu-themes
plan: 02
subsystem: ui
tags: [livewire, blade, css, alpine, themes, guest-menu, shop-settings]
requires:
  - phase: 05-01
    provides: "[data-theme] CSS cascade, theme tokens, ShopSettings.theme property, GuestMenu passes theme to view"
provides:
  - "guest-menu.blade.php with three conditional layout structures (warm/modern/dark)"
  - "Modern horizontal card: .menu-card-modern-inner flex layout, image left 80x80px"
  - "Dark hero card: .menu-card-dark-overlay positioned text over 200px image"
  - "Warm card: existing vertical 2-column grid card (unchanged behavior)"
  - "shop-settings.blade.php with theme picker section (3 stacked cards, static mockups, Alpine live preview)"
  - "app.css: .menu-badge-sale, .menu-card-modern-*, .menu-card-dark-overlay, RTL flex-reverse"
affects:
  - "guest-menu — layout changes by theme"
  - "shop-settings — theme picker added before brand colors"
tech-stack:
  added: []
  patterns:
    - "@if($theme === 'modern') / @elseif($theme === 'dark') / @else conditional blocks for structurally different HTML card layouts"
    - "Alpine @entangle for two-way Livewire property binding in theme picker"
    - "Static inline CSS mockups inside theme picker buttons — no external requests, no PHP rendering"
    - ".menu-badge-sale base class with [data-theme] overrides for per-theme badge positioning"
key-files:
  created: []
  modified:
    - resources/views/livewire/guest-menu.blade.php
    - resources/views/livewire/shop-settings.blade.php
    - resources/css/app.css
key-decisions:
  - "Modern card uses flex-row with a fixed 80x80 image div — no change to .menu-product-image-area class (avoids conflicting with dark theme's full-width usage)"
  - "Dark overlay uses position:absolute inside the .menu-product-image-area which already has position:relative — no extra wrapper needed"
  - "Sale badge moved from inline Tailwind classes to .menu-badge-sale class — cleaner and consistent across all 3 themes"
  - "previewTheme in shop-settings uses @entangle('theme') so Alpine state and Livewire property stay in sync without extra JS"
  - "Warm card wrapped in @else (not @elseif($theme === 'warm')) so it catches any unrecognized theme value as a safe fallback"
requirements-completed:
  - THEME-01
  - THEME-02
  - THEME-04
  - THEME-05
duration: ~8min
completed: 2026-03-21
---

# Phase 05 Plan 02: Menu Themes — Layout Structures and Theme Picker Summary

Three structurally distinct guest menu card layouts (warm 2-column grid, modern horizontal list, dark hero overlay) with Alpine.js theme picker in shop settings showing static mockups and live CSS preview.

## Performance

- **Duration:** ~8 min
- **Started:** 2026-03-21T08:10:16Z
- **Completed:** 2026-03-21T08:18:00Z (awaiting human verify)
- **Tasks:** 1 of 2 complete (Task 2 is human-verify checkpoint)
- **Files modified:** 3

## Accomplishments

- guest-menu.blade.php: replaced single product card block with `@if/$theme` conditional — three structurally different HTML card templates
- Modern card: `.menu-card-modern-inner` flex-row with 80x80 image on left, content on right; `[dir="rtl"]` reverses to row-reverse
- Dark card: `.menu-product-image-area` (200px) with `.menu-card-dark-overlay` positioned over image; description below
- Warm card: existing vertical card with accordion description — unchanged behavior, now in `@else` block
- shop-settings.blade.php: "Menu Theme" section added before Brand Colors; three picker buttons with inline CSS mockups (Croissant/Cappuccino), Alpine `previewTheme` state, live preview div with `:data-theme="previewTheme"`, checkmark on selected card
- app.css: `.menu-badge-sale` base + per-theme overrides replacing inline Tailwind badge classes; all modern/dark card layout classes added

## Task Commits

1. **Task 1: Three layout structures + theme picker UI** - `f6d767f` (feat)

## Files Created/Modified

- `resources/views/livewire/guest-menu.blade.php` - Three conditional card layout blocks replacing single card
- `resources/views/livewire/shop-settings.blade.php` - Theme picker section with static mockups and Alpine live preview
- `resources/css/app.css` - `.menu-badge-sale`, `.menu-card-modern-inner/image/content/footer`, `.menu-card-dark-overlay/desc`, RTL `.menu-card-modern-inner`

## Decisions Made

- Modern card uses a dedicated `div.menu-card-modern-image` (80x80) instead of the existing `.menu-product-image-area` — the image-area class is used for the dark hero where it spans full width, so keeping them separate avoids a height conflict
- `.menu-badge-sale` base class replaces per-card inline Tailwind strings — the badge is shared across all 3 card templates, making it cleaner to position consistently
- Warm card wrapped in `@else` (not `@elseif`) to act as a safe fallback for any invalid theme values that somehow bypass the allowlist

## Deviations from Plan

None — plan executed exactly as written. The CSS badge consolidation (`.menu-badge-sale` replacing inline Tailwind strings) was specified in the plan action text.

## Issues Encountered

None.

## Next Phase Readiness

- Task 2 (human-verify checkpoint) is pending — requires visual QA of all three themes and RTL
- Once approved, Phase 05 is complete and STATE.md / ROADMAP.md should be updated

## Self-Check

- File exists: resources/views/livewire/guest-menu.blade.php — YES
- File exists: resources/views/livewire/shop-settings.blade.php — YES
- File exists: resources/css/app.css — YES
- Commit f6d767f exists: YES (git log confirms)
- Tests: 188 passed (458 assertions)

## Self-Check: PASSED

All files modified, commit present, 188 tests passing.
