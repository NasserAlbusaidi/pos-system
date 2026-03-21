---
phase: 03-item-availability
plan: 02
subsystem: guest-menu
tags: [availability, ux, guest-menu, sold-out, cart]
dependency_graph:
  requires: [03-01]
  provides: [guest-menu-sold-out-display, cart-stale-item-recovery]
  affects: [app/Livewire/GuestMenu.php, resources/views/livewire/guest-menu.blade.php, resources/css/app.css]
tech_stack:
  added: []
  patterns: [tdd-red-green, livewire-render-filter-removal, css-design-tokens]
key_files:
  created:
    - tests/Feature/GuestMenuAvailabilityTest.php
  modified:
    - app/Livewire/GuestMenu.php
    - resources/views/livewire/guest-menu.blade.php
    - resources/css/app.css
    - lang/en/guest.php
    - lang/ar/guest.php
decisions:
  - "Removed is_available filter from GuestMenu render() — unavailable products now show greyed-out instead of being hidden"
  - "Auto-remove stale cart items on submitOrder() with new items_unavailable_removed key — better UX than returning an error and asking the user to manually remove items"
  - "Sold Out badge uses dark semi-transparent background (--ink/0.75) with paper text — visually distinct without requiring a new color token"
  - "Add button simply not rendered for sold-out items (not disabled) — cleaner HTML, no need for disabled state styling"
metrics:
  duration_minutes: 2
  completed_date: "2026-03-21"
  tasks_completed: 2
  files_changed: 7
---

# Phase 03 Plan 02: Guest Menu Sold-Out Display Summary

Guest menu now shows unavailable products as greyed-out cards with a "Sold Out" badge and JetBrains Mono styling, while stale cart items are auto-removed at checkout with a clear bilingual error message.

## What Was Built

### Task 1: GuestMenu PHP + translations (TDD)

**RED:** Created `tests/Feature/GuestMenuAvailabilityTest.php` with 4 tests covering: visibility of unavailable products, addToCart blocking, checkout auto-removal of stale items, and category display with only-unavailable products.

**GREEN:**
- Removed `->where('is_available', true)` from `GuestMenu::render()` products eager load. The `is_visible` filter remains — hidden products are still hidden, but sold-out visible products now appear.
- Updated `submitOrder()` stale item handling: instead of returning an error and leaving the cart unchanged, it now auto-removes the unavailable items from `$this->cart` (or from `GroupCart` via locked DB transaction in group mode), sets the new `items_unavailable_removed` error message, and lets the guest see what was removed.
- Added `sold_out` and `items_unavailable_removed` translation keys to both `lang/en/guest.php` and `lang/ar/guest.php`.

### Task 2: Blade + CSS visual treatment

- `<article>` element gets `menu-product-sold-out` class when `!$product->is_available`
- "Sold Out" badge rendered inside image area using `menu-product-sold-out-badge` class with `__('guest.sold_out')` text (bilingual)
- `+` add button not rendered for sold-out products (card still tappable for accordion description)
- CSS added after `.menu-product-add:active` rule:
  - `.menu-product-sold-out`: 55% opacity, position:relative
  - `.menu-product-sold-out .menu-product-image-area`: grayscale(0.7)
  - `.menu-product-sold-out .menu-product-body`: 70% opacity
  - `.menu-product-sold-out-badge`: absolute positioned, dark ink/0.75 bg, paper text, JetBrains Mono 9px
  - `[dir="ltr"]` badge at `right: 8px`, `[dir="rtl"]` badge at `left: 8px` with `letter-spacing: 0`

## Commits

| Task | Commit | Description |
|------|--------|-------------|
| Test (RED) | 7653ba5 | test(03-02): add failing tests for guest menu availability display |
| Task 1 (GREEN) | 1173798 | feat(03-02): show unavailable products in guest menu and auto-remove stale cart items |
| Task 2 | f15b0ad | feat(03-02): add sold-out visual treatment to guest menu |

## Deviations from Plan

None — plan executed exactly as written.

## Self-Check

**Files created/modified:**
- [x] `tests/Feature/GuestMenuAvailabilityTest.php` — created
- [x] `app/Livewire/GuestMenu.php` — modified (render filter removed, submitOrder updated)
- [x] `resources/views/livewire/guest-menu.blade.php` — modified (sold-out class, badge, button guard)
- [x] `resources/css/app.css` — modified (sold-out CSS rules added)
- [x] `lang/en/guest.php` — modified (sold_out, items_unavailable_removed)
- [x] `lang/ar/guest.php` — modified (sold_out, items_unavailable_removed)

**Verification:**
- [x] `php artisan test --filter=GuestMenuAvailabilityTest` — 4/4 passed
- [x] `npm run build` — exits 0 (86.25 kB CSS)
- [x] `grep "menu-product-sold-out" resources/views/livewire/guest-menu.blade.php` — 2 matches
- [x] `grep "menu-product-sold-out" resources/css/app.css` — 6 matches
- [x] `grep "sold_out" lang/en/guest.php` — key exists
- [x] GuestMenu render() does NOT contain `->where('is_available', true)` in products eager load

## Self-Check: PASSED
