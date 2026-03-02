# Currency System Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace all hardcoded `$` currency symbols with a per-shop configurable currency system defaulting to OMR (3 decimals).

**Architecture:** Add `currency_code`, `currency_symbol`, `currency_decimals` columns to the `shops` table. Create a global `formatPrice()` helper autoloaded via Composer. Replace every hardcoded `${{ number_format(..., 2) }}` across 10 files with `{{ formatPrice(..., $shop) }}`.

**Tech Stack:** Laravel 12, Livewire 3, PHPUnit 11, SQLite (test), Blade templates.

---

### Task 1: Migration — Add Currency Fields to Shops Table

**Files:**
- Create: `database/migrations/2026_03_02_100000_add_currency_fields_to_shops_table.php`

**Step 1: Create the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->string('currency_code', 3)->default('OMR')->after('tax_rate');
            $table->string('currency_symbol', 10)->default('OMR')->after('currency_code');
            $table->tinyInteger('currency_decimals')->default(3)->after('currency_symbol');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['currency_code', 'currency_symbol', 'currency_decimals']);
        });
    }
};
```

**Step 2: Run migration**

Run: `cd bite && php artisan migrate`
Expected: Migration runs successfully.

**Step 3: Update Shop model**

Modify: `app/Models/Shop.php` — add the three new fields to `$fillable`:

```php
protected $fillable = [
    'name',
    'slug',
    'status',
    'branding',
    'tax_rate',
    'currency_code',
    'currency_symbol',
    'currency_decimals',
];
```

**Step 4: Update ShopFactory**

Modify: `database/factories/ShopFactory.php` — add currency defaults:

```php
public function definition(): array
{
    $name = fake()->company();

    return [
        'name' => $name,
        'slug' => Str::slug($name),
        'status' => 'active',
        'branding' => null,
        'currency_code' => 'OMR',
        'currency_symbol' => 'OMR',
        'currency_decimals' => 3,
    ];
}
```

**Step 5: Commit**

```bash
git add -A && git commit -m "feat: add currency fields to shops table"
```

---

### Task 2: Helper Function — Create formatPrice

**Files:**
- Create: `app/Helpers/currency.php`
- Modify: `composer.json` (add autoload entry)

**Step 1: Write the test**

Create: `tests/Unit/CurrencyHelperTest.php`

```php
<?php

namespace Tests\Unit;

use App\Models\Shop;
use PHPUnit\Framework\TestCase;

class CurrencyHelperTest extends TestCase
{
    public function test_format_price_with_omr_defaults(): void
    {
        $shop = new Shop();
        $shop->currency_symbol = 'OMR';
        $shop->currency_decimals = 3;

        $this->assertSame('OMR 1.500', formatPrice(1.5, $shop));
        $this->assertSame('OMR 0.250', formatPrice(0.25, $shop));
        $this->assertSame('OMR 0.000', formatPrice(0, $shop));
        $this->assertSame('OMR 1,234.500', formatPrice(1234.5, $shop));
    }

    public function test_format_price_with_usd_config(): void
    {
        $shop = new Shop();
        $shop->currency_symbol = '$';
        $shop->currency_decimals = 2;

        $this->assertSame('$ 1.50', formatPrice(1.5, $shop));
        $this->assertSame('$ 0.25', formatPrice(0.25, $shop));
    }

    public function test_format_price_with_arabic_symbol(): void
    {
        $shop = new Shop();
        $shop->currency_symbol = 'ر.ع.';
        $shop->currency_decimals = 3;

        $this->assertSame('ر.ع. 1.500', formatPrice(1.5, $shop));
    }

    public function test_format_price_fallback_when_null(): void
    {
        $shop = new Shop();
        $shop->currency_symbol = null;
        $shop->currency_decimals = null;

        $this->assertSame('OMR 1.500', formatPrice(1.5, $shop));
    }
}
```

**Step 2: Run test to verify it fails**

Run: `cd bite && php artisan test --filter=CurrencyHelperTest`
Expected: FAIL — `formatPrice` function not found.

**Step 3: Create the helper file**

Create: `app/Helpers/currency.php`

```php
<?php

if (! function_exists('formatPrice')) {
    function formatPrice(float $amount, $shop): string
    {
        $decimals = $shop->currency_decimals ?? 3;
        $symbol = $shop->currency_symbol ?? 'OMR';

        return $symbol . ' ' . number_format($amount, $decimals);
    }
}
```

**Step 4: Register autoload in composer.json**

Modify: `composer.json` — add to the `autoload` section:

Before:
```json
"autoload": {
    "psr-4": {
        "App\\": "app/",
        "Database\\Factories\\": "database/factories/",
        "Database\\Seeders\\": "database/seeders/"
    }
},
```

After:
```json
"autoload": {
    "psr-4": {
        "App\\": "app/",
        "Database\\Factories\\": "database/factories/",
        "Database\\Seeders\\": "database/seeders/"
    },
    "files": [
        "app/Helpers/currency.php"
    ]
},
```

**Step 5: Dump autoload**

Run: `cd bite && composer dump-autoload`

**Step 6: Run tests to verify they pass**

Run: `cd bite && php artisan test --filter=CurrencyHelperTest`
Expected: All 4 tests PASS.

**Step 7: Commit**

```bash
git add -A && git commit -m "feat: add formatPrice currency helper"
```

---

### Task 3: Replace Currency in Guest Menu

**Files:**
- Modify: `resources/views/livewire/guest-menu.blade.php`

The `$shop` variable is available as `$this->shop` in the GuestMenu Livewire component. In Blade, Livewire public properties are accessible directly, so `$shop` works.

**Step 1: Replace all 11 occurrences**

Each replacement follows the pattern: `${{ number_format($x, 2) }}` → `{{ formatPrice($x, $shop) }}`

Line 63: `${{ number_format($product->price, 2) }}` → `{{ formatPrice($product->price, $shop) }}`
Line 64: `${{ number_format($product->final_price, 2) }}` → `{{ formatPrice($product->final_price, $shop) }}`
Line 66: `${{ number_format($product->price, 2) }}` → `{{ formatPrice($product->price, $shop) }}`
Line 94: `${{ number_format($this->total, 2) }}` → `{{ formatPrice($this->total, $shop) }}`
Line 123: `${{ number_format($item['price'] * $item['quantity'], 2) }}` → `{{ formatPrice($item['price'] * $item['quantity'], $shop) }}`
Line 132: `${{ number_format($this->subtotal, 2) }}` → `{{ formatPrice($this->subtotal, $shop) }}`
Line 136: `${{ number_format($this->tax, 2) }}` → `{{ formatPrice($this->tax, $shop) }}`
Line 140: `${{ number_format($this->total, 2) }}` → `{{ formatPrice($this->total, $shop) }}`
Line 200: `Price: ${{ number_format($this->customizingProductPrice, 2) }}` → `Price: {{ formatPrice($this->customizingProductPrice, $shop) }}`
Line 237: `+${{ number_format($option->price_adjustment, 2) }}` → `+{{ formatPrice($option->price_adjustment, $shop) }}`
Line 249: `Add for ${{ number_format($this->customizingProductPrice, 2) }}` → `Add for {{ formatPrice($this->customizingProductPrice, $shop) }}`

**Step 2: Run existing guest menu tests**

Run: `cd bite && php artisan test --filter=GuestMenuTest`
Expected: Tests pass (existing tests check for `4.50` which will now show as `OMR 4.500`).

Note: The existing test on line 35 asserts `->assertSee('4.50')`. After this change, the output will be `OMR 4.500`, so this assertion will fail. Update the test assertion:

Modify: `tests/Feature/Livewire/GuestMenuTest.php` line 35:
`->assertSee('4.50')` → `->assertSee('OMR 4.500')`

**Step 3: Run tests again**

Run: `cd bite && php artisan test --filter=GuestMenuTest`
Expected: PASS.

**Step 4: Commit**

```bash
git add -A && git commit -m "feat: replace hardcoded $ with formatPrice in guest menu"
```

---

### Task 4: Replace Currency in POS Dashboard

**Files:**
- Modify: `resources/views/livewire/pos-dashboard.blade.php`
- Modify: `app/Livewire/PosDashboard.php` (add `$shop` property)

The PosDashboard currently uses `Auth::user()->shop_id` without a `$shop` property. We need to add one so the Blade template can access it.

**Step 1: Add $shop property to PosDashboard.php**

Add to mount() or as a computed/loaded property. Find the `mount()` method and add:

```php
public Shop $shop;

public function mount(): void
{
    $this->shop = Auth::user()->shop;
    // ... existing mount logic
}
```

Make sure to add `use App\Models\Shop;` import if not present.

**Step 2: Replace all 6 occurrences in pos-dashboard.blade.php**

Line 37: `${{ number_format($order->total_amount, 2) }}` → `{{ formatPrice($order->total_amount, $shop) }}`
Line 57: `${{ number_format($order->paid_total, 2) }}` → `{{ formatPrice($order->paid_total, $shop) }}`
Line 61: `${{ number_format($order->balance_due, 2) }}` → `{{ formatPrice($order->balance_due, $shop) }}`
Line 96: `${{ number_format($salesToday, 2) }}` → `{{ formatPrice($salesToday, $shop) }}`
Line 158: `${{ number_format($item->price_snapshot, 2) }}` → `{{ formatPrice($item->price_snapshot, $shop) }}`
Line 183: `Balance due: ${{ number_format($paymentOrder->balance_due, 2) }}` → `Balance due: {{ formatPrice($paymentOrder->balance_due, $shop) }}`

**Step 3: Run existing POS dashboard tests**

Run: `cd bite && php artisan test --filter=PosDashboardTest`
Expected: PASS (update any assertions checking for `$` if they exist).

**Step 4: Commit**

```bash
git add -A && git commit -m "feat: replace hardcoded $ with formatPrice in POS dashboard"
```

---

### Task 5: Replace Currency in Shop Dashboard

**Files:**
- Modify: `resources/views/livewire/shop-dashboard.blade.php`
- Modify: `app/Livewire/ShopDashboard.php` (add `$shop` property)

Same pattern as Task 4 — add `$shop` property to the Livewire component.

**Step 1: Add $shop property to ShopDashboard.php**

```php
public Shop $shop;

public function mount(): void
{
    $this->shop = Auth::user()->shop;
    // ... existing mount logic
}
```

**Step 2: Replace all 6 occurrences in shop-dashboard.blade.php**

Line 24: `${{ number_format($dailyRevenue, 2) }}` → `{{ formatPrice($dailyRevenue, $shop) }}`
Line 55: `${{ number_format($avgOrderValue, 2) }}` → `{{ formatPrice($avgOrderValue, $shop) }}`
Line 88: `${{ number_format($product->revenue, 2) }}` → `{{ formatPrice($product->revenue, $shop) }}`
Line 112: `${{ number_format($summary['total'], 2) }}` → `{{ formatPrice($summary['total'], $shop) }}`
Line 129: `${{ number_format($row['total'], 2) }}` → `{{ formatPrice($row['total'], $shop) }}`
Line 160: `${{ number_format($order->total_amount, 2) }}` → `{{ formatPrice($order->total_amount, $shop) }}`

**Step 3: Run existing tests**

Run: `cd bite && php artisan test --filter=ShopDashboardTest`
Expected: PASS.

**Step 4: Commit**

```bash
git add -A && git commit -m "feat: replace hardcoded $ with formatPrice in shop dashboard"
```

---

### Task 6: Replace Currency in Reports Dashboard

**Files:**
- Modify: `resources/views/livewire/admin/reports-dashboard.blade.php`
- Modify: `app/Livewire/Admin/ReportsDashboard.php` (add `$shop` property)

**Step 1: Add $shop property to ReportsDashboard.php**

```php
public Shop $shop;

public function mount(): void
{
    $this->shop = Auth::user()->shop;
    // ... existing mount logic
}
```

**Step 2: Replace all 4 occurrences in reports-dashboard.blade.php**

Line 12: `${{ number_format($totalRevenue, 2) }}` → `{{ formatPrice($totalRevenue, $shop) }}`
Line 20: `${{ number_format($avgOrder, 2) }}` → `{{ formatPrice($avgOrder, $shop) }}`
Line 53: `${{ number_format($product->revenue, 2) }}` → `{{ formatPrice($product->revenue, $shop) }}`
Line 75: `${{ number_format($row->total, 2) }}` → `{{ formatPrice($row->total, $shop) }}`

**Step 3: Commit**

```bash
git add -A && git commit -m "feat: replace hardcoded $ with formatPrice in reports dashboard"
```

---

### Task 7: Replace Currency in Invoice, Order Tracker, and Remaining Templates

**Files:**
- Modify: `resources/views/invoices/order.blade.php` (4 occurrences — uses `$order->shop`)
- Modify: `resources/views/livewire/guest/order-tracker.blade.php` (4 occurrences — has `$shop` as Livewire property)
- Modify: `resources/views/livewire/product-manager.blade.php` (1 occurrence — needs `$shop` added)
- Modify: `resources/views/livewire/modifier-manager.blade.php` (1 occurrence — needs `$shop` added)
- Modify: `resources/views/livewire/admin/menu-builder.blade.php` (1 occurrence — needs `$shop` added)
- Modify: `app/Livewire/ProductManager.php` (add `$shop` property)
- Modify: `app/Livewire/ModifierManager.php` (add `$shop` property)
- Modify: `app/Livewire/Admin/MenuBuilder.php` (add `$shop` property)

**Step 1: Invoice template** (`invoices/order.blade.php`)

The invoice already loads `$order->shop` via the InvoiceController. Use `$order->shop`:

Line 58: `${{ number_format($item->price_snapshot, 2) }}` → `{{ formatPrice($item->price_snapshot, $order->shop) }}`
Line 66: `${{ number_format($order->subtotal_amount ?? $order->total_amount, 2) }}` → `{{ formatPrice($order->subtotal_amount ?? $order->total_amount, $order->shop) }}`
Line 68: `${{ number_format($order->tax_amount ?? 0, 2) }}` → `{{ formatPrice($order->tax_amount ?? 0, $order->shop) }}`
Line 70: `${{ number_format($order->total_amount, 2) }}` → `{{ formatPrice($order->total_amount, $order->shop) }}`

**Step 2: Order tracker** (`guest/order-tracker.blade.php`)

Already has `$shop` as a Livewire public property:

Line 51: `${{ number_format($order->total_amount, 2) }}` → `{{ formatPrice($order->total_amount, $shop) }}`
Line 58: `${{ number_format($order->subtotal_amount ?? $order->total_amount, 2) }}` → `{{ formatPrice($order->subtotal_amount ?? $order->total_amount, $shop) }}`
Line 62: `${{ number_format($order->tax_amount ?? 0, 2) }}` → `{{ formatPrice($order->tax_amount ?? 0, $shop) }}`
Line 66: `${{ number_format($order->total_amount, 2) }}` → `{{ formatPrice($order->total_amount, $shop) }}`

**Step 3: Add $shop to ProductManager, ModifierManager, MenuBuilder**

For each of these three Livewire components, add:

```php
public Shop $shop;

// In mount():
$this->shop = Auth::user()->shop;
```

Then replace in templates:

`product-manager.blade.php` line 103: `${{ number_format($product->price, 2) }}` → `{{ formatPrice($product->price, $shop) }}`

`modifier-manager.blade.php` line 89: `+${{ number_format($option->price_adjustment, 2) }}` → `+{{ formatPrice($option->price_adjustment, $shop) }}`

Also fix the label on line 45: `Extra Cost ($)` → `Extra Cost`

`admin/menu-builder.blade.php` line 83: `${{ number_format($product->price, 2) }}` → `{{ formatPrice($product->price, $shop) }}`

**Step 4: Commit**

```bash
git add -A && git commit -m "feat: replace hardcoded $ with formatPrice in remaining templates"
```

---

### Task 8: Replace Currency in PrintNodeService

**Files:**
- Modify: `app/Services/PrintNodeService.php`

**Step 1: Replace line 65**

Before: `$lines[] = 'Total: $'.number_format($order->total_amount, 2);`
After: `$lines[] = 'Total: '.formatPrice($order->total_amount, $order->shop);`

Ensure `$order->shop` is loaded. The method already calls `$order->loadMissing('items.modifiers')` on line 53. Update to: `$order->loadMissing('items.modifiers', 'shop');`

**Step 2: Commit**

```bash
git add -A && git commit -m "feat: replace hardcoded $ with formatPrice in PrintNodeService"
```

---

### Task 9: Integration Test — Guest Menu Renders OMR

**Files:**
- Create: `tests/Feature/CurrencyDisplayTest.php`

**Step 1: Write integration test**

```php
<?php

namespace Tests\Feature;

use App\Livewire\GuestMenu;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CurrencyDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_menu_renders_omr_currency(): void
    {
        $shop = Shop::create([
            'name' => 'Test Cafe',
            'slug' => 'test-cafe',
            'currency_code' => 'OMR',
            'currency_symbol' => 'OMR',
            'currency_decimals' => 3,
        ]);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Drinks']);
        Product::create([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name' => 'Karak',
            'price' => 0.50,
        ]);

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->assertSee('OMR 0.500')
            ->assertDontSee('$');
    }

    public function test_guest_menu_renders_custom_currency_symbol(): void
    {
        $shop = Shop::create([
            'name' => 'Test Cafe',
            'slug' => 'test-cafe',
            'currency_code' => 'OMR',
            'currency_symbol' => 'ر.ع.',
            'currency_decimals' => 3,
        ]);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Drinks']);
        Product::create([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name' => 'Karak',
            'price' => 0.50,
        ]);

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->assertSee('ر.ع. 0.500');
    }

    public function test_new_shop_defaults_to_omr(): void
    {
        $shop = Shop::create([
            'name' => 'New Shop',
            'slug' => 'new-shop',
        ]);

        $this->assertSame('OMR', $shop->currency_code);
        $this->assertSame('OMR', $shop->currency_symbol);
        $this->assertSame(3, (int) $shop->currency_decimals);
    }
}
```

**Step 2: Run integration tests**

Run: `cd bite && php artisan test --filter=CurrencyDisplayTest`
Expected: All 3 tests PASS.

**Step 3: Run full test suite**

Run: `cd bite && php artisan test`
Expected: All tests PASS. Fix any remaining `$` assertions in other test files that now show `OMR`.

**Step 4: Commit**

```bash
git add -A && git commit -m "test: add currency display integration tests"
```

---

### Task 10: Final Verification

**Step 1: Run full test suite**

Run: `cd bite && php artisan test`
Expected: All tests PASS.

**Step 2: Grep for remaining hardcoded $**

Run: `grep -rn '\${{ number_format' resources/views/ app/`
Expected: No matches found.

Run: `grep -rn "'\$'" app/Services/`
Expected: No matches found.

**Step 3: Commit (if any fixes needed)**

```bash
git add -A && git commit -m "chore: clean up any remaining hardcoded currency symbols"
```
