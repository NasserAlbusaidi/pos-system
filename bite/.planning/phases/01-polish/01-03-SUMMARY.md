---
phase: 01-polish
plan: 03
subsystem: testing
tags: [livewire, phpunit, guest-menu, branding, css-variables, image-url, regression-tests]

# Dependency graph
requires:
  - phase: 01-polish-01
    provides: image URL /storage/ prefix fix and branding cascade implementation
  - phase: 01-polish-02
    provides: guest menu overhaul (2-column grid, Playfair Display, shimmer, accordion)
provides:
  - TEST-01: regression test proving product images render with /storage/ prefix
  - TEST-02: regression test proving shop branding emits all 5 derived CSS variables
affects: [02-demo]

# Tech tracking
tech-stack:
  added: []
  patterns: [HTTP GET for layout-level assertions, Livewire::test for component-level assertions]

key-files:
  created:
    - tests/Feature/GuestMenuBrandingTest.php
  modified:
    - tests/Feature/Livewire/GuestMenuTest.php

key-decisions:
  - "Use Livewire::test() for component-level assertions (image src in guest-menu.blade.php), $this->get() for layout-level assertions (CSS variables in app.blade.php)"
  - "assertSee with false as second argument disables HTML escaping for CSS variable tokens containing colons"

patterns-established:
  - "Pattern: HTTP GET test vs Livewire::test — use HTTP GET when testing layout output, Livewire::test when testing component output only"

requirements-completed: [TEST-01, TEST-02]

# Metrics
duration: 3min
completed: 2026-03-20
---

# Phase 01 Plan 03: Regression Tests + Visual Verification Summary

**Two PHPUnit regression tests proving /storage/ image prefix and 5-token branding cascade both work correctly in the guest menu**

## Performance

- **Duration:** 3 min
- **Started:** 2026-03-20T18:38:33Z
- **Completed:** 2026-03-20T18:41:19Z
- **Tasks:** 1 of 2 (Task 2 at checkpoint: human-verify)
- **Files modified:** 2

## Accomplishments

- TEST-01: `test_product_image_url_includes_storage_prefix` proves the /storage/ fix from plan 01-01 is regression-tested
- TEST-02: `test_shop_branding_renders_derived_css_variables` proves all 5 derived tokens (--canvas, --panel, --panel-muted, --line, --ink-soft) appear in HTTP response
- TEST-02b: `test_shop_without_branding_does_not_emit_derived_tokens` verifies page loads without crash when no branding set
- All 16 GuestMenu tests pass (no regressions introduced)

## Task Commits

Each task was committed atomically:

1. **Task 1: Write feature tests for image URL prefix and branding cascade** - `3b05fa2` (test)

**Plan metadata:** (pending visual verification checkpoint)

## Files Created/Modified

- `tests/Feature/Livewire/GuestMenuTest.php` - Added `test_product_image_url_includes_storage_prefix` (TEST-01)
- `tests/Feature/GuestMenuBrandingTest.php` - Created new file with TEST-02 and TEST-02b

## Decisions Made

- Used `Livewire::test()` for TEST-01 because the `<img src="/storage/...">` tag is emitted by the component view (guest-menu.blade.php), not the layout
- Used `$this->get()` for TEST-02 because the `<style>:root { --canvas: ... }</style>` block is in the layout (app.blade.php), which `Livewire::test()` does NOT render
- `assertSee('--canvas:', false)` — the `false` second arg disables HTML escaping, required because CSS variable tokens contain `:` which gets escaped to `&#58;` by default

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None - implementation from plans 01-01 and 01-02 was correct; tests passed GREEN immediately.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- TEST-01 and TEST-02 requirements are complete
- Awaiting human visual verification of guest menu at http://localhost:8000/menu/demo (Task 2 checkpoint)
- Once visual verification is approved, Phase 01 (polish) is complete and Phase 02 (demo) can begin

---
*Phase: 01-polish*
*Completed: 2026-03-20*
