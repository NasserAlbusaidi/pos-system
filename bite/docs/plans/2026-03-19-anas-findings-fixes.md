# Anas Findings Fix Plan — 19 March 2026

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Fix all 12 open issues from Anas's testing of Bite POS staging.

**Architecture:** Surgical fixes across Livewire components and Blade views. No new models or migrations needed. Most fixes are 1-3 line changes. Two bugs require deeper investigation (modifier override, pricing rules).

**Tech Stack:** Laravel 12, Livewire 3, Vanilla CSS, SQLite (staging), MySQL (production)

---

## Priority Order

| # | Task | Severity | Effort |
|---|------|----------|--------|
| 1 | Pricing rules not applying | HIGH | 30 min |
| 2 | Modifier override bug (size + custom) | HIGH | 45 min |
| 3 | Menu Builder Arabic display | MEDIUM | 5 min |
| 4 | 86'd item validation on submit | MEDIUM | 15 min |
| 5 | Order cancellation from POS | MEDIUM | 30 min |
| 6 | Shift Report blank download | LOW | 15 min |
| 7 | OMR 3-decimal rounding | LOW | 10 min |
| 8 | Modifier size display notes | LOW | 10 min |
| 9 | Modifier extra cost decimal input | LOW | 5 min |
| 10 | Modifier group deletion | LOW | 20 min |
| 11 | Pricing rules question (auto-clear) | LOW | 5 min |
| 12 | Audit logs tabs | LOW | 20 min |

---

### Task 1: Pricing Rules Not Applying to Menu Items

**Bug:** Anas created pricing rules but they don't apply to menu items.

**Root Cause (likely):** Two issues:

1. **Time format mismatch on SQLite.** Admin saves `start_time`/`end_time` as `H:i` (e.g., `14:00`) but `scopeActiveNow()` compares against `H:i:s` (e.g., `14:00:00`). On SQLite, this is a string comparison where `'14:00' < '14:00:00'` is true (shorter prefix), causing edge-case failures.

2. **JSON type mismatch.** `days_of_week` stores values as strings from Livewire form (`["0","3"]`) but `whereJsonContains('days_of_week', $currentDay)` passes an integer. On SQLite, this type mismatch can cause the query to return no results.

**Files:**
- Fix: `app/Models/PricingRule.php:51-64` (scopeActiveNow)
- Fix: `app/Livewire/Admin/PricingRules.php` (ensure days_of_week values are integers on save)
- Test: `tests/Unit/PricingRuleTest.php` (new)

**Step 1: Write failing test**

```php
// tests/Unit/PricingRuleTest.php
<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\PricingRule;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PricingRuleTest extends TestCase
{
    use RefreshDatabase;

    private Shop $shop;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shop = Shop::factory()->create();
        $category = Category::factory()->create(['shop_id' => $this->shop->id]);
        $this->product = Product::factory()->create([
            'shop_id' => $this->shop->id,
            'category_id' => $category->id,
            'price' => 1.000,
        ]);
    }

    public function test_active_now_scope_matches_rule_within_time_window(): void
    {
        $rule = PricingRule::create([
            'shop_id' => $this->shop->id,
            'name' => 'Test Discount',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'start_time' => '08:00',
            'end_time' => '23:00',
            'days_of_week' => null,
            'is_active' => true,
        ]);

        $now = Carbon::parse('2026-03-19 14:30:00');
        $active = PricingRule::where('shop_id', $this->shop->id)->activeNow($now)->get();

        $this->assertCount(1, $active);
        $this->assertEquals($rule->id, $active->first()->id);
    }

    public function test_active_now_scope_matches_with_days_of_week(): void
    {
        // Wednesday = 3
        $rule = PricingRule::create([
            'shop_id' => $this->shop->id,
            'name' => 'Weekday Discount',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'start_time' => '00:00',
            'end_time' => '23:59',
            'days_of_week' => [1, 2, 3, 4, 5], // Mon-Fri
            'is_active' => true,
        ]);

        // Wednesday at 14:00
        $wed = Carbon::parse('2026-03-18 14:00:00'); // Wednesday
        $active = PricingRule::where('shop_id', $this->shop->id)->activeNow($wed)->get();
        $this->assertCount(1, $active);

        // Saturday at 14:00
        $sat = Carbon::parse('2026-03-21 14:00:00'); // Saturday
        $active = PricingRule::where('shop_id', $this->shop->id)->activeNow($sat)->get();
        $this->assertCount(0, $active);
    }

    public function test_get_time_priced_applies_product_specific_rule(): void
    {
        PricingRule::create([
            'shop_id' => $this->shop->id,
            'product_id' => $this->product->id,
            'name' => 'Product Discount',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'start_time' => '00:00',
            'end_time' => '23:59',
            'is_active' => true,
        ]);

        $rules = PricingRule::where('shop_id', $this->shop->id)->activeNow()->get();
        $price = $this->product->getTimePriced($rules);

        $this->assertEquals(round($this->product->final_price * 0.8, 3), $price);
    }
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=PricingRuleTest`
Expected: At least one test should fail due to the SQLite/JSON type mismatch

**Step 3: Fix the root causes**

Fix 1 — Normalize time format in `scopeActiveNow()` (`app/Models/PricingRule.php`):

```php
public function scopeActiveNow(Builder $query, ?Carbon $now = null): Builder
{
    $now = $now ?? now();
    $currentTime = $now->format('H:i:s');
    $currentDay = (int) $now->dayOfWeek;

    return $query
        ->where('is_active', true)
        ->whereRaw("CASE WHEN LENGTH(start_time) = 5 THEN start_time || ':00' ELSE start_time END <= ?", [$currentTime])
        ->whereRaw("CASE WHEN LENGTH(end_time) = 5 THEN end_time || ':00' ELSE end_time END >= ?", [$currentTime])
        ->where(function (Builder $q) use ($currentDay) {
            $q->whereNull('days_of_week')
                ->orWhereJsonContains('days_of_week', $currentDay)
                ->orWhereJsonContains('days_of_week', (string) $currentDay);
        });
}
```

Fix 2 — Cast days_of_week values to integers on save (`app/Livewire/Admin/PricingRules.php`):

Find where `days_of_week` is saved and add: `array_map('intval', $this->days_of_week)`

Fix 3 — Also fix `isActiveNow()` instance method for consistency:

```php
public function isActiveNow(?Carbon $now = null): bool
{
    $now = $now ?? now();
    $currentTime = $now->format('H:i:s');
    $currentDay = (int) $now->dayOfWeek;

    if (! $this->is_active) {
        return false;
    }

    $start = str_pad($this->start_time, 8, ':00');
    $end = str_pad($this->end_time, 8, ':00');

    if ($currentTime < $start || $currentTime > $end) {
        return false;
    }

    if ($this->days_of_week !== null) {
        $days = array_map('intval', $this->days_of_week);
        if (! in_array($currentDay, $days, true)) {
            return false;
        }
    }

    return true;
}
```

**Step 4: Run tests**

Run: `php artisan test --filter=PricingRuleTest`
Expected: All PASS

**Step 5: Commit**

```bash
git add app/Models/PricingRule.php app/Livewire/Admin/PricingRules.php tests/Unit/PricingRuleTest.php
git commit -m "fix: pricing rules not matching due to time format and JSON type mismatches"
```

---

### Task 2: Modifier Override Bug (Size + Custom Modifier)

**Bug:** Ordering medium Karak + choosing a newly created modifier overrides size to Small and doesn't add the modifier's extra cost.

**Root Cause (investigate):** Likely a Livewire state issue with `wire:model.live="selectedModifiers.{{ $group->id }}"` on radio buttons. When user selects in one modifier group, the server roundtrip may reset selections in other groups due to how Livewire 3 hydrates nested array properties.

**Files:**
- Investigate: `app/Livewire/GuestMenu.php:455-546` (addToCart flow)
- Investigate: `resources/views/livewire/guest-menu.blade.php:499-542` (modifier modal)
- Fix: `app/Livewire/GuestMenu.php` (normalize modifier state handling)

**Step 1: Reproduce and diagnose**

1. Open staging guest menu, find a product with multiple modifier groups (one size group with min_selection=1, one optional group)
2. Open browser dev tools Network tab, filter for Livewire requests
3. Select "Medium" in the size group — observe the Livewire request payload
4. Select an option in the second group — observe if `selectedModifiers` from the response still contains the size selection

If the size selection is lost, the bug is in Livewire state hydration of the nested array.

**Step 2: Write failing test**

```php
// tests/Unit/GuestMenuModifierTest.php
<?php

namespace Tests\Unit;

use App\Livewire\GuestMenu;
use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GuestMenuModifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_selecting_modifiers_across_groups_preserves_all_selections(): void
    {
        $shop = Shop::factory()->create(['slug' => 'test-shop']);

        $category = Category::factory()->create(['shop_id' => $shop->id]);

        $product = Product::factory()->create([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'price' => 0.500,
        ]);

        // Size group (required, single select)
        $sizeGroup = ModifierGroup::create([
            'shop_id' => $shop->id,
            'name_en' => 'Size',
            'name_ar' => 'الحجم',
            'min_selection' => 1,
            'max_selection' => 1,
        ]);

        $small = ModifierOption::create([
            'modifier_group_id' => $sizeGroup->id,
            'name_en' => 'Small',
            'name_ar' => 'صغير',
            'price_adjustment' => 0,
        ]);

        $medium = ModifierOption::create([
            'modifier_group_id' => $sizeGroup->id,
            'name_en' => 'Medium',
            'name_ar' => 'وسط',
            'price_adjustment' => 0.200,
        ]);

        // Extras group (optional, multi select)
        $extrasGroup = ModifierGroup::create([
            'shop_id' => $shop->id,
            'name_en' => 'Extras',
            'name_ar' => 'إضافات',
            'min_selection' => 0,
            'max_selection' => 3,
        ]);

        $extra = ModifierOption::create([
            'modifier_group_id' => $extrasGroup->id,
            'name_en' => 'Extra Spice',
            'name_ar' => 'بهارات إضافية',
            'price_adjustment' => 0.100,
        ]);

        $product->modifierGroups()->attach([$sizeGroup->id, $extrasGroup->id]);

        $component = Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id); // Opens modal

        // Select Medium
        $component->set("selectedModifiers.{$sizeGroup->id}", (string) $medium->id);

        // Select Extra — should NOT reset size
        $component->set("selectedModifiers.{$extrasGroup->id}", [(string) $extra->id]);

        // Submit
        $component->call('addToCart', $product->id);

        // Verify cart has correct price: base (0.500) + medium (0.200) + extra (0.100) = 0.800
        $cart = $component->get('cart');
        $cartItem = collect($cart)->first();

        $this->assertNotNull($cartItem);
        $this->assertEquals(0.800, $cartItem['price']);
        $this->assertCount(2, $cartItem['selectedModifiers']); // medium + extra
    }
}
```

**Step 3: Run test**

Run: `php artisan test --filter=GuestMenuModifierTest`
Expected: FAIL — price won't include both modifiers correctly

**Step 4: Investigate and fix**

The most likely fix is to ensure `selectedModifiers` is not partially reset on Livewire hydration. Potential approaches:

- **Option A:** Change `wire:model.live` to `wire:model` (defer updates until form submit) to avoid mid-selection state corruption
- **Option B:** Add explicit array type handling in the component to preserve all group keys when one group updates
- **Option C:** Use `$this->selectedModifiers` as a computed merge rather than direct model binding

The fix depends on what the investigation reveals. If Livewire is resetting the array, the cleanest fix is likely to use `wire:model` (without `.live`) and move the price preview to Alpine.js, or to explicitly handle the update via a Livewire method:

```blade
{{-- Instead of wire:model.live, use explicit method --}}
<input
    type="radio"
    value="{{ $option->id }}"
    wire:click="selectModifier({{ $group->id }}, {{ $option->id }})"
    @checked(($selectedModifiers[$group->id] ?? null) == $option->id)
>
```

```php
public function selectModifier(int $groupId, int $optionId): void
{
    $this->selectedModifiers[$groupId] = (string) $optionId;
}
```

**Step 5: Run tests**

Run: `php artisan test --filter=GuestMenuModifierTest`
Expected: PASS

**Step 6: Commit**

```bash
git add app/Livewire/GuestMenu.php resources/views/livewire/guest-menu.blade.php tests/Unit/GuestMenuModifierTest.php
git commit -m "fix: modifier selection across groups preserves all choices"
```

---

### Task 3: Menu Builder Arabic Display

**Bug:** Switching to Arabic in Menu Builder doesn't display Arabic item names.

**Root Cause:** `menu-builder.blade.php:83` hardcodes `$product->name_en` instead of using `$product->translated('name')`.

**Files:**
- Fix: `resources/views/livewire/admin/menu-builder.blade.php:83`

**Step 1: Fix**

```blade
{{-- Line 83: Change from --}}
{{ $product->name_en }}
{{-- To --}}
{{ $product->translated('name') }}
```

Also check if category names have the same issue in the same file.

**Step 2: Verify**

Run: `php artisan test --filter=MenuBuilder`
Expected: Existing tests still pass

**Step 3: Commit**

```bash
git add resources/views/livewire/admin/menu-builder.blade.php
git commit -m "fix: Menu Builder displays translated names based on locale"
```

---

### Task 4: 86'd Item Validation on Order Submit

**Bug:** Customer can complete an order for an 86'd item if the menu was open before the item was marked unavailable.

**Root Cause:** `GuestMenu.php:637-641` fetches products by ID without checking `is_available`.

**Files:**
- Fix: `app/Livewire/GuestMenu.php:637-641`
- Test: `tests/Unit/GuestMenu86dTest.php` (new)

**Step 1: Write failing test**

```php
public function test_submit_order_rejects_unavailable_products(): void
{
    // Create shop, product, add to cart
    // Then set product->is_available = false
    // Call submitOrder
    // Assert order is NOT created and error message shown
}
```

**Step 2: Fix**

```php
// GuestMenu.php line 637-641, add ->where('is_available', true)
$products = $this->shop->products()
    ->with('modifierGroups.options')
    ->where('is_available', true)
    ->whereIn('id', $productIds)
    ->get()
    ->keyBy('id');

// After the loop, check if any cart items were skipped
$unavailableItems = collect($cartItems)
    ->filter(fn ($item) => !$products->has($item['id']))
    ->pluck('name')
    ->all();

if (!empty($unavailableItems)) {
    $this->orderError = __('guest.items_no_longer_available', [
        'items' => implode(', ', $unavailableItems)
    ]);
    return;
}
```

**Step 3: Run tests and commit**

---

### Task 5: Order Cancellation from POS

**Bug:** No way to cancel individual orders from POS (for refunds/customer cancellations).

**Files:**
- Add: `app/Livewire/PosDashboard.php` — new `cancelOrder(int $orderId)` method
- Add: `resources/views/livewire/pos-dashboard.blade.php` — cancel button in order cards

**Step 1: Add cancelOrder method to PosDashboard**

```php
public function cancelOrder(int $orderId): void
{
    if ($this->requiresManagerOverride()) {
        $this->requestManagerOverride('cancelOrder', ['orderId' => $orderId]);
        return;
    }

    $order = Order::where('shop_id', Auth::user()->shop_id)
        ->whereIn('status', ['unpaid', 'paid', 'preparing'])
        ->findOrFail($orderId);

    DB::transaction(function () use ($order) {
        $order->update(['status' => 'cancelled']);

        AuditLog::record('order.cancelled', $order, [
            'cancelled_by' => Auth::user()->name,
            'previous_status' => $order->getOriginal('status'),
        ]);
    });

    session()->flash('message', 'Order #' . $order->id . ' cancelled.');
}
```

Also wire up `cancelOrder` in the `confirmManagerOverride()` switch (alongside `clearOldOrders` and `systemReset`).

**Step 2: Add cancel button to Blade**

Add a cancel/X button to order cards in the POS view for orders that are not completed or already cancelled.

**Step 3: Test and commit**

---

### Task 6: Shift Report Blank Download

**Bug:** "Downloading" a daily shift report shows a blank page.

**Root Cause:** There is no download/export feature. The only action is `window.print()` which opens the browser print dialog. If Anas expected a PDF/CSV download, there's a UX mismatch. The "blank page" might be a CSS issue where `@media print` styles or the `print-show`/`print-hidden` classes don't work correctly.

**Files:**
- Fix: `resources/views/livewire/shift-report.blade.php` — fix print CSS
- Or: Add CSV export method to `app/Livewire/ShiftReport.php`

**Step 1: Investigate the blank page**

Check if `print-hidden` class properly hides the non-printable controls and if `print-show` reveals the print header. The `style="display: none;"` on line 27 might override the CSS `print-show` class.

**Step 2: Fix print styles**

The `print-show` div has inline `style="display: none;"` which likely takes precedence over the `@media print` CSS rule. Fix:

```blade
{{-- Remove inline style, use class only --}}
<div class="print-show">
```

And ensure the print CSS has `!important`:

```css
@media print {
    .print-show { display: block !important; }
    .print-hidden { display: none !important; }
}
```

**Step 3: Optionally add CSV export**

Add a `downloadCsv()` method to ShiftReport similar to `ReportsExportController::orders()`.

**Step 4: Commit**

---

### Task 7: OMR 3-Decimal Rounding

**Bug:** 0.525 rounds to 0.530 instead of staying at 0.525.

**Root Cause:** `GuestMenu.php:705-706` uses `round($taxAmount, 2)` — rounds to 2 decimals instead of 3 (OMR standard). Same issue in `PosDashboard.php:546,552`.

**Files:**
- Fix: `app/Livewire/GuestMenu.php:705-706`
- Fix: `app/Livewire/PosDashboard.php:546,547,552,557`

**Step 1: Fix rounding precision**

```php
// GuestMenu.php line 705-706
'tax_amount' => round($taxAmount, 3),
'total_amount' => round($subtotalAmount + $taxAmount, 3),

// PosDashboard.php — all round(..., 2) for monetary amounts → round(..., 3)
```

**Step 2: Grep for other `round(..., 2)` on monetary values**

Run: `grep -n 'round(.*,\s*2)' app/Livewire/*.php`
Fix all monetary roundings to use 3 decimals.

**Step 3: Also fix `recordPaymentsForOrder` in PosDashboard**

Line 342: `'amount' => round((float) ($row['amount'] ?? 0), 2)` → `round(..., 3)`

**Step 4: Commit**

---

### Task 8: Modifier Size Display Notes

**Bug:** Sizes sub-menu shows "+300" for Large but no note for Regular (should show base/default indicator).

**Root Cause:** In `guest-menu.blade.php:519`, the price adjustment is only shown when `$option->price_adjustment > 0`. Regular (base size) has `price_adjustment = 0` so nothing shows.

**Files:**
- Fix: `resources/views/livewire/guest-menu.blade.php:519-521`

**Step 1: Fix display**

Show "Base" or "Included" label for options with 0 price adjustment when in a required group:

```blade
@if($option->price_adjustment > 0)
    <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-crema">+<x-price :amount="$option->price_adjustment" :shop="$shop" /></span>
@elseif($group->min_selection > 0 && $group->options->count() > 1)
    <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('guest.base_price') }}</span>
@endif
```

**Step 2: Add translation string**

Add `'base_price' => 'Base'` to `lang/en/guest.php` and `'base_price' => 'أساسي'` to `lang/ar/guest.php`.

**Step 3: Commit**

---

### Task 9: Modifier Extra Cost Decimal Input

**Bug:** Extra cost field doesn't accept "." from keyboard.

**Root Cause:** The `<input type="number" step="0.01">` at `modifier-manager.blade.php:61` should accept decimals. However, some browsers or keyboard configurations (especially Arabic/RTL keyboards) may not output a `.` character. The `step` attribute also rejects values that don't match the step.

**Files:**
- Fix: `resources/views/livewire/modifier-manager.blade.php:61`

**Step 1: Fix**

Change `step="0.01"` to `step="0.001"` (for OMR 3-decimal) and add `inputmode="decimal"` for mobile support. Also consider using `type="text" inputmode="decimal" pattern="[0-9]*\.?[0-9]*"` as a more permissive alternative:

```blade
<input type="number" step="0.001" inputmode="decimal" wire:model="optionPrice" class="w-full field transition-all" placeholder="0.100">
```

**Step 2: Commit**

---

### Task 10: Modifier Group Deletion

**Bug:** Admin can't delete modifier groups.

**Root Cause:** `ModifierManager.php` has no `deleteGroup()` or `deleteOption()` methods. The Blade view has no delete buttons.

**Files:**
- Add: `app/Livewire/ModifierManager.php` — `deleteGroup()` and `deleteOption()` methods
- Add: `resources/views/livewire/modifier-manager.blade.php` — delete buttons

**Step 1: Add delete methods**

```php
public function deleteGroup(int $groupId): void
{
    $group = ModifierGroup::where('shop_id', Auth::user()->shop_id)
        ->findOrFail($groupId);

    // Detach from all products first
    $group->products()->detach();

    // Delete options then group (cascade should handle this, but be explicit)
    $group->options()->delete();
    $group->delete();

    if ($this->selectedGroupId == $groupId) {
        $this->selectedGroupId = null;
    }
}

public function deleteOption(int $optionId): void
{
    $option = ModifierOption::whereHas('modifierGroup', function ($q) {
        $q->where('shop_id', Auth::user()->shop_id);
    })->findOrFail($optionId);

    $option->delete();
}
```

**Step 2: Add delete buttons to Blade**

Add a delete button next to each group name and each option card, using the existing `confirm-action` pattern from MenuBuilder.

**Step 3: Commit**

---

### Task 11: Pricing Rules Auto-Clear Question

**Bug/Question:** Anas asks if pricing rules auto-clear after a day.

**Answer:** No. Pricing rules are persistent — they don't auto-delete. They only appear inactive when the current time falls outside their `start_time`/`end_time` window or the current day isn't in their `days_of_week`. This is by design.

**Action:** This is answered by fixing Task 1 (rules weren't matching at all). If Anas created a rule and it disappeared, it's because the rule wasn't matching the current time/day due to the format bug. No code change needed — just confirm the answer to Anas on Notion after Task 1 is fixed.

---

### Task 12: Audit Logs Tabs

**Suggestion:** Add tabs/dividers to audit logs for better UX.

**Files:**
- Find: Audit logs Livewire component and Blade view
- Add: Tab filtering by action category

**Step 1: Identify audit log categories**

Grep for `AuditLog::record` calls to find all action types used. Group them into logical categories (e.g., Orders, Products, Settings, Auth).

**Step 2: Add tab filtering**

Add a `$logFilter` property to the audit logs component and filter the query by action prefix. Add tab buttons to the Blade view.

**Step 3: Commit**

---

## Post-Fix Checklist

After all fixes:

1. Run full test suite: `composer test`
2. Run linter: `./vendor/bin/pint`
3. Test on staging by reproducing each of Anas's original bug reports
4. Update Notion issue table — mark each as Fixed
5. Reply to Anas's Question (#11) on Notion
