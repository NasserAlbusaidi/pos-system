---
phase: 05-menu-themes
plan: 01
subsystem: guest-menu-theming
tags: [css, themes, fonts, livewire, tdd]
dependency_graph:
  requires: []
  provides:
    - "[data-theme] CSS cascade in app.css"
    - "Inter, DM Sans, DM Serif Display woff2 fonts in public/fonts/"
    - "data-theme attribute on html element in layouts/app.blade.php"
    - "ShopSettings.theme property with validation and persistence"
    - "GuestMenu passes theme to view"
  affects:
    - "resources/css/app.css (all guest menu CSS classes now use theme tokens)"
    - "resources/views/layouts/app.blade.php (html element has data-theme)"
    - "app/Livewire/ShopSettings.php (theme save/load)"
    - "app/Livewire/GuestMenu.php (theme passed to view)"
tech_stack:
  added:
    - "Inter variable woff2 (400, 600) — modern theme body font"
    - "DM Sans woff2 (400, 500) — dark theme body font"
    - "DM Serif Display woff2 (400) — dark theme display font"
  patterns:
    - "[data-theme] CSS attribute selectors for structural and palette tokens"
    - "Blade @php before html tag for pre-render variable computation"
    - "TDD: test files created first, confirmed RED, then implementation made them GREEN"
key_files:
  created:
    - public/fonts/Inter-Regular.woff2
    - public/fonts/Inter-SemiBold.woff2
    - public/fonts/DMSans-Regular.woff2
    - public/fonts/DMSans-Medium.woff2
    - public/fonts/DMSerifDisplay-Regular.woff2
    - tests/Feature/MenuThemeRenderTest.php
    - tests/Feature/ShopSettingsThemeTest.php
  modified:
    - resources/css/app.css
    - resources/views/layouts/app.blade.php
    - app/Livewire/ShopSettings.php
    - app/Livewire/GuestMenu.php
decisions:
  - "Theme computed in @php block BEFORE the html tag — not inside @if(isset($shop)) — so the attribute renders with the correct value"
  - "Warm theme defaults preserve Sourdough object-contain requirement (warm uses contain, modern/dark use cover)"
  - "Theme tokens use CSS custom properties with fallbacks so non-themed pages degrade gracefully"
  - "Theme palette tokens (--paper, --ink, --crema) in [data-theme] blocks are overridden by the inline <style> when shop has custom branding — cascade ordering preserved"
metrics:
  duration: "~6 minutes"
  completed_date: "2026-03-21"
  tasks_completed: 2
  files_modified: 7
  files_created: 7
  tests_added: 10
  tests_total: 188
---

# Phase 05 Plan 01: Menu Themes — CSS Foundation and Backend Wiring Summary

CSS theming cascade with three data-theme blocks (warm/modern/dark), self-hosted Inter/DM Sans/DM Serif Display fonts, and backend save/load/validate wiring in ShopSettings + GuestMenu.

## Tasks Completed

| Task | Name | Commit | Files |
|------|------|--------|-------|
| 1 | Font files and CSS theme foundation | 99d3b25 | app.css, 5 woff2 files |
| 2 | Backend wiring — layout, ShopSettings, GuestMenu, tests | 903053d | app.blade.php, ShopSettings.php, GuestMenu.php, 2 test files |

## What Was Built

### Task 1: Font Files and CSS Theme Foundation

Downloaded 5 WOFF2 font files from Google Fonts (latin subset only):
- Inter Regular (400) and SemiBold (600) — for modern theme
- DM Sans Regular (400) and Medium (500) — for dark theme body text
- DM Serif Display Regular (400) — for dark theme display/headers

Added `@font-face` declarations for all 5 fonts in `resources/css/app.css` with `font-display: swap`.

Added three `[data-theme]` CSS blocks inside `@layer components`:

**warm** — 12px card radius, 2-column grid, 140px images, Rubik+Playfair Display, warm palette defaults
**modern** — 0px radius, 1-column list, 80px images, Inter everywhere, high-contrast palette defaults
**dark** — 8px radius, 1-column list, 200px images, DM Sans+DM Serif Display, dark palette defaults

Updated existing menu CSS classes to use theme token variables with fallbacks:
- `.menu-product-grid` → `var(--theme-grid-cols, repeat(2, 1fr))`
- `.menu-product-card` → `var(--theme-card-radius, 12px)` + shadow + border tokens
- `.menu-product-image-area` → `var(--theme-image-height, 120px)`
- `.menu-product-body` → `var(--theme-card-padding, 8px)`
- `.menu-product-name` → `var(--theme-body-font, 'Rubik', system-ui, sans-serif)`
- `.menu-category-header` → `var(--theme-display-font, 'Playfair Display', Georgia, serif)`

Added per-theme category header treatments, image overlay for dark, RTL font fallbacks, theme picker CSS classes, and responsive breakpoints.

### Task 2: Backend Wiring (TDD)

Tests written first and confirmed failing (RED) before implementation.

**Layout injection:** Added a `@php` block before the `<html>` tag that computes `$theme` (defaulting to `'warm'`, reading from `$shop->branding['theme']` with allowlist validation) so the `data-theme` attribute on `<html>` renders with the correct value.

**ShopSettings:** Added `public $theme = 'warm'` property, `mount()` loading with allowlist fallback, `save()` validation (`required|in:warm,modern,dark`), and branding persistence (`'theme' => $this->theme`).

**GuestMenu:** Added `$theme` computation in `render()` with allowlist validation, passed to view as `'theme' => $theme`.

## Test Results

```
Tests:    188 passed (458 assertions)
Duration: 4.87s
```

10 new tests added:
- `MenuThemeRenderTest`: 5 tests (dark/modern/warm default/invalid fallback/with brand colors)
- `ShopSettingsThemeTest`: 5 tests (mount loads/mount defaults/save persists/colors preserved/invalid rejected)

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Theme variable must be computed before `<html>` tag**
- **Found during:** Task 2 implementation
- **Issue:** The plan suggested adding theme extraction inside the `@if(isset($shop))` block (which is inside `<head>`, after the `<html>` tag). This would cause `data-theme` to always render as `'warm'` regardless of shop setting, because the `$theme` variable update happens after the HTML tag is already output.
- **Fix:** Moved all theme computation into a new `@php` block placed between `<!DOCTYPE html>` and the `<html>` tag, before any HTML output. The `@if(isset($shop))` `@php` block continues to handle branding color cascade, but `$theme` is no longer computed there.
- **Files modified:** `resources/views/layouts/app.blade.php`
- **Commit:** 903053d

## Self-Check: PASSED

All files created, all commits present, all 188 tests passing.
