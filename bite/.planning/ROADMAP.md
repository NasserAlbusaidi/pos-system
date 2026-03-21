# Roadmap: Bite-POS — Sourdough Demo

## Overview

Two phases: first fix the guest menu so it looks good enough to demo, then build the Sourdough shop so there's something real to show. Phase 1 is all code — bug fixes, visual polish, branding cascade, tests. Phase 2 is data and verification — entering the full 33-item menu, configuring branding, and walking through the end-to-end flow.

## Phases

- [x] **Phase 1: Polish** - Fix image bug, overhaul guest menu visuals, derive branding cascade, add regression tests (completed 2026-03-20)
- [x] **Phase 2: Demo** - Create Sourdough shop, enter all menu data, verify end-to-end flow (completed 2026-03-21)

## Phase Details

### Phase 1: Polish
**Goal**: The guest menu renders product photos correctly, looks warm and artisan, and behaves well on mobile
**Depends on**: Nothing (first phase)
**Requirements**: GMVIZ-01, GMVIZ-02, GMVIZ-03, GMVIZ-04, GMVIZ-05, GMVIZ-06, GMVIZ-07, GMVIZ-08, GMVIZ-09, GMVIZ-10, BRND-01, BRND-02, BRND-03, TEST-01, TEST-02
**Success Criteria** (what must be TRUE):
  1. Product photos appear on the guest menu (no broken images from missing /storage/ prefix)
  2. The menu displays in a 2-column compact grid with photo, name, and price — description hidden until interaction
  3. Category headers render in Playfair Display and empty categories are not shown
  4. Entering Sourdough's 3 brand colors cascades warmth across canvas, panel, and border tokens
  5. Two feature tests pass: image URL prefix and branding CSS variable derivation
**Plans:** 3/3 plans complete

Plans:
- [x] 01-01-PLAN.md — Branding cascade + Playfair Display font + menu CSS classes
- [x] 01-02-PLAN.md — Guest menu Blade template rewrite (compact card grid)
- [x] 01-03-PLAN.md — Feature tests (image URL + branding) + visual verification

### Phase 2: Demo
**Goal**: Sourdough Oman's full menu exists in Bite-POS, branded correctly, and the complete order flow works
**Depends on**: Phase 1
**Requirements**: DEMO-01, DEMO-02, DEMO-03
**Success Criteria** (what must be TRUE):
  1. The Sourdough shop exists with paper/gold/ink brand colors applied and guest menu looks on-brand
  2. All 33 menu items are visible on the guest menu with bilingual names, correct prices, and photos
  3. A test order placed from the guest menu appears on the KDS and can be marked ready
**Plans:** 2/2 plans complete

Plans:
- [x] 02-01-PLAN.md — Create Sourdough shop + 33-item bilingual menu seeder
- [x] 02-02-PLAN.md — Smoke tests + end-to-end human verification walkthrough

## Progress

**Execution Order:** 1 → 2

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Polish | 3/3 | Complete   | 2026-03-20 |
| 2. Demo | 2/2 | Complete   | 2026-03-21 |
