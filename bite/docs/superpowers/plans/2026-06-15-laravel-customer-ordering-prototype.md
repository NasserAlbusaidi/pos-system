# Laravel Customer Ordering Prototype Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the visible `/menu/demo` guest menu design with the downloaded customer-ordering prototype while preserving Laravel Livewire menu, cart, modifier, checkout, language, and order-submission behavior.

**Architecture:** Keep `App\Livewire\GuestMenu` as the behavior boundary. Convert the prototype's phone-shell, language gate, home/menu, product detail sheet, cart, and checkout visuals into Blade/CSS driven by real Laravel shop/category/product/cart data. Copy only static visual assets into `public/`, avoiding the prototype's mock `app.js` as production behavior.

**Tech Stack:** Laravel 12, Livewire 3, Blade, Vite, vanilla CSS, existing translation files, existing product image and currency helpers.

---

### Task 1: Characterization Tests

**Files:**
- Create: `tests/Feature/GuestMenuPrototypeDesignTest.php`

- [ ] **Step 1: Write failing tests for the new design contract**

```php
<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestMenuPrototypeDesignTest extends TestCase
{
    use RefreshDatabase;

    private function seedShop(): Shop
    {
        $shop = Shop::create([
            'name' => 'The Nitro Bar',
            'slug' => 'demo',
            'branding' => [
                'cover_url' => '/customer-ordering/assets/hopresso/hopresso-cover.png',
                'logo_url' => '/customer-ordering/assets/hopresso/hopresso-logo-white.png',
            ],
            'currency_symbol' => 'OMR',
            'currency_decimals' => 3,
        ]);

        $coffee = Category::create([
            'shop_id' => $shop->id,
            'name_en' => 'Coffee Classics',
            'name_ar' => 'قهوة كلاسيكية',
            'sort_order' => 1,
        ]);

        Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $coffee->id,
            'name_en' => 'Americano',
            'name_ar' => 'أمريكانو',
            'description_en' => 'Hopresso coffee classic',
            'description_ar' => 'قهوة كلاسيكية من هوبريسو',
            'price' => 1.300,
            'image_url' => null,
            'is_available' => true,
            'is_visible' => true,
            'sort_order' => 1,
        ]);

        return $shop;
    }

    public function test_guest_menu_uses_customer_ordering_phone_shell(): void
    {
        $shop = $this->seedShop();

        $response = $this->withSession(['guest_locale' => 'en'])
            ->get(route('guest.menu', $shop->slug));

        $response->assertOk();
        $response->assertSee('bite-phone-shell', false);
        $response->assertSee('bite-menu-screen', false);
        $response->assertSee('Order from your table');
        $response->assertSee('Popular for this table');
        $response->assertSee('Full menu');
        $response->assertSee('Powered by');
    }

    public function test_table_query_parameter_is_reflected_in_menu_context(): void
    {
        $shop = $this->seedShop();

        $response = $this->withSession(['guest_locale' => 'en'])
            ->get(route('guest.menu', ['shop' => $shop->slug, 'table' => '99']));

        $response->assertOk();
        $response->assertSee('Table 99');
    }

    public function test_language_gate_matches_prototype_entry_screen(): void
    {
        $shop = $this->seedShop();

        $response = $this->get(route('guest.menu', $shop->slug));

        $response->assertOk();
        $response->assertSee('bite-language-gate', false);
        $response->assertSee('Choose your language');
        $response->assertSee('اختر لغتك', false);
        $response->assertSee('Continue to menu');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/GuestMenuPrototypeDesignTest.php`

Expected: FAIL because the current Blade does not emit `bite-phone-shell`, `bite-menu-screen`, the prototype table context, or the new gate class.

### Task 2: Prototype Assets

**Files:**
- Create directory: `public/customer-ordering/assets`

- [ ] **Step 1: Copy static visual assets**

Run:

```bash
mkdir -p public/customer-ordering
cp -R /Users/apple/Downloads/customer-ordering-prototype/assets public/customer-ordering/
```

Expected: Hopresso and Bite brand assets are available from `/customer-ordering/assets/...`.

- [ ] **Step 2: Verify copied asset paths**

Run: `find public/customer-ordering/assets -maxdepth 3 -type f | sort | head`

Expected: output includes `public/customer-ordering/assets/brand/bite-powered-logo.png` and `public/customer-ordering/assets/hopresso/hopresso-cover.png`.

### Task 3: Livewire Table Context

**Files:**
- Modify: `app/Livewire/GuestMenu.php`

- [ ] **Step 1: Add server-owned table label state**

Add a nullable `public ?string $tableLabel` and derive it in `mount()` from `request()->query('table')`, capped to a short safe label.

- [ ] **Step 2: Keep table context presentation-only**

Do not create a schema migration in this task. Existing checkout continues to create the order exactly as before; table context is visible in the guest UI and can be wired to persistence later if backend table support returns.

### Task 4: Blade Reskin

**Files:**
- Modify: `resources/views/livewire/guest-menu.blade.php`
- Modify: `resources/views/livewire/partials/guest-gate.blade.php`
- Modify: `resources/views/livewire/partials/guest-hero.blade.php`
- Modify: `resources/views/livewire/partials/guest-popular-rail.blade.php`

- [ ] **Step 1: Convert the outer shell**

Wrap the existing Livewire content with a prototype-style phone shell:

```blade
<div class="bite-ordering-stage">
    <div class="bite-phone-shell">
        <section class="bite-menu-screen">
            ...
        </section>
    </div>
</div>
```

- [ ] **Step 2: Preserve all existing `wire:*` behavior**

Keep `chooseLanguage`, `switchLanguage`, `createGroup`, `openProductSheet`, `addToCart`, `toggleReview`, cart quantity controls, contact fields, and `submitOrder` unchanged.

- [ ] **Step 3: Replace visible text and layout with prototype-aligned sections**

Render restaurant header, table context, language toggle, highlight/popular rail, search/category tabs, full menu rows, sticky cart bar, product detail sheet, cart review, checkout fields, and powered-by footer with prototype class names.

### Task 5: CSS Port

**Files:**
- Modify: `resources/css/app.css`
- Create or replace focused CSS modules under `resources/css/guest-*.css` if needed.

- [ ] **Step 1: Add prototype visual system**

Define CSS for `bite-ordering-stage`, `bite-phone-shell`, `bite-menu-screen`, language gate, hero, search, categories, product rows/cards, sticky cart, sheets, controls, and powered-by footer.

- [ ] **Step 2: Keep CSS responsive**

Mobile first at 390px phone-shell width, with desktop centering. Do not use Tailwind for new styles.

- [ ] **Step 3: Build assets**

Run: `npm run build`

Expected: Vite completes successfully and produces `public/build/manifest.json`.

### Task 6: Verification

**Files:**
- None unless failures require fixes.

- [ ] **Step 1: Run focused PHP tests**

Run:

```bash
php artisan test tests/Feature/GuestMenuPrototypeDesignTest.php tests/Feature/GuestMenuBrowseTest.php tests/Feature/GuestMenuHeroTest.php tests/Feature/GuestMenuBrandingTest.php tests/Feature/Livewire/GuestMenuLanguageGateTest.php tests/Feature/Livewire/GuestMenuProductSheetTest.php tests/Feature/GuestMenuCheckoutTest.php
```

Expected: PASS. If PHP is unavailable in the environment, record this as blocked and continue with build/browser verification.

- [ ] **Step 2: Run browser verification**

Run Laravel locally if PHP is available:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Open: `http://127.0.0.1:8000/menu/demo?table=12`

Expected: no console errors, no broken local assets, language gate works when session has no locale, menu renders with real seeded products, product sheet opens, cart review opens, and checkout fields are visible.

---

## Self-Review

- Spec coverage: The plan maps the downloaded prototype to the existing Laravel `/menu/{shop:slug}` route, keeps real Livewire behavior, includes table query display, and verifies design markers.
- Placeholder scan: No task uses TBD/TODO/fill-in language.
- Type consistency: `tableLabel`, `bite-phone-shell`, `bite-menu-screen`, and `bite-language-gate` are consistently named across tests and implementation tasks.
