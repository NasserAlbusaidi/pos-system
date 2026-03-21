---
phase: 02-demo
plan: 02
subsystem: testing
tags: [smoke-tests, guest-menu, branding, bilingual, tdd, kds, sourdough-demo]

# Dependency graph
requires:
  - phase: 02-demo
    plan: 01
    provides: Sourdough shop seeder and demo data
  - phase: 01-polish
    provides: Guest menu with warm branding cascade and placeholder icons
provides:
  - SourdoughDemoTest.php — 4 smoke tests covering guest menu load, branding tokens, product rendering, bilingual names
  - Human-verified end-to-end demo: guest menu browse -> order placement -> KDS completion
affects:
  - CI pipeline — new test class runs on every push
  - Sourdough client pitch — demo confirmed ready

# Tech tracking
tech-stack:
  added: []
  patterns:
    - Inline test data creation (not seeder) for SQLite in-memory compatibility
    - Explicit shop_id assignment via property for guarded tenant field in tests
    - assertSee with false param to disable HTML escaping for CSS variable assertions
    - App::setLocale('ar') in test to verify bilingual rendering

key-files:
  created:
    - tests/Feature/SourdoughDemoTest.php
  modified: []

key-decisions:
  - "Test data created inline in setUp() — seeder data not available in SQLite in-memory test DB"
  - "4 smoke tests only — focused on demo-critical paths (load, branding, products, bilingual)"
  - "Human verification confirmed: placeholder icons satisfy DEMO-02 for pitch without real photos"
  - "Human verification checkpoint for interactive order flow — cannot be automated in feature tests"

requirements-completed: [DEMO-03]

# Metrics
duration: ~25min
completed: 2026-03-21
---

# Phase 2 Plan 2: Sourdough Demo Verification Summary

**4 smoke tests confirm guest menu loads at /menu/sourdough with warm branding and bilingual 33-item menu; human-verified end-to-end order flow from guest menu to KDS completion — Sourdough demo is pitch-ready**

## Performance

- **Duration:** ~25 min
- **Started:** 2026-03-20T19:40:52Z
- **Completed:** 2026-03-21T04:22:06Z
- **Tasks:** 2 of 2
- **Files modified:** 1

## Accomplishments

- SourdoughDemoTest.php created with 4 passing smoke tests covering guest menu load, branding CSS tokens, product rendering across categories, and bilingual Arabic names
- Human-verified the complete interactive demo: warm paper/gold color scheme renders, all 33 items visible across 6 categories with placeholder fork-knife icons, order placement from guest menu works end-to-end
- KDS confirmed: order placed from guest menu appears in kitchen display and status transitions (paid -> preparing -> ready) function correctly
- Sourdough demo is ready for the bakery owner pitch

## Task Commits

Each task was committed atomically:

1. **Task 1: Create SourdoughDemoTest smoke tests** - `d8fa65e` (test)
2. **Task 2: End-to-end demo walkthrough** - human-verify checkpoint, approved by user

**Checkpoint metadata:** `b97c9a7` (docs: checkpoint after task 1)

## Files Created/Modified

- `tests/Feature/SourdoughDemoTest.php` — 4 smoke tests covering the Sourdough demo's critical paths; inline test data (not seeder); 118 lines

## Decisions Made

- Created test data inline in `setUp()` rather than relying on SourdoughMenuSeeder — seeder targets MySQL, tests use SQLite in-memory; they are separate databases
- Used explicit `$product->shop_id = $this->shop->id` assignment pattern (consistent with Plan 01 established pattern)
- `assertSee('--canvas:', false)` — the `false` parameter disables HTML escaping, required because colons are escaped to `&#58;` by default (established pattern from Phase 1)
- Human verification confirmed that Phase 1 placeholder fork-knife SVG icons display acceptably for the demo pitch, satisfying DEMO-02 without real product photos

## Deviations from Plan

None — plan executed exactly as written. Smoke tests matched the spec, all 4 passed on first run. Human verification approved without issues.

## Issues Encountered

None.

## User Setup Required

None — no external service configuration required.

## Next Phase Readiness

- Sourdough demo is fully ready for the pitch. Guest menu at /menu/sourdough is live with 33 bilingual items, warm branding, and functional order flow.
- Phase 02 (demo) is complete.
- Future work: actual product photos from the Sourdough client can be uploaded via the admin panel to fully satisfy DEMO-02 post-pitch.

---
*Phase: 02-demo*
*Completed: 2026-03-21*

## Self-Check: PASSED

- FOUND: tests/Feature/SourdoughDemoTest.php
- FOUND: .planning/phases/02-demo/02-02-SUMMARY.md
- FOUND: commit d8fa65e (test(02-02): add SourdoughDemoTest smoke tests)
- FOUND: commit b97c9a7 (docs(02-02): checkpoint — task 1 complete, awaiting human verify)
