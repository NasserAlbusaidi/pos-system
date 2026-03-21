---
phase: 03-item-availability
verified: 2026-03-21T05:48:33Z
status: passed
score: 8/8 must-haves verified
re_verification: false
---

# Phase 3: Item Availability Verification Report

**Phase Goal:** Operators can mark items sold out in real time and guests see accurate availability on the menu
**Verified:** 2026-03-21T05:48:33Z
**Status:** passed
**Re-verification:** No — initial verification

---

## Goal Achievement

### Observable Truths

| # | Truth | Status | Evidence |
|---|-------|--------|----------|
| 1 | Admin can toggle a product between Available and Sold Out from the product list without opening the edit form | VERIFIED | `wire:click="toggleAvailability({{ $product->id }})"` on row in product-manager.blade.php line 134; `toggleAvailability()` method in ProductManager.php line 166 |
| 2 | Admin can toggle availability from within the product edit form | VERIFIED | `@if ($editingProductId)` guard wraps second toggle button at product-manager.blade.php line 81–95; `wire:click="toggleAvailability({{ $editingProductId }})"` at line 88 |
| 3 | Toggling availability creates an audit log entry | VERIFIED | `AuditLog::record('product.restored'/'product.86d', ...)` at ProductManager.php lines 173–177; confirmed by `test_toggle_creates_audit_log` passing |
| 4 | Toast notification confirms the toggle action | VERIFIED | `$this->dispatch('toast', ...)` at ProductManager.php lines 179–184 with success/error variant; `test_toggle_dispatches_toast` passing |
| 5 | Guest menu shows unavailable products greyed out with a Sold Out badge instead of hiding them | VERIFIED | `menu-product-sold-out` class applied conditionally on article element (guest-menu.blade.php line 121); `menu-product-sold-out-badge` div rendered at lines 138–141 with `__('guest.sold_out')` |
| 6 | Sold-out items remain in their original sort position within the category | VERIFIED | render() in GuestMenu.php (line 1039) has NO `->where('is_available', true)` filter — products sorted by `sort_order` only; `test_unavailable_product_visible_in_guest_menu` passing |
| 7 | Tapping a sold-out product card shows details but the Add to Cart button is disabled | VERIFIED | `@if($product->is_available)` guard at guest-menu.blade.php line 183 — add button not rendered for sold-out; card `@click` handler still active (not guarded); `test_cannot_add_unavailable_product_to_cart` passing |
| 8 | A guest who tries to submit an order with a stale sold-out item sees a clear error and the sold-out item is auto-removed from the cart | VERIFIED | GuestMenu.php submitOrder() lines 684–713: builds `$unavailableIds`, filters `$this->cart` to remove them, sets `$this->orderError = __('guest.items_unavailable_removed', ...)`, and sets `$this->showReviewModal = true`; `test_checkout_removes_stale_unavailable_items_from_cart` passing |

**Score:** 8/8 truths verified

---

### Required Artifacts

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `app/Livewire/ProductManager.php` | toggleAvailability() method with audit logging | VERIFIED | Line 166–185: full method with tenant-scoped findOrFail, is_available flip, AuditLog::record, dispatch('toast') |
| `resources/views/livewire/product-manager.blade.php` | Inline toggle button per product row + availability indicator in edit form | VERIFIED | 2 toggle locations (list row line 134, edit form line 88); opacity-50 line-through on sold-out name (line 127); wire:loading.attr="disabled" (line 135) |
| `tests/Feature/ProductAvailabilityToggleTest.php` | Tests for toggle from list, toggle from form, audit log, tenant isolation | VERIFIED | 4 tests present and all pass (4/4) |
| `app/Livewire/GuestMenu.php` | render() includes unavailable products; submitOrder() auto-removes stale items | VERIFIED | render() line 1039 has no is_available filter; submitOrder() lines 684–713 auto-remove stale items and set items_unavailable_removed error |
| `resources/views/livewire/guest-menu.blade.php` | Greyed-out card treatment with Sold Out badge | VERIFIED | menu-product-sold-out class (line 121), badge div (lines 138–141), add button guard (line 183) |
| `resources/css/app.css` | CSS classes for sold-out product card visual treatment | VERIFIED | .menu-product-sold-out (opacity: 0.55, line 307), .menu-product-sold-out .menu-product-image-area (grayscale(0.7), line 313), .menu-product-sold-out-badge (lines 321–336), [dir="rtl"] rule (line 341) |
| `tests/Feature/GuestMenuAvailabilityTest.php` | Tests for display, add-to-cart blocking, cart validation | VERIFIED | 4 tests present and all pass (4/4) |
| `lang/en/admin.php` | product_available, product_sold_out, product_availability keys | VERIFIED | Lines 272–274 confirmed |
| `lang/ar/admin.php` | Arabic equivalents for admin translation keys | VERIFIED | Lines 272–274 confirmed (متاح, نفذ, التوفر) |
| `lang/en/guest.php` | sold_out, items_unavailable_removed keys | VERIFIED | Lines 79, 81 confirmed |
| `lang/ar/guest.php` | Arabic equivalents for guest translation keys | VERIFIED | Lines 79, 81 confirmed (نفذ, Arabic removal message) |

---

### Key Link Verification

| From | To | Via | Status | Details |
|------|----|-----|--------|---------|
| `resources/views/livewire/product-manager.blade.php` | `app/Livewire/ProductManager.php` | `wire:click="toggleAvailability` | WIRED | Pattern found on lines 88 and 134 of blade; method at line 166 of PHP |
| `app/Livewire/ProductManager.php` | `App\Models\AuditLog` | `AuditLog::record()` call | WIRED | Import at line 5; call at lines 173–177 of toggleAvailability() |
| `app/Livewire/GuestMenu.php` render() | `resources/views/livewire/guest-menu.blade.php` | categories passed without is_available filter | WIRED | render() line 1039 loads products with only is_visible filter; blade consumes `$product->is_available` conditionally |
| `resources/views/livewire/guest-menu.blade.php` | `resources/css/app.css` | CSS class `menu-product-sold-out` | WIRED | Class applied in blade line 121; defined in CSS line 307 |
| `app/Livewire/GuestMenu.php` submitOrder() | cart cleanup | auto-remove unavailable items from `$this->cart` | WIRED | Lines 686–710: builds $unavailableIds, filters cart via collect()->filter(), handles both solo and group cart modes |

---

### Requirements Coverage

| Requirement | Source Plan | Description | Status | Evidence |
|-------------|------------|-------------|--------|----------|
| AVAIL-01 | 03-01-PLAN.md | Admin/manager can toggle a product's availability on/off from the menu builder | SATISFIED | ProductManager.toggleAvailability() exists; inline list toggle + edit form toggle both wired; 4 tests pass |
| AVAIL-02 | 03-02-PLAN.md | Guest menu shows unavailable products greyed out with a "Sold Out" badge instead of hiding them | SATISFIED | render() filter removed; blade adds menu-product-sold-out class + badge; CSS rules present; test_unavailable_product_visible_in_guest_menu passes |
| AVAIL-03 | 03-02-PLAN.md | Guest cart validates item availability at checkout and surfaces a clear error if a stale item is in the cart | SATISFIED | submitOrder() auto-removes stale items + sets items_unavailable_removed error; test_checkout_removes_stale_unavailable_items_from_cart passes |

No orphaned requirements — REQUIREMENTS.md maps AVAIL-01, AVAIL-02, AVAIL-03 to Phase 3, all three are claimed by plans 01 and 02 respectively, all three are satisfied.

---

### Anti-Patterns Found

None. Scanned all phase-modified files for TODO/FIXME/placeholder/empty implementations. No blockers or warnings found. The "placeholder" hits in product-manager.blade.php are HTML input `placeholder=` attributes (legitimate form UX), not implementation stubs.

---

### Human Verification Required

#### 1. Visual sold-out treatment on guest menu

**Test:** Open a shop's guest menu (e.g., `/menu/demo`), mark a product sold out via ProductManager, then reload the guest menu page.
**Expected:** The sold-out product card appears visually dimmed (55% opacity), its image is desaturated (grayscale), a dark "Sold Out" badge appears in the top-right corner, and there is no "+" add button on the card. The card remains tappable to expand the product description.
**Why human:** CSS/visual rendering cannot be verified programmatically. The class wiring is confirmed but visual outcome requires browser inspection.

#### 2. RTL "Sold Out" badge positioning

**Test:** Load the guest menu with Arabic locale (`/menu/demo` with Arabic selected). Mark a product sold out.
**Expected:** The "Sold Out" badge ("نفذ") appears in the top-LEFT corner of the card (RTL direction), not the right. No letter-spacing artifacts on the Arabic text.
**Why human:** Directional CSS properties ([dir="rtl"] rule) require visual browser verification.

#### 3. Toast notification in admin view

**Test:** Log in as admin, go to the menu builder. Toggle a product's availability from the product list row.
**Expected:** A toast notification appears (success green for "now available", error red for "marked as sold out") with the product name in the message.
**Why human:** Toast dispatch is verified by tests but the actual UI rendering of the toast component requires browser observation.

---

### Gaps Summary

None. All 8 observable truths are verified, all 11 required artifacts pass all three levels (exists, substantive, wired), all 5 key links are confirmed, all 3 requirements are satisfied, and both test suites pass (8/8 tests across 2 suites). Phase goal is fully achieved.

---

## Test Run Results

```
PASS  Tests\Feature\ProductAvailabilityToggleTest
  can toggle product availability from list     0.26s
  toggle creates audit log                      0.02s
  toggle dispatches toast                       0.01s
  toggle scoped to shop                         0.01s
  Tests: 4 passed (9 assertions) — Duration: 0.37s

PASS  Tests\Feature\GuestMenuAvailabilityTest
  unavailable product visible in guest menu     0.21s
  cannot add unavailable product to cart        0.01s
  checkout removes stale unavailable items      0.02s
  category with only unavailable products shown 0.01s
  Tests: 4 passed (8 assertions) — Duration: 0.30s
```

---

_Verified: 2026-03-21T05:48:33Z_
_Verifier: Claude (gsd-verifier)_
