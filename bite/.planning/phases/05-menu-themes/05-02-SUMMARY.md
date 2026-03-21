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
duration: ~2 hours (including 4 post-checkpoint fix commits)
completed: 2026-03-21
---

# Phase 05 Plan 02: Menu Themes — Layout Structures and Theme Picker Summary

Three structurally distinct guest menu card layouts (warm 2-column grid, modern horizontal list, dark hero overlay) with Alpine.js theme picker in shop settings showing wireframe mockups and live inline-style preview — all 5 theme success criteria satisfied.

## Performance

- **Duration:** ~2 hours (including 4 post-checkpoint polish/fix commits)
- **Started:** 2026-03-21T08:10:16Z
- **Completed:** 2026-03-21 (human verification approved)
- **Tasks:** 2 of 2 complete
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
2. **Task 2: Visual verification** - Human checkpoint approved

**Post-checkpoint fix commits (orchestrator):**
- `943ae9a` - fix(05): polish theme picker UI and guest menu card styles
- `2a6c56f` - fix(05): rebuild theme picker — wireframe mockups, working preview
- `7167c76` - fix(05): inline all theme picker styles, remove dead CSS classes
- `a9ccd57` - fix(05): move theme CSS tokens/overrides outside @layer components

## Files Created/Modified

- `resources/views/livewire/guest-menu.blade.php` - Three conditional card layout blocks replacing single card
- `resources/views/livewire/shop-settings.blade.php` - Theme picker section with static mockups and Alpine live preview
- `resources/css/app.css` - `.menu-badge-sale`, `.menu-card-modern-inner/image/content/footer`, `.menu-card-dark-overlay/desc`, RTL `.menu-card-modern-inner`

## Decisions Made

- Modern card uses a dedicated `div.menu-card-modern-image` (80x80) instead of the existing `.menu-product-image-area` — the image-area class is used for the dark hero where it spans full width, so keeping them separate avoids a height conflict
- `.menu-badge-sale` base class replaces per-card inline Tailwind strings — the badge is shared across all 3 card templates, making it cleaner to position consistently
- Warm card wrapped in `@else` (not `@elseif`) to act as a safe fallback for any invalid theme values that somehow bypass the allowlist

## Deviations from Plan

### Post-Checkpoint Fixes (4 additional commits)

Task 1 implementation was functionally complete at commit f6d767f, but visual QA during the human checkpoint revealed several rendering issues that required additional fix commits:

**1. [Rule 1 - Bug] Theme picker checkmark rendering outside card / [x-cloak] missing**
- **Found during:** Visual QA (post-checkpoint)
- **Issue:** `[x-cloak]` CSS rule was missing; Alpine-hidden elements were visible during initialization
- **Fix:** Added `[x-cloak] { display: none; }` rule; simplified Alpine binding for checkmark
- **Committed in:** 2a6c56f

**2. [Rule 1 - Bug] Live preview not working — CSS custom property cascade failure**
- **Found during:** Visual QA (post-checkpoint)
- **Issue:** Theme picker live preview relied on toggling `data-theme` on a nested div, but admin layout does not set `[data-theme]` on `<html>`, so the CSS variable cascade context was broken
- **Fix:** Rewrote live preview to use hardcoded Alpine `:style` bindings (bg, text, card colors) per theme, bypassing CSS variable cascade entirely
- **Committed in:** 2a6c56f

**3. [Rule 1 - Bug] Theme picker CSS classes in @layer not applying**
- **Found during:** Post-checkpoint polish
- **Issue:** Theme picker card styles defined in `@layer components` were not applied (Tailwind's layering establishes a context where un-layered CSS always wins)
- **Fix:** Moved all theme picker styles to inline Blade element styles; removed all now-dead `.theme-card*`, `.theme-picker-grid`, `.theme-mockup` CSS classes
- **Committed in:** 7167c76

**4. [Rule 1 - Bug] Theme token blocks inside @layer losing to inline branding styles**
- **Found during:** Post-checkpoint polish
- **Issue:** `[data-theme]` blocks inside `@layer components` had lower specificity than the un-layered inline `<style>` block for shop brand colors — theme overrides were not applying on themed pages
- **Fix:** Moved all `[data-theme]` blocks, category header treatments, image overlays, letter-spacing rules, and sale badge overrides outside `@layer` so they always win specificity
- **Committed in:** a9ccd57

---

**Total deviations:** 4 post-checkpoint auto-fixes (all Rule 1 — bugs)
**Impact on plan:** All fixes required for correct visual rendering. No scope creep — all changes stayed within guest-menu.blade.php, shop-settings.blade.php, and app.css.

## Issues Encountered

- CSS `@layer` specificity caused two separate issues (picker styles not applying; theme tokens losing to branding inline styles). Root cause: Tailwind establishes a layering context and un-layered CSS always beats layered CSS in specificity. Resolution: keep base component classes in `@layer components` but place all theme override rules outside any layer.
- Alpine live preview initially used `data-theme` switching on a nested div, which failed because admin layout doesn't set `[data-theme]` on `<html>`. Inline `:style` bindings with hardcoded palette values proved correct for admin-context previews.

## Next Phase Readiness

- Phase 05 (Menu Themes) is complete. Human verification approved. All 5 success criteria met.
- Phase 06 (Custom Fonts) can begin. Key deliverables: font validation, SSRF allowlist, `@font-face` self-hosting pattern.
- Remaining concern from STATE.md: GD WebP support unverified on production server — health check should be created before image pipeline ships.
- Google Fonts CSS2 woff2 URL format stability concern noted in STATE.md for Phase 6 implementation.

## Self-Check

- File exists: resources/views/livewire/guest-menu.blade.php — YES
- File exists: resources/views/livewire/shop-settings.blade.php — YES
- File exists: resources/css/app.css — YES
- Commit f6d767f exists: YES (task 1 — three layout structures)
- Commit 943ae9a exists: YES (post-checkpoint polish)
- Commit 2a6c56f exists: YES (theme picker rebuild)
- Commit 7167c76 exists: YES (inline styles, remove dead CSS)
- Commit a9ccd57 exists: YES (theme CSS outside @layer)
- Human verification: APPROVED

## Self-Check: PASSED

All files modified, all commits present, human verification approved.
