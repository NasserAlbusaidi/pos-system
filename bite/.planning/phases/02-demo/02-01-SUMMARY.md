---
phase: 02-demo
plan: 01
subsystem: database
tags: [seeder, menu, bilingual, branding, mysql, laravel]

# Dependency graph
requires:
  - phase: 01-polish
    provides: Guest menu with warm branding cascade, placeholder icons satisfying photo requirement
provides:
  - Sourdough Oman shop in database (slug=sourdough, branding paper/accent/ink)
  - Admin user admin@sourdough.om for shop access
  - 6 bakery categories with Arabic translations
  - 33 bilingual products with OMR prices across all categories
  - SourdoughMenuSeeder.php — idempotent, safe to re-run
affects:
  - 02-02 (guest menu visual demo — uses sourdough shop slug to verify branding)

# Tech tracking
tech-stack:
  added: []
  patterns:
    - Explicit shop_id assignment for guarded fields (Product, User) — bypasses mass-assignment protection correctly
    - trial_ends_at and status set via direct property assignment after Shop::create() — not in $fillable
    - Idempotency guard in seeder run() — checks slug before executing

key-files:
  created:
    - database/seeders/SourdoughMenuSeeder.php
  modified: []

key-decisions:
  - "Explicit product->shop_id = $shop->id assignment instead of forceCreate — cleaner than bypassing all guards"
  - "status='active' (not 'trial') + trial_ends_at 10 years — demo shop should never expire"
  - "No image_url on products — Phase 1 fork-knife placeholder icons satisfy DEMO-02 photo requirement for pitch"
  - "Seeder is idempotent — safe to run in CI or after a fresh migrate"

patterns-established:
  - "Product tenant isolation: always set shop_id via explicit property, never via mass assignment"
  - "User tenant isolation: always use User::forceCreate() when shop_id and role must be set together"

requirements-completed: [DEMO-01, DEMO-02]

# Metrics
duration: 4min
completed: 2026-03-20
---

# Phase 2 Plan 1: Sourdough Menu Seeder Summary

**Sourdough Oman shop seeded in database with 33 bilingual bakery products across 6 categories, warm parchment branding, and admin user — pitch-ready at /menu/sourdough**

## Performance

- **Duration:** 4 min
- **Started:** 2026-03-20T19:32:54Z
- **Completed:** 2026-03-20T19:37:30Z
- **Tasks:** 2
- **Files modified:** 1

## Accomplishments

- Sourdough Oman shop created with authentic brand colors (paper #F5F0E8, accent #C4975A, ink #2C2520) and 10-year trial for permanent demo access
- 33 bilingual bakery products across 6 categories (Breads, Pastries, Sandwiches, Salads & Bowls, Beverages, Desserts) with proper Arabic food names
- Admin user admin@sourdough.om provisioned; seeder is idempotent and safe to re-run after migrate:fresh

## Task Commits

Each task was committed atomically:

1. **Task 1 + 2: Create SourdoughMenuSeeder and verify data** - `466f70b` (feat)

## Files Created/Modified

- `database/seeders/SourdoughMenuSeeder.php` — Self-contained seeder creating shop, admin user, 6 categories, 33 products; idempotent via slug check

## Decisions Made

- Used explicit `$product->shop_id = $shop->id` assignment (not `forceCreate`) to set the guarded field — leaves other guards intact and is semantically clearer
- Set `status = 'active'` and `trial_ends_at = now()->addYears(10)` via direct property after create — these fields are not in Shop's `$fillable` so must bypass mass assignment separately
- No image_url on products — the Phase 1 fork-knife placeholder SVG from Plan 01-01 displays automatically, satisfying DEMO-02's photo requirement for the pitch

## Deviations from Plan

None — plan executed exactly as written.

One minor observation: the DemoMenuSeeder uses `Product::create(['shop_id' => ...])` which silently ignores shop_id (it's guarded). The plan correctly flagged this and specified using explicit assignment instead. The fix was built into the implementation per plan spec, not discovered as a deviation.

## Issues Encountered

None — seeder ran cleanly on first attempt.

## User Setup Required

None — no external service configuration required.

## Next Phase Readiness

- Sourdough shop is fully seeded and ready for Plan 02-02 guest menu visual verification
- Navigate to /menu/sourdough in a running server to see the branded guest menu with 33 items
- Run `php artisan db:seed --class=SourdoughMenuSeeder` is idempotent — safe to call after any migrate:fresh

---
*Phase: 02-demo*
*Completed: 2026-03-20*
