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

- **Duration:** ~20 min (including visual verification)
- **Started:** 2026-03-20T18:38:33Z
- **Completed:** 2026-03-20T18:55:00Z
- **Tasks:** 2 of 2 (complete)
- **Files modified:** 4

## Accomplishments

- TEST-01: `test_product_image_url_includes_storage_prefix` proves the /storage/ fix from plan 01-01 is regression-tested
- TEST-02: `test_shop_branding_renders_derived_css_variables` proves all 5 derived tokens (--canvas, --panel, --panel-muted, --line, --ink-soft) appear in HTTP response
- TEST-02b: `test_shop_without_branding_does_not_emit_derived_tokens` verifies page loads without crash when no branding set
- All 16 GuestMenu tests pass (no regressions introduced)
- Human visual verification approved: 2-column grid, Playfair Display category headers, warm gradient, accordion, shimmer loading, OMR prices, gold accent on + button and cart bar only

## Task Commits

Each task was committed atomically:

1. **Task 1: Write feature tests for image URL prefix and branding cascade** - `3b05fa2` (test)
2. **Task 2: Visual verification of guest menu overhaul** - user approved at checkpoint (no code commit — human verification gate)

**Plan metadata:** `3333017` (docs: complete regression tests plan)

## Files Created/Modified

- `tests/Feature/Livewire/GuestMenuTest.php` - Added `test_product_image_url_includes_storage_prefix` (TEST-01)
- `tests/Feature/GuestMenuBrandingTest.php` - Created new file with TEST-02 and TEST-02b
- `app/Http/Middleware/SecurityHeaders.php` - Added 'unsafe-eval' to CSP script-src (deviation fix during visual testing)
- `resources/views/livewire/guest-menu.blade.php` - Scaled placeholder SVG from 24x24 to 48x48 (deviation fix during visual testing)

## Decisions Made

- Used `Livewire::test()` for TEST-01 because the `<img src="/storage/...">` tag is emitted by the component view (guest-menu.blade.php), not the layout
- Used `$this->get()` for TEST-02 because the `<style>:root { --canvas: ... }</style>` block is in the layout (app.blade.php), which `Livewire::test()` does NOT render
- `assertSee('--canvas:', false)` — the `false` second arg disables HTML escaping, required because CSS variable tokens contain `:` which gets escaped to `&#58;` by default

## Deviations from Plan

Two auto-fixes applied during visual verification (Task 2):

### Auto-fixed Issues

**1. [Rule 3 - Blocking] CSP 'unsafe-eval' needed for Livewire/Alpine.js**
- **Found during:** Task 2 (visual verification — guest menu wouldn't render Alpine reactive state)
- **Issue:** Content-Security-Policy script-src directive was missing 'unsafe-eval', which Alpine.js requires for reactive bindings. The menu loaded but Alpine directives silently failed (accordion, shimmer, + button).
- **Fix:** Added 'unsafe-eval' to the script-src directive in `app/Http/Middleware/SecurityHeaders.php`
- **Files modified:** `app/Http/Middleware/SecurityHeaders.php`
- **Verification:** Alpine accordion and + button worked correctly after fix

**2. [Rule 1 - Bug] Placeholder SVG too small at 24x24 in 120px container**
- **Found during:** Task 2 (visual verification — placeholder looked like a tiny dot)
- **Issue:** The placeholder icon SVG was 24x24px but rendered inside a 120px image container, making it barely visible
- **Fix:** Scaled the SVG viewBox and rendered size from 24x24 to 48x48
- **Files modified:** `resources/views/livewire/guest-menu.blade.php`
- **Verification:** Placeholder is now clearly visible at correct size in image area

---

**Total deviations:** 2 auto-fixed (1 blocking, 1 bug)
**Impact on plan:** Both fixes were required for the visual verification to pass. No scope creep.

## Issues Encountered

None - implementation from plans 01-01 and 01-02 was correct; tests passed GREEN immediately.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- TEST-01 and TEST-02 requirements are complete
- Human visual verification approved — guest menu aesthetic confirmed correct
- Phase 01 (polish) is fully complete: branding cascade, Playfair Display, compact grid, regression tests, and visual sign-off
- Phase 02 (demo) can begin: create the Sourdough Oman shop with demo data to support the first client pitch

## Self-Check: PASSED

- FOUND: .planning/phases/01-polish/01-03-SUMMARY.md
- FOUND: tests/Feature/Livewire/GuestMenuTest.php
- FOUND: tests/Feature/GuestMenuBrandingTest.php
- FOUND: commit 3b05fa2 (test(01-03): add TEST-01 and TEST-02 regression tests)
- FOUND: commit 3333017 (docs(01-03): complete regression tests plan)

---
*Phase: 01-polish*
*Completed: 2026-03-20*
