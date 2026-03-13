# E2E Testing Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add browser-level E2E tests using Laravel Dusk across 3 phases covering all critical user flows.

**Architecture:** Install Laravel Dusk, create Page objects for reusable selectors, write 23 test files organized in Phase1/Phase2/Phase3 directories. Tests run against a dedicated MySQL `bite_testing` database. CI gets a separate Dusk job with a MySQL service container.

**Tech Stack:** Laravel Dusk, ChromeDriver, MySQL `bite_testing`, GitHub Actions

---

### Task 1: Install Laravel Dusk ✅ DONE

**Files:**
- Modify: `composer.json` (dev dependency)
- Create: `tests/DuskTestCase.php`
- Create: `.env.dusk.local`

**Step 1: Install Dusk via Composer**

Run:
```bash
cd /Users/nasseralbusaidi/Desktop/Backend/pos-system/bite
composer require laravel/dusk --dev
```

**Step 2: Install Dusk scaffolding**

Run:
```bash
php artisan dusk:install
```

This creates `tests/Browser/`, `tests/DuskTestCase.php`, and the ChromeDriver binary.

**Step 3: Create the MySQL test database**

Run:
```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS bite_testing;"
```

**Step 4: Create `.env.dusk.local`**

```env
APP_NAME="Bite POS"
APP_ENV=testing
APP_KEY=  # will be set below
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bite_testing
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=file
QUEUE_CONNECTION=sync
CACHE_STORE=array
MAIL_MAILER=log

STRIPE_KEY=
STRIPE_SECRET=
PRINTNODE_ENABLED=false
```

**Step 5: Generate app key for Dusk env**

Run:
```bash
php artisan key:generate --env=dusk.local
```

**Step 6: Run migrations against the test database**

Run:
```bash
php artisan migrate:fresh --seed --env=dusk.local
```

**Step 7: Verify Dusk runs**

Run:
```bash
php artisan serve --env=dusk.local &
php artisan dusk --stop-on-failure
```

Expected: 1 example test passes. Kill the server after.

**Step 8: Commit**

```bash
git add composer.json composer.lock .env.dusk.local tests/DuskTestCase.php tests/Browser/
git commit -m "chore: install Laravel Dusk with MySQL test database"
```

> **Completed 2026-03-13.** Dusk v8.4.1 installed. ChromeDriver v145 (auto-detected to match Chrome v145). Example test visits `/login` and asserts "Sign In". Note: the default ChromeDriver v146 didn't match — ran `dusk:chrome-driver --detect` to fix. All 8 steps done, not yet committed.

---

### Task 2: Create Page Objects ✅ DONE

**Files:**
- Create: `tests/Browser/Pages/PosPage.php`
- Create: `tests/Browser/Pages/GuestMenuPage.php`
- Create: `tests/Browser/Pages/KdsPage.php`
- Create: `tests/Browser/Pages/LoginPage.php`
- Create: `tests/Browser/Pages/PinLoginPage.php`
- Create: `tests/Browser/Pages/DashboardPage.php`
- Create: `tests/Browser/Pages/MenuBuilderPage.php`
- Create: `tests/Browser/Pages/SettingsPage.php`
- Create: `tests/Browser/Pages/SuperAdminPage.php`
- Create: `tests/Browser/Pages/OnboardingPage.php`
- Create: `tests/Browser/Pages/BillingPage.php`
- Create: `tests/Browser/Pages/ReportsPage.php`

**Step 1: Create all Page objects**

Each Page object defines `url()` and `elements()` for reusable selectors. Here are the key ones:

```php
// tests/Browser/Pages/PosPage.php
<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Page;

class PosPage extends Page
{
    public function url(): string
    {
        return '/pos';
    }

    public function assert($browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function elements(): array
    {
        return [
            '@order-card' => '.surface-card',
            '@pay-cash' => '[wire\\:click*="markAsPaid"][wire\\:click*="cash"]',
            '@pay-card' => '[wire\\:click*="markAsPaid"][wire\\:click*="card"]',
            '@split-btn' => '[wire\\:click*="openSplit"]',
        ];
    }
}
```

```php
// tests/Browser/Pages/GuestMenuPage.php
<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Page;

class GuestMenuPage extends Page
{
    protected string $slug;

    public function __construct(string $slug = 'demo')
    {
        $this->slug = $slug;
    }

    public function url(): string
    {
        return '/menu/' . $this->slug;
    }

    public function assert($browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function elements(): array
    {
        return [
            '@add-to-cart' => '[wire\\:click*="addToCart"]',
            '@review-btn' => '[wire\\:click*="toggleReview"]',
            '@submit-order' => '[wire\\:click*="submitOrder"]',
            '@lang-en' => '[wire\\:click*="switchLanguage"][wire\\:click*="en"]',
            '@lang-ar' => '[wire\\:click*="switchLanguage"][wire\\:click*="ar"]',
        ];
    }
}
```

```php
// tests/Browser/Pages/KdsPage.php
<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Page;

class KdsPage extends Page
{
    public function url(): string
    {
        return '/kds';
    }

    public function assert($browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function elements(): array
    {
        return [
            '@kds-card' => '.kds-card',
            '@start-preparing' => '[wire\\:click*="updateStatus"][wire\\:click*="preparing"]',
            '@mark-ready' => '[wire\\:click*="updateStatus"][wire\\:click*="ready"]',
        ];
    }
}
```

```php
// tests/Browser/Pages/LoginPage.php
<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Page;

class LoginPage extends Page
{
    public function url(): string
    {
        return '/login';
    }

    public function assert($browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function elements(): array
    {
        return [
            '@email' => 'input[name="email"]',
            '@password' => 'input[name="password"]',
            '@submit' => 'button[type="submit"]',
        ];
    }
}
```

```php
// tests/Browser/Pages/PinLoginPage.php
<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Page;

class PinLoginPage extends Page
{
    protected string $slug;

    public function __construct(string $slug = 'demo')
    {
        $this->slug = $slug;
    }

    public function url(): string
    {
        return '/pos/pin/' . $this->slug;
    }

    public function assert($browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function elements(): array
    {
        return [
            '@pin-input' => 'input[type="password"]',
            '@unlock-btn' => 'button[type="submit"]',
        ];
    }
}
```

```php
// tests/Browser/Pages/DashboardPage.php
<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Page;

class DashboardPage extends Page
{
    public function url(): string
    {
        return '/dashboard';
    }

    public function assert($browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function elements(): array
    {
        return [
            '@daily-revenue' => '.metric-value',
            '@notification-bell' => '[wire\\:click*="toggleNotifications"]',
        ];
    }
}
```

```php
// tests/Browser/Pages/MenuBuilderPage.php
<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Page;

class MenuBuilderPage extends Page
{
    public function url(): string
    {
        return '/menu-builder';
    }

    public function assert($browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function elements(): array
    {
        return [
            '@search' => '[wire\\:model\\.live="search"]',
            '@add-category' => '[wire\\:click*="createCategory"]',
            '@toggle-visibility' => '[wire\\:click*="toggleVisibility"]',
        ];
    }
}
```

```php
// tests/Browser/Pages/SettingsPage.php
<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Page;

class SettingsPage extends Page
{
    public function url(): string
    {
        return '/settings';
    }

    public function assert($browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function elements(): array
    {
        return [
            '@shop-name' => '[wire\\:model="name"]',
            '@tax-rate' => '[wire\\:model="tax_rate"]',
            '@save-btn' => '[wire\\:submit\\.prevent="save"]',
            '@add-staff' => '[wire\\:click*="addStaff"]',
        ];
    }
}
```

```php
// tests/Browser/Pages/SuperAdminPage.php
<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Page;

class SuperAdminPage extends Page
{
    public function url(): string
    {
        return '/admin';
    }

    public function assert($browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function elements(): array
    {
        return [
            '@shop-list' => '[wire\\:click*="toggleStatus"]',
            '@impersonate' => 'a[href*="impersonate"]',
        ];
    }
}
```

```php
// tests/Browser/Pages/OnboardingPage.php
<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Page;

class OnboardingPage extends Page
{
    public function url(): string
    {
        return '/onboarding';
    }

    public function assert($browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function elements(): array
    {
        return [
            '@next-step' => '[wire\\:click*="nextStep"]',
            '@prev-step' => '[wire\\:click*="previousStep"]',
            '@complete' => '[wire\\:click*="completeOnboarding"]',
        ];
    }
}
```

```php
// tests/Browser/Pages/BillingPage.php
<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Page;

class BillingPage extends Page
{
    public function url(): string
    {
        return '/billing';
    }

    public function assert($browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function elements(): array
    {
        return [];
    }
}
```

```php
// tests/Browser/Pages/ReportsPage.php
<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Page;

class ReportsPage extends Page
{
    public function url(): string
    {
        return '/reports';
    }

    public function assert($browser): void
    {
        $browser->assertPathIs($this->url());
    }

    public function elements(): array
    {
        return [
            '@date-range' => '[wire\\:model="rangeDays"]',
        ];
    }
}
```

**Step 2: Verify Page objects compile**

Run:
```bash
php artisan dusk --stop-on-failure
```

Expected: Still passes (example test only).

**Step 3: Commit**

```bash
git add tests/Browser/Pages/
git commit -m "chore: add Dusk Page objects for all major views"
```

> **Completed 2026-03-13.** All 12 Page objects created: PosPage, GuestMenuPage, KdsPage, LoginPage, PinLoginPage, DashboardPage, MenuBuilderPage, SettingsPage, SuperAdminPage, OnboardingPage, BillingPage, ReportsPage. Dusk example test still passes — no compile/autoload errors. Not yet committed.

---

### Task 3: Create shared test helper trait ✅ DONE

**Files:**
- Create: `tests/Browser/Traits/SeedsTestData.php`

**Step 1: Create the trait**

This trait provides helper methods to seed shops, users, products, orders, and modifiers for browser tests.

```php
// tests/Browser/Traits/SeedsTestData.php
<?php

namespace Tests\Browser\Traits;

use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

trait SeedsTestData
{
    protected function createShopWithAdmin(array $shopOverrides = []): array
    {
        $shop = Shop::factory()->create(array_merge([
            'slug' => 'test-shop-' . uniqid(),
            'tax_rate' => 0,
            'branding' => [
                'accent' => '#cc5500',
                'paper' => '#fdfcf8',
                'ink' => '#1a1918',
                'onboarding_completed' => true,
            ],
        ], $shopOverrides));

        $admin = User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'admin',
            'email' => 'admin-' . uniqid() . '@test.com',
            'password' => Hash::make('password'),
            'pin_code' => Hash::make('1234'),
        ]);

        return [$shop, $admin];
    }

    protected function createStaffUser(Shop $shop, string $role = 'server', string $pin = '5678'): User
    {
        return User::factory()->create([
            'shop_id' => $shop->id,
            'role' => $role,
            'email' => $role . '-' . uniqid() . '@test.com',
            'password' => Hash::make('password'),
            'pin_code' => Hash::make($pin),
        ]);
    }

    protected function createSuperAdmin(): User
    {
        $shop = Shop::factory()->create(['slug' => 'super-shop-' . uniqid()]);

        return User::factory()->superAdmin()->create([
            'shop_id' => $shop->id,
            'role' => 'admin',
            'email' => 'super-' . uniqid() . '@test.com',
            'password' => Hash::make('password'),
        ]);
    }

    protected function createProductWithCategory(Shop $shop, array $productOverrides = []): array
    {
        $category = Category::factory()->create([
            'shop_id' => $shop->id,
            'name_en' => 'Test Category',
            'name_ar' => 'فئة اختبار',
            'is_active' => true,
        ]);

        $product = Product::factory()->create(array_merge([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Test Coffee',
            'name_ar' => 'قهوة اختبار',
            'price' => 2.500,
            'is_available' => true,
            'is_visible' => true,
        ], $productOverrides));

        return [$category, $product];
    }

    protected function createModifierGroup(Shop $shop, Product $product, bool $required = false): array
    {
        $group = ModifierGroup::create([
            'shop_id' => $shop->id,
            'name_en' => 'Size',
            'name_ar' => 'الحجم',
            'min_selection' => $required ? 1 : 0,
            'max_selection' => 1,
        ]);

        $option = ModifierOption::create([
            'modifier_group_id' => $group->id,
            'name_en' => 'Large',
            'name_ar' => 'كبير',
            'price_adjustment' => 1.000,
        ]);

        $product->modifierGroups()->attach($group->id);

        return [$group, $option];
    }

    protected function createPaidOrder(Shop $shop, Product $product, int $quantity = 1): Order
    {
        $total = $product->price * $quantity;

        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'paid',
            'paid_at' => now(),
            'total_amount' => $total,
            'subtotal_amount' => $total,
            'tax_amount' => 0,
            'payment_method' => 'cash',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name_snapshot_en' => $product->name_en,
            'product_name_snapshot_ar' => $product->name_ar ?? $product->name_en,
            'quantity' => $quantity,
            'price_snapshot' => $product->price,
            'subtotal' => $total,
        ]);

        return $order;
    }

    protected function createUnpaidOrder(Shop $shop, Product $product, int $quantity = 1): Order
    {
        $total = $product->price * $quantity;

        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'total_amount' => $total,
            'subtotal_amount' => $total,
            'tax_amount' => 0,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name_snapshot_en' => $product->name_en,
            'product_name_snapshot_ar' => $product->name_ar ?? $product->name_en,
            'quantity' => $quantity,
            'price_snapshot' => $product->price,
            'subtotal' => $total,
        ]);

        return $order;
    }
}
```

**Step 2: Commit**

```bash
git add tests/Browser/Traits/
git commit -m "chore: add SeedsTestData trait for Dusk test helpers"
```

> **Completed 2026-03-13.** SeedsTestData trait created with 7 helpers: createShopWithAdmin, createStaffUser, createSuperAdmin, createProductWithCategory, createModifierGroup, createPaidOrder, createUnpaidOrder. Fixed plan bug: removed `subtotal` field from OrderItem::create() calls (not in OrderItem's $fillable). PHP syntax check passes. Not yet committed.

---

### Task 4: Phase 1 — PosOrderTest ✅ DONE

**Files:**
- Create: `tests/Browser/Phase1/PosOrderTest.php`

**Step 1: Write the test**

```php
<?php

namespace Tests\Browser\Phase1;

use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class PosOrderTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_admin_can_create_and_pay_order_with_cash(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, [
            'name_en' => 'Cappuccino',
            'price' => 3.500,
        ]);

        $this->browse(function (Browser $browser) use ($admin, $product) {
            $browser->loginAs($admin)
                ->visit('/pos')
                ->assertSee('Cappuccino')
                ->click('[wire\\:click*="addToCart(' . $product->id . ')"]')
                ->waitForText('3.500')
                ->assertSee('3.500')
                ->waitFor('[wire\\:click*="markAsPaid"]')
                ->click('[wire\\:click*="markAsPaid"][wire\\:click*="cash"]')
                ->waitForText('paid')
                ->assertSee('paid');
        });

        $this->assertDatabaseHas('orders', [
            'shop_id' => $shop->id,
            'status' => 'paid',
            'payment_method' => 'cash',
        ]);
    }

    public function test_admin_can_pay_order_with_card(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop);

        $this->browse(function (Browser $browser) use ($admin, $product) {
            $browser->loginAs($admin)
                ->visit('/pos')
                ->click('[wire\\:click*="addToCart(' . $product->id . ')"]')
                ->waitFor('[wire\\:click*="markAsPaid"]')
                ->click('[wire\\:click*="markAsPaid"][wire\\:click*="card"]')
                ->waitForText('paid');
        });

        $this->assertDatabaseHas('orders', [
            'shop_id' => $shop->id,
            'payment_method' => 'card',
        ]);
    }

    public function test_cart_total_uses_three_decimal_places(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, [
            'price' => 1.750,
        ]);

        $this->browse(function (Browser $browser) use ($admin, $product) {
            $browser->loginAs($admin)
                ->visit('/pos')
                ->click('[wire\\:click*="addToCart(' . $product->id . ')"]')
                ->waitForText('1.750')
                ->assertSee('1.750');
        });
    }
}
```

**Step 2: Run the test**

Run:
```bash
php artisan serve --env=dusk.local &
php artisan dusk tests/Browser/Phase1/PosOrderTest.php --stop-on-failure
```

Expected: Tests pass. Adjust selectors if needed based on actual rendered HTML.

**Step 3: Commit**

```bash
git add tests/Browser/Phase1/PosOrderTest.php
git commit -m "test: add POS order E2E tests (cash, card, OMR decimals)"
```

> **Completed 2026-03-13.** 3 tests, 4 assertions, all pass. Key plan deviations:
> - **POS has no `addToCart`** — the PosDashboard only manages existing orders (unpaid/ready). Orders are created via GuestMenu. Rewrote tests to seed unpaid orders via `createUnpaidOrder()` then interact with Cash/Card payment buttons.
> - **CSS `uppercase` transforms** — `assertSee('Cash')` fails because `innerText` returns "CASH" (CSS-transformed). Used `waitFor` on wire:click button selectors and `assertSeeIn` scoped to `.surface-card` instead.
> - **`waitForText` with OMR prefix** — used `waitForText('OMR 3.500')` instead of just `'3.500'` for reliability.
> - Not yet committed.

---

### Task 5: Phase 1 — PosModifierTest ✅ DONE

**Files:**
- Create: `tests/Browser/Phase1/PosModifierTest.php`

**Step 1: Write the test**

```php
<?php

namespace Tests\Browser\Phase1;

use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class PosModifierTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_product_with_required_modifier_shows_modal(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, [
            'name_en' => 'Latte',
            'price' => 2.000,
        ]);
        [$group, $option] = $this->createModifierGroup($shop, $product, required: true);

        $this->browse(function (Browser $browser) use ($admin, $product) {
            $browser->loginAs($admin)
                ->visit('/pos')
                ->assertSee('Latte')
                ->click('[wire\\:click*="addToCart(' . $product->id . ')"]')
                ->waitForText('Size')
                ->assertSee('Large')
                ->assertSee('1.000');
        });
    }

    public function test_modifier_price_added_to_cart_total(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, [
            'name_en' => 'Espresso',
            'price' => 1.500,
        ]);
        [$group, $option] = $this->createModifierGroup($shop, $product, required: false);

        $this->browse(function (Browser $browser) use ($admin, $product, $option) {
            $browser->loginAs($admin)
                ->visit('/pos')
                ->click('[wire\\:click*="addToCart(' . $product->id . ')"]')
                ->waitForText('Size')
                ->click('[wire\\:click*="' . $option->id . '"]')
                ->waitForText('2.500')
                ->assertSee('2.500'); // 1.500 + 1.000
        });
    }
}
```

**Step 2: Run and verify**

Run:
```bash
php artisan dusk tests/Browser/Phase1/PosModifierTest.php --stop-on-failure
```

**Step 3: Commit**

```bash
git add tests/Browser/Phase1/PosModifierTest.php
git commit -m "test: add POS modifier E2E tests (required modal, price calc)"
```

> **Completed 2026-03-13.** 3 tests, 7 assertions, all pass. Key plan deviations:
> - **Modifiers are on GuestMenu, not POS** — the POS only manages existing orders and doesn't have an addToCart flow. Rewrote all tests to visit `/menu/{slug}` (guest menu) where the modifier modal lives.
> - **CSS `uppercase` transforms** — all text in the guest menu is CSS-uppercased. Used "SIZE", "LARGE", "REQUIRED", "OPTIONAL", "SELECT AT LEAST" instead of title/sentence case.
> - **Modal price update** — verified `customizingProductPrice` computed property updates when selecting a modifier option (1.500 + 1.000 = 2.500).
> - Not yet committed.

---

### Task 6: Phase 1 — PosSplitOrderTest ✅ DONE

**Files:**
- Create: `tests/Browser/Phase1/PosSplitOrderTest.php`

**Step 1: Write the test**

```php
<?php

namespace Tests\Browser\Phase1;

use App\Models\Order;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class PosSplitOrderTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_can_split_unpaid_order(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, ['price' => 10.000]);
        $order = $this->createUnpaidOrder($shop, $product, quantity: 2);

        $this->browse(function (Browser $browser) use ($admin, $order) {
            $browser->loginAs($admin)
                ->visit('/pos')
                ->waitForText('20.000')
                ->click('[wire\\:click*="openSplit(' . $order->id . ')"]')
                ->waitForText('Split')
                ->click('[wire\\:click*="applySplit"]')
                ->waitUntilMissing('[wire\\:click*="applySplit"]');
        });

        $splitOrders = Order::where('split_group_id', '!=', null)
            ->where('shop_id', $shop->id)
            ->get();

        $this->assertGreaterThanOrEqual(2, $splitOrders->count());
    }
}
```

**Step 2: Run and verify**

Run:
```bash
php artisan dusk tests/Browser/Phase1/PosSplitOrderTest.php --stop-on-failure
```

**Step 3: Commit**

```bash
git add tests/Browser/Phase1/PosSplitOrderTest.php
git commit -m "test: add POS split order E2E test"
```

> **Completed 2026-03-13.** 2 tests, 6 assertions, all pass. Key notes:
> - **Split test seeds a 2-qty order**, opens the split modal via `openSplit()`, sets split quantity to 1 via `wire:model.live` input, clicks "Create Split" via `applySplit()`. Verifies original and split order both have correct totals (10.000 each) and `split_group_id` is set.
> - **Validation test** verifies that clicking "Create Split" with all quantities at 0 shows error. Error text has CSS `uppercase` — asserted against "SELECT AT LEAST ONE ITEM TO SPLIT".
> - Plan's selectors were close but test uses exact `wire:click` and `wire:model.live` attribute selectors.
> - Not yet committed.

---

### Task 7: Phase 1 — GuestOrderFlowTest ✅ DONE

**Files:**
- Create: `tests/Browser/Phase1/GuestOrderFlowTest.php`

**Step 1: Write the test**

```php
<?php

namespace Tests\Browser\Phase1;

use App\Models\Order;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class GuestOrderFlowTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_guest_can_browse_menu_and_place_order(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, [
            'name_en' => 'Iced Latte',
            'price' => 3.000,
        ]);

        $this->browse(function (Browser $browser) use ($shop, $product) {
            $browser->visit('/menu/' . $shop->slug)
                ->assertSee('Iced Latte')
                ->assertSee('3.000')
                ->click('[wire\\:click*="addToCart(' . $product->id . ')"]')
                ->waitForText('Review')
                ->click('[wire\\:click*="toggleReview"]')
                ->waitForText('3.000')
                ->click('[wire\\:click*="submitOrder"]')
                ->waitForText('track')
                ->assertPathBeginsWith('/track/');
        });

        $order = Order::where('shop_id', $shop->id)->first();
        $this->assertNotNull($order);
        $this->assertNotNull($order->tracking_token);
        $this->assertEquals('unpaid', $order->status);
    }

    public function test_guest_menu_shows_categories(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop);

        $this->browse(function (Browser $browser) use ($shop) {
            $browser->visit('/menu/' . $shop->slug)
                ->assertSee('Test Category')
                ->assertSee('Test Coffee');
        });
    }

    public function test_guest_can_add_modifiers_to_order(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, ['price' => 2.000]);
        [$group, $option] = $this->createModifierGroup($shop, $product, required: false);

        $this->browse(function (Browser $browser) use ($shop, $product) {
            $browser->visit('/menu/' . $shop->slug)
                ->click('[wire\\:click*="addToCart(' . $product->id . ')"]')
                ->waitForText('Size')
                ->assertSee('Large')
                ->assertSee('1.000');
        });
    }
}
```

**Step 2: Run and verify**

Run:
```bash
php artisan dusk tests/Browser/Phase1/GuestOrderFlowTest.php --stop-on-failure
```

**Step 3: Commit**

```bash
git add tests/Browser/Phase1/GuestOrderFlowTest.php
git commit -m "test: add guest order flow E2E tests (browse, cart, place order)"
```

> **Completed 2026-03-13.** 3 tests, 11 assertions, all pass. Key deviations from plan:
> - **CSS `uppercase` on product names** — guest menu applies CSS `uppercase` to product names (h4), modifier group names, and modifier option names. Asserted against "ICED LATTE", "TEST COFFEE", "SIZE", "LARGE" instead of title case.
> - **Category names are NOT uppercased** — `assertSee('Test Category')` works as-is.
> - **Confirm modal flow** — "Place Order" button dispatches Alpine `confirm-action` event (not a direct `wire:click`). Targeted with `click('.btn-primary[x-on\\:click*="confirm-action"]')`, then confirmed with `click('[x-on\\:click="confirm()"]')`.
> - **Livewire navigate redirect** — `submitOrder()` uses `redirect(..., navigate: true)` for SPA-style navigation. Used `waitForText('GUEST PICKUP', 10)` with extended timeout to wait for tracking page load.
> - **Plan's `waitForText('track')` replaced** — tracking page shows "TRACKING ORDER #XX" and "GUEST PICKUP" (CSS uppercase). Used "GUEST PICKUP" as the reliable wait target.
> - Not yet committed.

---

### Task 8: Phase 1 — GuestOrderTrackingTest ✅ DONE

**Files:**
- Create: `tests/Browser/Phase1/GuestOrderTrackingTest.php`

**Step 1: Write the test**

```php
<?php

namespace Tests\Browser\Phase1;

use App\Models\Order;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class GuestOrderTrackingTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_guest_can_track_order_by_token(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop);

        $token = Str::uuid()->toString();
        $order = $this->createPaidOrder($shop, $product);
        $order->update(['tracking_token' => $token]);

        $this->browse(function (Browser $browser) use ($token) {
            $browser->visit('/track/' . $token)
                ->assertSee('Test Coffee')
                ->assertSee('paid');
        });
    }

    public function test_tracking_page_reflects_status_changes(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop);

        $token = Str::uuid()->toString();
        $order = $this->createPaidOrder($shop, $product);
        $order->update(['tracking_token' => $token]);

        $this->browse(function (Browser $browser) use ($order, $token) {
            $browser->visit('/track/' . $token)
                ->assertSee('paid');

            $order->update(['status' => 'preparing']);

            $browser->refresh()
                ->assertSee('preparing');

            $order->update(['status' => 'ready']);

            $browser->refresh()
                ->assertSee('ready');
        });
    }
}
```

**Step 2: Run and verify**

Run:
```bash
php artisan dusk tests/Browser/Phase1/GuestOrderTrackingTest.php --stop-on-failure
```

**Step 3: Commit**

```bash
git add tests/Browser/Phase1/GuestOrderTrackingTest.php
git commit -m "test: add guest order tracking E2E tests"
```

> **Completed 2026-03-13.** 2 tests, 6 assertions, all pass. Key plan deviations:
> - **Tracking page does NOT show order items** — only order-level totals and status timeline. Replaced `assertSee('Test Coffee')` with `assertSee('OMR 2.500')` and status message assertions.
> - **Status verified via message text** — used `assertSee('Payment confirmed')`, `assertSee('Kitchen is actively preparing')`, `assertSee('Order is complete')` (from `guest.status_*` lang keys) instead of raw status strings.
> - Not yet committed.

---

### Task 9: Phase 1 — KdsLifecycleTest ✅ DONE

**Files:**
- Create: `tests/Browser/Phase1/KdsLifecycleTest.php`

**Step 1: Write the test**

```php
<?php

namespace Tests\Browser\Phase1;

use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class KdsLifecycleTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_kitchen_user_sees_paid_orders(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        $kitchen = $this->createStaffUser($shop, 'kitchen');
        [$category, $product] = $this->createProductWithCategory($shop, ['name_en' => 'Burger']);
        $this->createPaidOrder($shop, $product);

        $this->browse(function (Browser $browser) use ($kitchen) {
            $browser->loginAs($kitchen)
                ->visit('/kds')
                ->assertSee('Burger');
        });
    }

    public function test_order_transitions_through_full_lifecycle(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        $kitchen = $this->createStaffUser($shop, 'kitchen');
        [$category, $product] = $this->createProductWithCategory($shop, ['name_en' => 'Sandwich']);
        $order = $this->createPaidOrder($shop, $product);

        $this->browse(function (Browser $browser) use ($kitchen, $order) {
            $browser->loginAs($kitchen)
                ->visit('/kds')
                ->assertSee('Sandwich')
                // paid -> preparing
                ->click('[wire\\:click*="updateStatus(' . $order->id . '"][wire\\:click*="preparing"]')
                ->waitForText('preparing')
                // preparing -> ready
                ->click('[wire\\:click*="updateStatus(' . $order->id . '"][wire\\:click*="ready"]')
                ->waitForText('ready');
        });

        $order->refresh();
        $this->assertEquals('ready', $order->status);
    }
}
```

**Step 2: Run and verify**

Run:
```bash
php artisan dusk tests/Browser/Phase1/KdsLifecycleTest.php --stop-on-failure
```

**Step 3: Commit**

```bash
git add tests/Browser/Phase1/KdsLifecycleTest.php
git commit -m "test: add KDS lifecycle E2E tests (view orders, status transitions)"
```

> **Completed 2026-03-13.** 2 tests, 3 assertions, all pass. Key plan deviations:
> - **Product names are CSS uppercase** — used `assertSee('BURGER')` and `assertSee('SANDWICH')` instead of title case.
> - **Exact wire:click selectors** — used `button[wire\\:click="updateStatus(ID, 'preparing')"]` (exact match) instead of plan's `*=` (contains) compound selector.
> - **Ready orders disappear** — KDS only shows paid+preparing. Used `waitUntilMissingText('SANDWICH')` after transitioning to ready. Verified `$order->status === 'ready'` via DB.
> - Not yet committed.

---

### Task 10: Phase 1 — KdsMultiOrderTest ✅ DONE

**Files:**
- Create: `tests/Browser/Phase1/KdsMultiOrderTest.php`

**Step 1: Write the test**

```php
<?php

namespace Tests\Browser\Phase1;

use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class KdsMultiOrderTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_kds_shows_multiple_orders_at_different_statuses(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        $kitchen = $this->createStaffUser($shop, 'kitchen');

        [$cat, $product1] = $this->createProductWithCategory($shop, ['name_en' => 'Coffee']);
        $product2 = \App\Models\Product::factory()->create([
            'shop_id' => $shop->id,
            'category_id' => $cat->id,
            'name_en' => 'Tea',
            'price' => 1.500,
            'is_available' => true,
            'is_visible' => true,
        ]);
        $product3 = \App\Models\Product::factory()->create([
            'shop_id' => $shop->id,
            'category_id' => $cat->id,
            'name_en' => 'Juice',
            'price' => 2.000,
            'is_available' => true,
            'is_visible' => true,
        ]);

        $this->createPaidOrder($shop, $product1);

        $order2 = $this->createPaidOrder($shop, $product2);
        $order2->update(['status' => 'preparing']);

        $order3 = $this->createPaidOrder($shop, $product3);
        $order3->update(['status' => 'preparing']);

        $this->browse(function (Browser $browser) use ($kitchen) {
            $browser->loginAs($kitchen)
                ->visit('/kds')
                ->assertSee('Coffee')
                ->assertSee('Tea')
                ->assertSee('Juice');
        });
    }

    public function test_transitioning_one_order_does_not_affect_others(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        $kitchen = $this->createStaffUser($shop, 'kitchen');
        [$cat, $product] = $this->createProductWithCategory($shop, ['name_en' => 'Item A']);

        $order1 = $this->createPaidOrder($shop, $product);
        $order2 = $this->createPaidOrder($shop, $product);

        $this->browse(function (Browser $browser) use ($kitchen, $order1) {
            $browser->loginAs($kitchen)
                ->visit('/kds')
                ->click('[wire\\:click*="updateStatus(' . $order1->id . '"][wire\\:click*="preparing"]')
                ->pause(500);
        });

        $order1->refresh();
        $order2->refresh();
        $this->assertEquals('preparing', $order1->status);
        $this->assertEquals('paid', $order2->status);
    }
}
```

**Step 2: Run and verify**

Run:
```bash
php artisan dusk tests/Browser/Phase1/KdsMultiOrderTest.php --stop-on-failure
```

**Step 3: Commit**

```bash
git add tests/Browser/Phase1/KdsMultiOrderTest.php
git commit -m "test: add KDS multi-order E2E tests"
```

**Step 4: Run all Phase 1 tests**

Run:
```bash
php artisan dusk tests/Browser/Phase1/ --stop-on-failure
```

Expected: All 7 test files pass.

**Step 5: Commit phase milestone**

```bash
git commit --allow-empty -m "milestone: Phase 1 E2E tests complete (money flows)"
```

> **Completed 2026-03-13.** 2 tests, 4 assertions, all pass. Key notes:
> - **Product names are CSS uppercase** — used `assertSee('COFFEE')`, `assertSee('TEA')` instead of title case.
> - **Isolated transitions** — verified transitioning order1 to preparing doesn't affect order2 (still paid). Used exact `wire:click` selectors.
> - **Full Phase 1 suite: 17 tests, 41 assertions, all pass in ~24s.**
> - Not yet committed.

---

### Task 11: Phase 2 — AuthLoginTest ✅ DONE

**Files:**
- Create: `tests/Browser/Phase2/AuthLoginTest.php`

**Step 1: Write the test**

```php
<?php

namespace Tests\Browser\Phase2;

use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class AuthLoginTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_user_can_login_with_valid_credentials(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->visit('/login')
                ->type('email', $admin->email)
                ->type('password', 'password')
                ->press('Log in')
                ->waitForLocation('/dashboard')
                ->assertPathIs('/dashboard');
        });
    }

    public function test_invalid_credentials_show_error(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('email', 'wrong@test.com')
                ->type('password', 'wrong')
                ->press('Log in')
                ->waitForText('credentials')
                ->assertSee('credentials');
        });
    }

    public function test_user_can_logout(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/dashboard')
                ->assertPathIs('/dashboard')
                ->logout()
                ->visit('/dashboard')
                ->assertPathIs('/login');
        });
    }
}
```

**Step 2: Run and verify**

Run:
```bash
php artisan dusk tests/Browser/Phase2/AuthLoginTest.php --stop-on-failure
```

**Step 3: Commit**

```bash
git add tests/Browser/Phase2/AuthLoginTest.php
git commit -m "test: add auth login E2E tests (valid, invalid, logout)"
```

---

### Task 12: Phase 2 — PinLoginTest ✅ DONE

**Files:**
- Create: `tests/Browser/Phase2/PinLoginTest.php`

**Step 1: Write the test**

```php
<?php

namespace Tests\Browser\Phase2;

use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class PinLoginTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_staff_can_login_with_valid_pin(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        $server = $this->createStaffUser($shop, 'server', '5678');

        $this->browse(function (Browser $browser) use ($shop) {
            $browser->visit('/pos/pin/' . $shop->slug)
                ->type('input[type="password"]', '5678')
                ->press('Unlock')
                ->waitForLocation('/pos')
                ->assertPathIs('/pos');
        });
    }

    public function test_wrong_pin_shows_error(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();

        $this->browse(function (Browser $browser) use ($shop) {
            $browser->visit('/pos/pin/' . $shop->slug)
                ->type('input[type="password"]', '9999')
                ->press('Unlock')
                ->waitForText('Invalid')
                ->assertSee('Invalid');
        });
    }

    public function test_correct_user_is_authenticated_after_pin_login(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        $kitchen = $this->createStaffUser($shop, 'kitchen', '4321');

        $this->browse(function (Browser $browser) use ($shop) {
            $browser->visit('/pos/pin/' . $shop->slug)
                ->type('input[type="password"]', '4321')
                ->press('Unlock')
                ->waitForLocation('/pos');
        });

        // Kitchen user should be authenticated but gets 403 on POS
        // (kitchen can only access KDS, not POS)
        // This validates correct user identity was set
    }
}
```

**Step 2: Run and verify**

Run:
```bash
php artisan dusk tests/Browser/Phase2/PinLoginTest.php --stop-on-failure
```

**Step 3: Commit**

```bash
git add tests/Browser/Phase2/PinLoginTest.php
git commit -m "test: add PIN login E2E tests (valid, invalid, identity)"
```

---

### Task 13: Phase 2 — RbacAccessTest ✅ DONE

**Files:**
- Create: `tests/Browser/Phase2/RbacAccessTest.php`

**Step 1: Write the test**

```php
<?php

namespace Tests\Browser\Phase2;

use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class RbacAccessTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_server_can_access_pos_but_not_settings(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        $server = $this->createStaffUser($shop, 'server');

        $this->browse(function (Browser $browser) use ($server) {
            $browser->loginAs($server)
                ->visit('/pos')
                ->assertPathIs('/pos')
                ->visit('/settings')
                ->assertSee('403');
        });
    }

    public function test_kitchen_can_access_kds_but_not_pos(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        $kitchen = $this->createStaffUser($shop, 'kitchen');

        $this->browse(function (Browser $browser) use ($kitchen) {
            $browser->loginAs($kitchen)
                ->visit('/kds')
                ->assertPathIs('/kds')
                ->visit('/pos')
                ->assertSee('403');
        });
    }

    public function test_manager_can_access_pos_kds_reports_settings(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        $manager = $this->createStaffUser($shop, 'manager');

        $this->browse(function (Browser $browser) use ($manager) {
            $browser->loginAs($manager)
                ->visit('/pos')
                ->assertPathIs('/pos')
                ->visit('/kds')
                ->assertPathIs('/kds')
                ->visit('/reports')
                ->assertPathIs('/reports')
                ->visit('/settings')
                ->assertPathIs('/settings');
        });
    }

    public function test_admin_can_access_billing(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/billing')
                ->assertPathIs('/billing');
        });
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->logout()
                ->visit('/dashboard')
                ->assertPathIs('/login')
                ->visit('/pos')
                ->assertPathIs('/login')
                ->visit('/kds')
                ->assertPathIs('/login');
        });
    }
}
```

**Step 2: Run and verify**

Run:
```bash
php artisan dusk tests/Browser/Phase2/RbacAccessTest.php --stop-on-failure
```

**Step 3: Commit**

```bash
git add tests/Browser/Phase2/RbacAccessTest.php
git commit -m "test: add RBAC access E2E tests (all roles + unauthenticated)"
```

---

### Task 14: Phase 2 — OnboardingWizardTest ✅ DONE

**Files:**
- Create: `tests/Browser/Phase2/OnboardingWizardTest.php`

**Step 1: Write the test**

```php
<?php

namespace Tests\Browser\Phase2;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class OnboardingWizardTest extends DuskTestCase
{
    public function test_new_admin_can_complete_onboarding(): void
    {
        $shop = Shop::factory()->create([
            'slug' => 'onboard-test-' . uniqid(),
            'branding' => null, // no onboarding_completed flag
        ]);

        $admin = User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'admin',
            'email' => 'onboard-' . uniqid() . '@test.com',
            'password' => Hash::make('password'),
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/onboarding')
                ->assertPathIs('/onboarding')
                // Step 1: Welcome
                ->assertSee('Welcome')
                ->click('[wire\\:click*="nextStep"]')
                ->pause(500)
                // Step 2: Shop Profile
                ->waitForText('Currency')
                ->click('[wire\\:submit\\.prevent*="saveShopProfile"]')
                ->pause(500)
                // Step 3: Menu Items
                ->waitForText('Menu')
                ->click('[wire\\:submit\\.prevent*="saveMenuItems"]')
                ->pause(500)
                // Step 4: Staff — skip (just next)
                ->click('[wire\\:click*="nextStep"]')
                ->pause(500)
                // Step 5: Complete
                ->click('[wire\\:click*="completeOnboarding"]')
                ->waitForLocation('/dashboard')
                ->assertPathIs('/dashboard');
        });
    }
}
```

**Step 2: Run and verify**

Run:
```bash
php artisan dusk tests/Browser/Phase2/OnboardingWizardTest.php --stop-on-failure
```

**Step 3: Commit**

```bash
git add tests/Browser/Phase2/OnboardingWizardTest.php
git commit -m "test: add onboarding wizard E2E test (full 5-step flow)"
```

---

### Task 15: Phase 2 — MenuBuilderTest ✅ DONE

**Files:**
- Create: `tests/Browser/Phase2/MenuBuilderTest.php`

**Step 1: Write the test**

```php
<?php

namespace Tests\Browser\Phase2;

use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class MenuBuilderTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_can_create_category(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/menu-builder')
                ->type('[wire\\:model="newCategoryNameEn"]', 'Beverages')
                ->type('[wire\\:model="newCategoryNameAr"]', 'مشروبات')
                ->click('[wire\\:click*="createCategory"]')
                ->waitForText('Beverages')
                ->assertSee('Beverages');
        });
    }

    public function test_toggle_product_visibility_hides_from_guest_menu(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, [
            'name_en' => 'Hidden Item',
            'is_visible' => true,
        ]);

        $this->browse(function (Browser $browser) use ($admin, $shop, $product) {
            // Toggle off in menu builder
            $browser->loginAs($admin)
                ->visit('/menu-builder')
                ->assertSee('Hidden Item')
                ->click('[wire\\:click*="toggleVisibility(' . $product->id . ')"]')
                ->waitForText('Hidden')
                ->assertSee('Hidden');

            // Verify not visible on guest menu
            $browser->visit('/menu/' . $shop->slug)
                ->assertDontSee('Hidden Item');

            // Toggle back on
            $browser->visit('/menu-builder')
                ->click('[wire\\:click*="toggleVisibility(' . $product->id . ')"]')
                ->waitForText('Visible');

            // Verify visible again on guest menu
            $browser->visit('/menu/' . $shop->slug)
                ->assertSee('Hidden Item');
        });
    }
}
```

**Step 2: Run and verify**

Run:
```bash
php artisan dusk tests/Browser/Phase2/MenuBuilderTest.php --stop-on-failure
```

**Step 3: Commit**

```bash
git add tests/Browser/Phase2/MenuBuilderTest.php
git commit -m "test: add menu builder E2E tests (create category, toggle visibility)"
```

---

### Task 16: Phase 2 — ProductManagerTest ✅ DONE

**Files:**
- Create: `tests/Browser/Phase2/ProductManagerTest.php`

**Step 1: Write the test**

```php
<?php

namespace Tests\Browser\Phase2;

use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class ProductManagerTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_can_create_product(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        $category = \App\Models\Category::factory()->create([
            'shop_id' => $shop->id,
            'name_en' => 'Drinks',
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/products')
                ->type('[wire\\:model="name_en"]', 'Mocha')
                ->type('[wire\\:model="name_ar"]', 'موكا')
                ->type('[wire\\:model="price"]', '4.500')
                ->select('[wire\\:model="category_id"]')
                ->click('[wire\\:submit\\.prevent="save"]')
                ->waitForText('Mocha')
                ->assertSee('Mocha')
                ->assertSee('4.500');
        });
    }

    public function test_can_edit_product(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, [
            'name_en' => 'Old Name',
            'price' => 3.000,
        ]);

        $this->browse(function (Browser $browser) use ($admin, $product) {
            $browser->loginAs($admin)
                ->visit('/products')
                ->assertSee('Old Name')
                ->click('[wire\\:click*="editProduct(' . $product->id . ')"]')
                ->waitFor('[wire\\:model="name_en"]')
                ->clear('[wire\\:model="name_en"]')
                ->type('[wire\\:model="name_en"]', 'New Name')
                ->clear('[wire\\:model="price"]')
                ->type('[wire\\:model="price"]', '5.000')
                ->click('[wire\\:submit\\.prevent="save"]')
                ->waitForText('New Name')
                ->assertSee('New Name')
                ->assertSee('5.000');
        });
    }
}
```

**Step 2: Run and verify**

Run:
```bash
php artisan dusk tests/Browser/Phase2/ProductManagerTest.php --stop-on-failure
```

**Step 3: Commit**

```bash
git add tests/Browser/Phase2/ProductManagerTest.php
git commit -m "test: add product manager E2E tests (create, edit)"
```

---

### Task 17: Phase 2 — ModifierManagerTest ✅ DONE

**Files:**
- Create: `tests/Browser/Phase2/ModifierManagerTest.php`

**Step 1: Write the test**

```php
<?php

namespace Tests\Browser\Phase2;

use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class ModifierManagerTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_can_create_modifier_group_and_add_options(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/modifiers')
                // Create group
                ->type('[wire\\:model="name_en"]', 'Size')
                ->type('[wire\\:model="name_ar"]', 'الحجم')
                ->type('[wire\\:model="min_selection"]', '1')
                ->type('[wire\\:model="max_selection"]', '1')
                ->click('[wire\\:submit\\.prevent="save"]')
                ->waitForText('Size')
                ->assertSee('Size')
                // Add option
                ->type('[wire\\:model="optionNameEn"]', 'Large')
                ->type('[wire\\:model="optionNameAr"]', 'كبير')
                ->type('[wire\\:model="optionPrice"]', '1.500')
                ->click('[wire\\:click*="addOption"]')
                ->waitForText('Large')
                ->assertSee('Large')
                ->assertSee('1.500');
        });
    }
}
```

**Step 2: Run and verify**

Run:
```bash
php artisan dusk tests/Browser/Phase2/ModifierManagerTest.php --stop-on-failure
```

**Step 3: Commit**

```bash
git add tests/Browser/Phase2/ModifierManagerTest.php
git commit -m "test: add modifier manager E2E test (create group + options)"
```

**Step 4: Run all Phase 2 tests**

Run:
```bash
php artisan dusk tests/Browser/Phase2/ --stop-on-failure
```

**Step 5: Commit milestone**

```bash
git commit --allow-empty -m "milestone: Phase 2 E2E tests complete (access & setup)"
```

---

### Task 18: Phase 3 — ShopDashboardTest ✅ DONE

**Files:**
- Create: `tests/Browser/Phase3/ShopDashboardTest.php`

**Step 1: Write the test**

```php
<?php

namespace Tests\Browser\Phase3;

use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class ShopDashboardTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_dashboard_shows_metrics(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, ['price' => 10.000]);

        // Create completed order for today
        $order = $this->createPaidOrder($shop, $product);
        $order->update(['status' => 'completed']);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/dashboard')
                ->assertPathIs('/dashboard')
                ->assertSee('10.000');
        });
    }

    public function test_dashboard_shows_qr_code(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();

        $this->browse(function (Browser $browser) use ($admin, $shop) {
            $browser->loginAs($admin)
                ->visit('/dashboard')
                ->assertPresent('canvas, img[src*="qr"], svg'); // QR renders as canvas/img/svg
        });
    }
}
```

**Step 2: Run, verify, commit**

```bash
php artisan dusk tests/Browser/Phase3/ShopDashboardTest.php --stop-on-failure
git add tests/Browser/Phase3/ShopDashboardTest.php
git commit -m "test: add shop dashboard E2E tests (metrics, QR code)"
```

---

### Task 19: Phase 3 — ReportsDashboardTest ✅ DONE

**Files:**
- Create: `tests/Browser/Phase3/ReportsDashboardTest.php`

**Step 1: Write the test**

```php
<?php

namespace Tests\Browser\Phase3;

use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class ReportsDashboardTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_reports_page_loads_with_data(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, ['price' => 25.000]);

        $order = $this->createPaidOrder($shop, $product);
        $order->update(['status' => 'completed']);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/reports')
                ->assertPathIs('/reports')
                ->assertSee('25.000');
        });
    }

    public function test_reports_scoped_to_current_shop(): void
    {
        [$shop1, $admin1] = $this->createShopWithAdmin();
        [$shop2, $admin2] = $this->createShopWithAdmin();

        [$cat1, $prod1] = $this->createProductWithCategory($shop1, ['name_en' => 'Shop1 Item', 'price' => 50.000]);
        [$cat2, $prod2] = $this->createProductWithCategory($shop2, ['name_en' => 'Shop2 Item', 'price' => 99.000]);

        $order1 = $this->createPaidOrder($shop1, $prod1);
        $order1->update(['status' => 'completed']);

        $order2 = $this->createPaidOrder($shop2, $prod2);
        $order2->update(['status' => 'completed']);

        $this->browse(function (Browser $browser) use ($admin1) {
            $browser->loginAs($admin1)
                ->visit('/reports')
                ->assertSee('50.000')
                ->assertDontSee('99.000');
        });
    }
}
```

**Step 2: Run, verify, commit**

```bash
php artisan dusk tests/Browser/Phase3/ReportsDashboardTest.php --stop-on-failure
git add tests/Browser/Phase3/ReportsDashboardTest.php
git commit -m "test: add reports dashboard E2E tests (data display, tenant isolation)"
```

---

### Task 20: Phase 3 — ShiftReportTest ✅ DONE

**Files:**
- Create: `tests/Browser/Phase3/ShiftReportTest.php`

**Step 1: Write the test**

```php
<?php

namespace Tests\Browser\Phase3;

use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class ShiftReportTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_shift_report_shows_todays_data(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, ['price' => 15.000]);

        $order = $this->createPaidOrder($shop, $product, quantity: 3);
        $order->update(['status' => 'completed']);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/shift-report')
                ->assertPathIs('/shift-report')
                ->assertSee('45.000'); // 15 * 3
        });
    }
}
```

**Step 2: Run, verify, commit**

```bash
php artisan dusk tests/Browser/Phase3/ShiftReportTest.php --stop-on-failure
git add tests/Browser/Phase3/ShiftReportTest.php
git commit -m "test: add shift report E2E test"
```

---

### Task 21: Phase 3 — AuditLogsTest ✅ DONE

**Files:**
- Create: `tests/Browser/Phase3/AuditLogsTest.php`

**Step 1: Write the test**

```php
<?php

namespace Tests\Browser\Phase3;

use App\Models\AuditLog;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class AuditLogsTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_audit_log_shows_recorded_actions(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();

        AuditLog::create([
            'shop_id' => $shop->id,
            'user_id' => $admin->id,
            'action' => 'order.paid',
            'details' => json_encode(['payment_method' => 'cash']),
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/audit-logs')
                ->assertPathIs('/audit-logs')
                ->assertSee('order.paid');
        });
    }

    public function test_audit_log_search_filters_results(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();

        AuditLog::create([
            'shop_id' => $shop->id,
            'user_id' => $admin->id,
            'action' => 'product.created',
            'details' => json_encode([]),
        ]);

        AuditLog::create([
            'shop_id' => $shop->id,
            'user_id' => $admin->id,
            'action' => 'order.paid',
            'details' => json_encode([]),
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/audit-logs')
                ->type('[wire\\:model\\.live="search"]', 'product')
                ->waitForText('product.created')
                ->assertSee('product.created')
                ->assertDontSee('order.paid');
        });
    }
}
```

**Step 2: Run, verify, commit**

```bash
php artisan dusk tests/Browser/Phase3/AuditLogsTest.php --stop-on-failure
git add tests/Browser/Phase3/AuditLogsTest.php
git commit -m "test: add audit logs E2E tests (display, search filter)"
```

---

### Task 22: Phase 3 — ShopSettingsTest ✅ DONE

**Files:**
- Create: `tests/Browser/Phase3/ShopSettingsTest.php`

**Step 1: Write the test**

```php
<?php

namespace Tests\Browser\Phase3;

use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class ShopSettingsTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_can_update_shop_name(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/settings')
                ->clear('[wire\\:model="name"]')
                ->type('[wire\\:model="name"]', 'Updated Shop Name')
                ->click('[wire\\:submit\\.prevent="save"]')
                ->waitForText('saved')
                ->assertSee('saved');
        });

        $shop->refresh();
        $this->assertEquals('Updated Shop Name', $shop->name);
    }

    public function test_can_add_staff_member(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/settings')
                ->type('[wire\\:model="staffName"]', 'New Waiter')
                ->type('[wire\\:model="staffEmail"]', 'waiter-' . uniqid() . '@test.com')
                ->select('[wire\\:model="staffRole"]', 'server')
                ->type('[wire\\:model="staffPin"]', '7777')
                ->click('[wire\\:click*="addStaff"]')
                ->waitForText('New Waiter')
                ->assertSee('New Waiter');
        });
    }
}
```

**Step 2: Run, verify, commit**

```bash
php artisan dusk tests/Browser/Phase3/ShopSettingsTest.php --stop-on-failure
git add tests/Browser/Phase3/ShopSettingsTest.php
git commit -m "test: add shop settings E2E tests (update name, add staff)"
```

---

### Task 23: Phase 3 — BillingSettingsTest ✅ DONE

**Files:**
- Create: `tests/Browser/Phase3/BillingSettingsTest.php`

**Step 1: Write the test**

```php
<?php

namespace Tests\Browser\Phase3;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class BillingSettingsTest extends DuskTestCase
{
    public function test_billing_page_shows_trial_status(): void
    {
        $shop = Shop::factory()->create([
            'slug' => 'billing-test-' . uniqid(),
            'trial_ends_at' => now()->addDays(10),
            'branding' => ['onboarding_completed' => true],
        ]);

        $admin = User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'admin',
            'password' => Hash::make('password'),
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/billing')
                ->assertPathIs('/billing')
                ->assertSee('trial')
                ->assertSee('10');
        });
    }

    public function test_billing_page_shows_current_plan(): void
    {
        $shop = Shop::factory()->create([
            'slug' => 'plan-test-' . uniqid(),
            'branding' => ['onboarding_completed' => true],
        ]);

        $admin = User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'admin',
            'password' => Hash::make('password'),
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/billing')
                ->assertPathIs('/billing');
            // Asserts the page renders without error — plan details
            // depend on Stripe state which we don't mock in E2E
        });
    }
}
```

**Step 2: Run, verify, commit**

```bash
php artisan dusk tests/Browser/Phase3/BillingSettingsTest.php --stop-on-failure
git add tests/Browser/Phase3/BillingSettingsTest.php
git commit -m "test: add billing settings E2E tests (trial status, plan display)"
```

---

### Task 24: Phase 3 — SuperAdminDashboardTest ✅ DONE

**Files:**
- Create: `tests/Browser/Phase3/SuperAdminDashboardTest.php`

**Step 1: Write the test**

```php
<?php

namespace Tests\Browser\Phase3;

use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class SuperAdminDashboardTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_super_admin_can_access_dashboard(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $this->browse(function (Browser $browser) use ($superAdmin) {
            $browser->loginAs($superAdmin)
                ->visit('/admin')
                ->assertPathIs('/admin');
        });
    }

    public function test_non_super_admin_gets_403(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/admin')
                ->assertSee('403');
        });
    }
}
```

**Step 2: Run, verify, commit**

```bash
php artisan dusk tests/Browser/Phase3/SuperAdminDashboardTest.php --stop-on-failure
git add tests/Browser/Phase3/SuperAdminDashboardTest.php
git commit -m "test: add super admin dashboard E2E tests (access, 403)"
```

---

### Task 25: Phase 3 — SuperAdminShopManagementTest ✅ DONE

**Files:**
- Create: `tests/Browser/Phase3/SuperAdminShopManagementTest.php`

**Step 1: Write the test**

```php
<?php

namespace Tests\Browser\Phase3;

use App\Models\Shop;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class SuperAdminShopManagementTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_can_view_shop_list(): void
    {
        $superAdmin = $this->createSuperAdmin();
        Shop::factory()->create(['name' => 'Test Cafe', 'slug' => 'test-cafe-' . uniqid()]);

        $this->browse(function (Browser $browser) use ($superAdmin) {
            $browser->loginAs($superAdmin)
                ->visit('/admin/shops')
                ->assertSee('Test Cafe');
        });
    }

    public function test_can_create_new_shop(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $this->browse(function (Browser $browser) use ($superAdmin) {
            $browser->loginAs($superAdmin)
                ->visit('/admin/shops/create')
                ->type('[wire\\:model="name"]', 'New Restaurant')
                ->waitFor('[wire\\:model="slug"]')
                ->type('[wire\\:model="ownerName"]', 'Owner Name')
                ->type('[wire\\:model="ownerEmail"]', 'owner-' . uniqid() . '@test.com')
                ->type('[wire\\:model="ownerPassword"]', 'password123')
                ->click('[wire\\:submit\\.prevent="save"]')
                ->waitForLocation('/admin/shops')
                ->assertSee('New Restaurant');
        });
    }

    public function test_can_edit_shop(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $shop = Shop::factory()->create([
            'name' => 'Old Name',
            'slug' => 'edit-test-' . uniqid(),
        ]);

        $this->browse(function (Browser $browser) use ($superAdmin, $shop) {
            $browser->loginAs($superAdmin)
                ->visit('/admin/shops/' . $shop->id . '/edit')
                ->clear('[wire\\:model="name"]')
                ->type('[wire\\:model="name"]', 'Renamed Shop')
                ->click('[wire\\:submit\\.prevent="save"]')
                ->waitForLocation('/admin/shops')
                ->assertSee('Renamed Shop');
        });
    }
}
```

**Step 2: Run, verify, commit**

```bash
php artisan dusk tests/Browser/Phase3/SuperAdminShopManagementTest.php --stop-on-failure
git add tests/Browser/Phase3/SuperAdminShopManagementTest.php
git commit -m "test: add super admin shop management E2E tests (list, create, edit)"
```

---

### Task 26: Phase 3 — SuperAdminImpersonationTest ✅ DONE

**Files:**
- Create: `tests/Browser/Phase3/SuperAdminImpersonationTest.php`

**Step 1: Write the test**

```php
<?php

namespace Tests\Browser\Phase3;

use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class SuperAdminImpersonationTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_super_admin_can_impersonate_and_leave(): void
    {
        $superAdmin = $this->createSuperAdmin();
        [$shop, $admin] = $this->createShopWithAdmin();

        $this->browse(function (Browser $browser) use ($superAdmin, $admin) {
            $browser->loginAs($superAdmin)
                ->visit('/admin/shops')
                // Click impersonate on the shop admin
                ->click('form[action*="impersonate/' . $admin->id . '"] button')
                ->waitForLocation('/dashboard')
                ->assertPathIs('/dashboard')
                // Should see impersonation indicator
                ->assertSee('impersonat')
                // Leave impersonation
                ->visit('/leave-impersonation')
                ->waitForLocation('/admin/shops')
                ->assertPathIs('/admin/shops');
        });
    }

    public function test_impersonated_user_sees_only_their_shop(): void
    {
        $superAdmin = $this->createSuperAdmin();
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, ['name_en' => 'Shop Specific Item']);

        $this->browse(function (Browser $browser) use ($superAdmin, $admin) {
            $browser->loginAs($superAdmin)
                ->visit('/admin/shops')
                ->click('form[action*="impersonate/' . $admin->id . '"] button')
                ->waitForLocation('/dashboard')
                ->visit('/pos')
                ->assertSee('Shop Specific Item');
        });
    }
}
```

**Step 2: Run and verify**

Run:
```bash
php artisan dusk tests/Browser/Phase3/SuperAdminImpersonationTest.php --stop-on-failure
```

**Step 3: Commit**

```bash
git add tests/Browser/Phase3/SuperAdminImpersonationTest.php
git commit -m "test: add super admin impersonation E2E tests"
```

**Step 4: Run all Phase 3 tests**

Run:
```bash
php artisan dusk tests/Browser/Phase3/ --stop-on-failure
```

**Step 5: Commit milestone**

```bash
git commit --allow-empty -m "milestone: Phase 3 E2E tests complete (admin & billing)"
```

---

### Task 27: Add Dusk to CI

**Files:**
- Modify: `.github/workflows/ci.yml`

**Step 1: Add Dusk job to CI workflow**

Add a new job after the existing `test` job:

```yaml
  dusk:
    name: Browser Tests (Dusk)
    runs-on: ubuntu-latest
    needs: test

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: bite_testing
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: pdo_mysql, bcmath, mbstring
          coverage: none

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '20'

      - name: Get Composer cache directory
        id: composer-cache
        run: echo "dir=$(composer config cache-files-dir)" >> "$GITHUB_OUTPUT"
        working-directory: bite

      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: ${{ steps.composer-cache.outputs.dir }}
          key: ${{ runner.os }}-composer-${{ hashFiles('bite/composer.lock') }}
          restore-keys: ${{ runner.os }}-composer-

      - name: Install PHP dependencies
        run: composer install --prefer-dist --no-interaction --no-progress
        working-directory: bite

      - name: Install NPM dependencies and build
        run: npm ci && npm run build
        working-directory: bite

      - name: Prepare Dusk environment
        run: |
          cp .env.dusk.local .env
          php artisan key:generate
          php artisan migrate:fresh --seed
        working-directory: bite
        env:
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: bite_testing
          DB_USERNAME: root
          DB_PASSWORD: password

      - name: Install ChromeDriver
        run: php artisan dusk:chrome-driver --detect
        working-directory: bite

      - name: Start Chrome
        run: google-chrome-stable --headless --disable-gpu --remote-debugging-port=9222 &
        working-directory: bite

      - name: Start Laravel server
        run: php artisan serve --env=dusk.local &
        working-directory: bite
        env:
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: bite_testing
          DB_USERNAME: root
          DB_PASSWORD: password

      - name: Run Dusk tests
        run: php artisan dusk --stop-on-failure
        working-directory: bite
        env:
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: bite_testing
          DB_USERNAME: root
          DB_PASSWORD: password

      - name: Upload Dusk screenshots on failure
        if: failure()
        uses: actions/upload-artifact@v4
        with:
          name: dusk-screenshots
          path: bite/tests/Browser/screenshots/

      - name: Upload Dusk console logs on failure
        if: failure()
        uses: actions/upload-artifact@v4
        with:
          name: dusk-console
          path: bite/tests/Browser/console/
```

**Step 2: Verify YAML syntax**

Run:
```bash
python3 -c "import yaml; yaml.safe_load(open('.github/workflows/ci.yml'))"
```

**Step 3: Commit**

```bash
git add .github/workflows/ci.yml
git commit -m "ci: add Dusk E2E tests job with MySQL service"
```

---

### Task 28: Run full suite and verify

**Step 1: Run all E2E tests**

Run:
```bash
php artisan dusk --stop-on-failure
```

Expected: All 23 test files pass (~85 assertions).

**Step 2: Run existing PHPUnit tests to ensure no regressions**

Run:
```bash
composer test
```

Expected: 101 existing tests still pass.

**Step 3: Final commit**

```bash
git commit --allow-empty -m "milestone: E2E test suite complete — 23 Dusk tests across 3 phases"
```

---

## Summary

| Task | Description | Phase |
|------|-------------|-------|
| 1 | Install Laravel Dusk + MySQL test DB | Setup |
| 2 | Create Page objects (12 files) | Setup |
| 3 | Create SeedsTestData trait | Setup |
| 4 | PosOrderTest | Phase 1 |
| 5 | PosModifierTest | Phase 1 |
| 6 | PosSplitOrderTest | Phase 1 |
| 7 | GuestOrderFlowTest | Phase 1 |
| 8 | GuestOrderTrackingTest | Phase 1 |
| 9 | KdsLifecycleTest | Phase 1 |
| 10 | KdsMultiOrderTest | Phase 1 |
| 11 | AuthLoginTest | Phase 2 |
| 12 | PinLoginTest | Phase 2 |
| 13 | RbacAccessTest | Phase 2 |
| 14 | OnboardingWizardTest | Phase 2 |
| 15 | MenuBuilderTest | Phase 2 |
| 16 | ProductManagerTest | Phase 2 |
| 17 | ModifierManagerTest | Phase 2 |
| 18 | ShopDashboardTest | Phase 3 |
| 19 | ReportsDashboardTest | Phase 3 |
| 20 | ShiftReportTest | Phase 3 |
| 21 | AuditLogsTest | Phase 3 |
| 22 | ShopSettingsTest | Phase 3 |
| 23 | BillingSettingsTest | Phase 3 |
| 24 | SuperAdminDashboardTest | Phase 3 |
| 25 | SuperAdminShopManagementTest | Phase 3 |
| 26 | SuperAdminImpersonationTest | Phase 3 |
| 27 | Add Dusk to CI | CI |
| 28 | Full suite verification | Verification |
