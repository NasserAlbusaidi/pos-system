# Arabic UI / RTL Layout — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Full RTL support for guest and admin pages — Arabic-speaking users get a native right-to-left interface with translated UI strings.

**Architecture:** CSS logical properties for auto-directional flipping, a `SetLocale` middleware for consistent locale resolution, session-based per-user overrides with shop `branding['language']` as default. Super admin stays English.

**Tech Stack:** Laravel 12, Livewire 3, vanilla CSS with custom properties, PHP lang files for i18n.

**Design doc:** `docs/plans/2026-03-13-rtl-design.md`

---

## Task 1: SetLocale Middleware — Test

**Files:**
- Create: `tests/Feature/SetLocaleMiddlewareTest.php`

**Step 1: Write the test**

```php
<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetLocaleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_locale_defaults_to_shop_branding_language(): void
    {
        $shop = Shop::factory()->create([
            'branding' => ['language' => 'ar'],
        ]);
        $user = User::factory()->create(['shop_id' => $shop->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $this->assertEquals('ar', app()->getLocale());
    }

    public function test_admin_locale_defaults_to_english_when_no_branding(): void
    {
        $shop = Shop::factory()->create(['branding' => null]);
        $user = User::factory()->create(['shop_id' => $shop->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $this->assertEquals('en', app()->getLocale());
    }

    public function test_admin_session_override_takes_priority(): void
    {
        $shop = Shop::factory()->create([
            'branding' => ['language' => 'en'],
        ]);
        $user = User::factory()->create(['shop_id' => $shop->id]);

        $response = $this->actingAs($user)
            ->withSession(['admin_locale' => 'ar'])
            ->get(route('dashboard'));

        $response->assertOk();
        $this->assertEquals('ar', app()->getLocale());
    }

    public function test_guest_locale_from_session(): void
    {
        $shop = Shop::factory()->create([
            'branding' => ['language' => 'en'],
        ]);

        $response = $this->withSession(['guest_locale' => 'ar'])
            ->get(route('guest.menu', $shop->slug));

        $response->assertOk();
        $this->assertEquals('ar', app()->getLocale());
    }

    public function test_super_admin_always_english(): void
    {
        $shop = Shop::factory()->create([
            'branding' => ['language' => 'ar'],
        ]);
        $user = User::factory()->create([
            'shop_id' => $shop->id,
            'is_super_admin' => true,
        ]);

        $response = $this->actingAs($user)->get(route('super-admin.dashboard'));

        $response->assertOk();
        $this->assertEquals('en', app()->getLocale());
    }

    public function test_invalid_locale_falls_back_to_english(): void
    {
        $shop = Shop::factory()->create([
            'branding' => ['language' => 'fr'],
        ]);
        $user = User::factory()->create(['shop_id' => $shop->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $this->assertEquals('en', app()->getLocale());
    }
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=SetLocaleMiddlewareTest`
Expected: FAIL (middleware class not found)

---

## Task 2: SetLocale Middleware — Implementation

**Files:**
- Create: `app/Http/Middleware/SetLocale.php`
- Modify: `bootstrap/app.php` (line ~17, middleware aliases)

**Step 1: Create the middleware**

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const SUPPORTED_LOCALES = ['en', 'ar'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);
        App::setLocale($locale);

        $direction = $locale === 'ar' ? 'rtl' : 'ltr';
        View::share('direction', $direction);
        View::share('currentLocale', $locale);

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        // Super admin routes: always English
        if ($request->routeIs('super-admin.*')) {
            return 'en';
        }

        // Guest routes: session > shop branding > 'en'
        if ($request->routeIs('guest.*')) {
            $locale = session('guest_locale');
            if ($this->isValid($locale)) {
                return $locale;
            }

            $shop = $request->route('shop');
            if ($shop) {
                $branding = $shop->branding ?? [];
                $locale = $branding['language'] ?? null;
                if ($this->isValid($locale)) {
                    return $locale;
                }
            }

            return 'en';
        }

        // Authenticated admin routes: session > shop branding > 'en'
        $user = $request->user();
        if ($user) {
            $locale = session('admin_locale');
            if ($this->isValid($locale)) {
                return $locale;
            }

            $shop = $user->shop;
            if ($shop) {
                $branding = $shop->branding ?? [];
                $locale = $branding['language'] ?? null;
                if ($this->isValid($locale)) {
                    return $locale;
                }
            }
        }

        return 'en';
    }

    private function isValid(?string $locale): bool
    {
        return $locale !== null && in_array($locale, self::SUPPORTED_LOCALES);
    }
}
```

**Step 2: Register the middleware globally in `bootstrap/app.php`**

Find in `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
```

Add `SetLocale` to the global web middleware stack by appending to the web group:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\SetLocale::class,
    ]);
```

Keep the existing aliases unchanged below.

**Step 3: Run tests**

Run: `php artisan test --filter=SetLocaleMiddlewareTest`
Expected: All 6 tests pass

**Step 4: Commit**

```bash
git add app/Http/Middleware/SetLocale.php bootstrap/app.php tests/Feature/SetLocaleMiddlewareTest.php
git commit -m "feat: Add SetLocale middleware for consistent locale resolution"
```

---

## Task 3: CSS Logical Properties Migration

**Files:**
- Modify: `resources/css/app.css`

**Step 1: Migrate directional properties**

In `resources/css/app.css`, make these changes:

**Line 224** — Loading spinner border:
```css
/* Before */
border-right-color: transparent;
/* After */
border-inline-end-color: transparent;
```

**Lines 232-237** — Nav progress bar:
```css
/* Before */
.nav-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 2px;
    transform-origin: left;
}
/* After */
.nav-progress {
    position: absolute;
    bottom: 0;
    inset-inline-start: 0;
    inset-inline-end: 0;
    height: 2px;
    transform-origin: start;
}
```

**Step 2: Remove now-redundant `[dir="rtl"]` overrides**

Delete these lines (the directional overrides that logical properties make unnecessary):

```css
/* DELETE — lines 327-344 */
/* Flip the surface-card top accent line from left to right */
[dir="rtl"] .surface-card::before {
    background: linear-gradient(270deg, rgb(var(--crema) / 0.9), transparent 70%);
}

/* Flip nav-progress transform origin */
[dir="rtl"] .nav-progress {
    transform-origin: right;
}

/* Flip text alignment where explicitly set */
[dir="rtl"] .text-left {
    text-align: right;
}

[dir="rtl"] .text-right {
    text-align: left;
}
```

**Keep** the `[dir="rtl"]` rules for font-family and letter-spacing (lines 290-325) — those are typographic, not directional.

**Step 3: Fix the surface-card gradient with logical properties**

The `surface-card::before` gradient needs a different approach since CSS gradients don't support logical keywords. Keep the `[dir="rtl"]` override for this one case:

```css
/* Keep this — gradients can't use logical properties */
[dir="rtl"] .surface-card::before {
    background: linear-gradient(270deg, rgb(var(--crema) / 0.9), transparent 70%);
}
```

So only delete the nav-progress and text-alignment overrides (lines 332-344), not the surface-card one.

**Step 4: Verify the build**

Run: `npm run build`
Expected: Successful build, no errors

**Step 5: Commit**

```bash
git add resources/css/app.css
git commit -m "refactor: Migrate CSS to logical properties for RTL support"
```

---

## Task 4: Admin Translation Strings

**Files:**
- Create: `lang/en/admin.php`
- Create: `lang/ar/admin.php`

**Step 1: Create English admin language file**

```php
<?php

return [
    // Navigation
    'console' => 'Bite Console',
    'product_console' => 'Bite Product Console',
    'store_state' => 'Store State',
    'services_online' => 'Services Online',
    'exit' => 'Exit',
    'log_out' => 'Log Out',

    // Nav sections
    'operations' => 'Operations',
    'catalog' => 'Catalog',
    'administration' => 'Administration',

    // Nav links
    'dashboard' => 'Dashboard',
    'pos_register' => 'POS Register',
    'kitchen_display' => 'Kitchen Display',
    'reports' => 'Reports',
    'shift_report' => 'Shift Report',
    'audit_logs' => 'Audit Logs',
    'menu_builder' => 'Menu Builder',
    'product_catalog' => 'Product Catalog',
    'modifiers' => 'Modifiers',
    'guest_menu' => 'Guest Menu',
    'settings' => 'Settings',
    'billing' => 'Billing',

    // Dashboard
    'operations_dashboard' => 'Operations Dashboard',
    'store_pulse' => 'Store Pulse',
    'store_pulse_desc' => 'Revenue, throughput, and kitchen state update every 10 seconds.',
    'notifications' => 'Notifications',
    'clear_all' => 'Clear All',
    'no_notifications' => 'No notifications yet',
    'auto_refresh' => 'Auto Refresh',
    'live' => 'Live',
    'todays_revenue' => "Today's Revenue",
    'orders_today' => 'Orders Today',
    'active_orders' => 'Active Orders',
    'system_status' => 'System Status',
    'online' => 'Online',
    'items_sold_today' => 'Items Sold Today',
    'avg_order_value' => 'Average Order Value',

    // POS
    'front_counter_queue' => 'Front Counter Queue',
    'refresh_interval' => 'Refresh :seconds',
    'open_count' => ':count open',
    'order_number' => 'Order #:id',
    'print_receipt' => 'Print Receipt',
    'channel' => 'Channel',
    'guest_counter_order' => 'Guest Counter Order',
    'items' => 'Items',
    'more_items' => '+:count more',
    'paid' => 'Paid',
    'due' => 'Due',

    // Kitchen Display
    'back_of_house' => 'Back-of-House Flow',
    'kitchen_ticket' => 'Kitchen Ticket',
    'new' => 'New',
    'guest_order' => 'Guest Order',
    'start_preparing' => 'Start Preparing',
    'order_ready' => 'Order Ready',
    'awaiting_next_stage' => 'Awaiting Next Stage',
    'no_active_orders' => 'No Active Orders',
    'active_count' => ':count active',

    // Reports
    'export_orders' => 'Export completed orders',
    'export_csv' => 'Export CSV (:days days)',
    'revenue_days' => 'Revenue (:days days)',
    'orders_days' => 'Orders (:days days)',
    'revenue_by_day' => 'Revenue by Day',
    'orders_by_hour' => 'Orders by Hour',
    'top_products' => 'Top Products (:days days)',
    'product' => 'Product',
    'qty' => 'Qty',
    'revenue' => 'Revenue',
    'no_sales_yet' => 'No sales yet...',
    'payments' => 'Payments',
    'order_count' => ':count orders',
    'no_payments_yet' => 'No payments yet',

    // Language
    'language' => 'Language',
    'switch_arabic' => 'عربي',
    'switch_english' => 'EN',
];
```

**Step 2: Create Arabic admin language file**

```php
<?php

return [
    // Navigation
    'console' => 'وحدة التحكم',
    'product_console' => 'وحدة تحكم المنتجات',
    'store_state' => 'حالة المتجر',
    'services_online' => 'الخدمات متصلة',
    'exit' => 'خروج',
    'log_out' => 'تسجيل الخروج',

    // Nav sections
    'operations' => 'العمليات',
    'catalog' => 'القائمة',
    'administration' => 'الإدارة',

    // Nav links
    'dashboard' => 'لوحة التحكم',
    'pos_register' => 'نقطة البيع',
    'kitchen_display' => 'شاشة المطبخ',
    'reports' => 'التقارير',
    'shift_report' => 'تقرير الوردية',
    'audit_logs' => 'سجل المراجعة',
    'menu_builder' => 'إدارة القائمة',
    'product_catalog' => 'كتالوج المنتجات',
    'modifiers' => 'الإضافات',
    'guest_menu' => 'قائمة الضيوف',
    'settings' => 'الإعدادات',
    'billing' => 'الفواتير',

    // Dashboard
    'operations_dashboard' => 'لوحة العمليات',
    'store_pulse' => 'نبض المتجر',
    'store_pulse_desc' => 'الإيرادات والإنتاجية وحالة المطبخ تتحدث كل 10 ثوانٍ.',
    'notifications' => 'الإشعارات',
    'clear_all' => 'مسح الكل',
    'no_notifications' => 'لا توجد إشعارات',
    'auto_refresh' => 'تحديث تلقائي',
    'live' => 'مباشر',
    'todays_revenue' => 'إيرادات اليوم',
    'orders_today' => 'طلبات اليوم',
    'active_orders' => 'الطلبات النشطة',
    'system_status' => 'حالة النظام',
    'online' => 'متصل',
    'items_sold_today' => 'المنتجات المباعة اليوم',
    'avg_order_value' => 'متوسط قيمة الطلب',

    // POS
    'front_counter_queue' => 'قائمة انتظار الكاونتر',
    'refresh_interval' => 'تحديث :seconds',
    'open_count' => ':count مفتوح',
    'order_number' => 'طلب #:id',
    'print_receipt' => 'طباعة الإيصال',
    'channel' => 'القناة',
    'guest_counter_order' => 'طلب كاونتر الضيوف',
    'items' => 'المنتجات',
    'more_items' => '+:count إضافي',
    'paid' => 'مدفوع',
    'due' => 'مستحق',

    // Kitchen Display
    'back_of_house' => 'سير عمل المطبخ',
    'kitchen_ticket' => 'تذكرة المطبخ',
    'new' => 'جديد',
    'guest_order' => 'طلب ضيف',
    'start_preparing' => 'بدء التحضير',
    'order_ready' => 'الطلب جاهز',
    'awaiting_next_stage' => 'بانتظار المرحلة التالية',
    'no_active_orders' => 'لا توجد طلبات نشطة',
    'active_count' => ':count نشط',

    // Reports
    'export_orders' => 'تصدير الطلبات المكتملة',
    'export_csv' => 'تصدير CSV (:days يوم)',
    'revenue_days' => 'الإيرادات (:days يوم)',
    'orders_days' => 'الطلبات (:days يوم)',
    'revenue_by_day' => 'الإيرادات حسب اليوم',
    'orders_by_hour' => 'الطلبات حسب الساعة',
    'top_products' => 'أفضل المنتجات (:days يوم)',
    'product' => 'المنتج',
    'qty' => 'الكمية',
    'revenue' => 'الإيرادات',
    'no_sales_yet' => 'لا توجد مبيعات بعد...',
    'payments' => 'المدفوعات',
    'order_count' => ':count طلبات',
    'no_payments_yet' => 'لا توجد مدفوعات بعد',

    // Language
    'language' => 'اللغة',
    'switch_arabic' => 'عربي',
    'switch_english' => 'EN',
];
```

**Step 3: Commit**

```bash
git add lang/en/admin.php lang/ar/admin.php
git commit -m "feat: Add admin translation strings (en/ar) for RTL support"
```

---

## Task 5: Admin Navigation — Language Toggle + String Migration

**Files:**
- Modify: `app/Livewire/Layout/AdminNavigation.php`
- Modify: `resources/views/livewire/layout/admin-navigation.blade.php`

**Step 1: Add `switchLocale` method to AdminNavigation.php**

Read the existing file first. Add this method:

```php
public function switchLocale(string $locale): void
{
    $locale = in_array($locale, ['en', 'ar']) ? $locale : 'en';
    session()->put('admin_locale', $locale);

    // Full page reload — dir attribute is on <html>
    $this->redirect(request()->header('Referer', route('dashboard')), navigate: false);
}
```

**Step 2: Update the Blade view**

Replace all hardcoded English strings with `{{ __('admin.key') }}` calls. Key replacements in the desktop sidebar:

| Hardcoded | Replacement |
|-----------|------------|
| `Bite Product Console` | `{{ __('admin.product_console') }}` |
| `Store State` | `{{ __('admin.store_state') }}` |
| `Services Online` | `{{ __('admin.services_online') }}` |
| `Operations` | `{{ __('admin.operations') }}` |
| `Dashboard` | `{{ __('admin.dashboard') }}` |
| `POS Register` | `{{ __('admin.pos_register') }}` |
| `Kitchen Display` | `{{ __('admin.kitchen_display') }}` |
| `Reports` | `{{ __('admin.reports') }}` |
| `Shift Report` | `{{ __('admin.shift_report') }}` |
| `Audit Logs` | `{{ __('admin.audit_logs') }}` |
| `Catalog` | `{{ __('admin.catalog') }}` |
| `Menu Builder` | `{{ __('admin.menu_builder') }}` |
| `Product Catalog` | `{{ __('admin.product_catalog') }}` |
| `Modifiers` | `{{ __('admin.modifiers') }}` |
| `Guest Menu` | `{{ __('admin.guest_menu') }}` |
| `Administration` | `{{ __('admin.administration') }}` |
| `Settings` | `{{ __('admin.settings') }}` |
| `Billing` | `{{ __('admin.billing') }}` |
| `Log Out` | `{{ __('admin.log_out') }}` |
| `Exit` | `{{ __('admin.exit') }}` |

Also replace in the **mobile nav** (same strings used there).

**Step 3: Add language toggle to desktop sidebar**

Add before the logout button in the footer section (inside `<div class="border-t border-panel/10 p-4">`), after the user info card:

```blade
{{-- Language Toggle --}}
<div class="mb-3 flex items-center justify-center gap-0.5 rounded-full border border-panel/15 bg-panel/10 p-0.5">
    <button wire:click="switchLocale('en')" class="lang-toggle {{ app()->getLocale() === 'en' ? 'lang-toggle-active' : '' }}" type="button">
        EN
    </button>
    <button wire:click="switchLocale('ar')" class="lang-toggle {{ app()->getLocale() === 'ar' ? 'lang-toggle-active' : '' }}" type="button">
        عربي
    </button>
</div>
```

**Step 4: Add language toggle to mobile nav**

Add a similar toggle in the mobile header bar (inside the flex container alongside the shop name), keeping it compact.

**Step 5: Verify visually**

Run: `php artisan serve`
Navigate to dashboard, verify:
- Toggle appears at bottom of sidebar
- Clicking "عربي" reloads page with RTL layout
- All nav labels show Arabic text
- Clicking "EN" reverts to English

**Step 6: Commit**

```bash
git add app/Livewire/Layout/AdminNavigation.php resources/views/livewire/layout/admin-navigation.blade.php
git commit -m "feat: Add admin sidebar language toggle and translate nav strings"
```

---

## Task 6: Shop Dashboard — String Migration

**Files:**
- Modify: `resources/views/livewire/shop-dashboard.blade.php`

**Step 1: Replace hardcoded strings**

| Hardcoded | Replacement |
|-----------|------------|
| `Operations Dashboard` | `{{ __('admin.operations_dashboard') }}` |
| `Store Pulse` | `{{ __('admin.store_pulse') }}` |
| `Revenue, throughput...` | `{{ __('admin.store_pulse_desc') }}` |
| `Notifications` | `{{ __('admin.notifications') }}` |
| `Clear All` | `{{ __('admin.clear_all') }}` |
| `No notifications yet` | `{{ __('admin.no_notifications') }}` |
| `Auto Refresh` | `{{ __('admin.auto_refresh') }}` |
| `Live` | `{{ __('admin.live') }}` |
| `Today's Revenue` | `{{ __('admin.todays_revenue') }}` |
| `Orders Today` | `{{ __('admin.orders_today') }}` |
| `Active Orders` | `{{ __('admin.active_orders') }}` |
| `System Status` | `{{ __('admin.system_status') }}` |
| `Online` | `{{ __('admin.online') }}` |
| `Items Sold Today` | `{{ __('admin.items_sold_today') }}` |
| `Average Order Value` | `{{ __('admin.avg_order_value') }}` |

**Step 2: Verify**

Run: `php artisan serve`
Navigate to dashboard with Arabic locale. Verify all labels are Arabic.

**Step 3: Commit**

```bash
git add resources/views/livewire/shop-dashboard.blade.php
git commit -m "feat: Translate shop dashboard strings for RTL support"
```

---

## Task 7: POS Dashboard — String Migration

**Files:**
- Modify: `resources/views/livewire/pos-dashboard.blade.php`

**Step 1: Replace hardcoded strings**

| Hardcoded | Replacement |
|-----------|------------|
| `POS Register` (header) | `{{ __('admin.pos_register') }}` |
| `Front Counter Queue` | `{{ __('admin.front_counter_queue') }}` |
| `Refresh 5s` | `{{ __('admin.refresh_interval', ['seconds' => '5s']) }}` |
| `X open` | `{{ __('admin.open_count', ['count' => count($orders)]) }}` |
| `Order #X` | `{{ __('admin.order_number', ['id' => $order->id]) }}` |
| `Print Receipt` (title attr) | `{{ __('admin.print_receipt') }}` |
| `Channel` | `{{ __('admin.channel') }}` |
| `Guest Counter Order` | `{{ __('admin.guest_counter_order') }}` |
| `Items` | `{{ __('admin.items') }}` |
| `+X more` | `{{ __('admin.more_items', ['count' => $order->items->count() - 3]) }}` |
| `Paid` | `{{ __('admin.paid') }}` |
| `Due` | `{{ __('admin.due') }}` |

Also update `$item->product_name_snapshot_en` to use `$item->translated('product_name_snapshot')` so the correct locale name shows.

**Step 2: Commit**

```bash
git add resources/views/livewire/pos-dashboard.blade.php
git commit -m "feat: Translate POS dashboard strings for RTL support"
```

---

## Task 8: Kitchen Display — String Migration

**Files:**
- Modify: `resources/views/livewire/kitchen-display.blade.php`

**Step 1: Replace hardcoded strings**

| Hardcoded | Replacement |
|-----------|------------|
| `Kitchen Display` (header) | `{{ __('admin.kitchen_display') }}` |
| `Back-of-House Flow` | `{{ __('admin.back_of_house') }}` |
| `Live` | `{{ __('admin.live') }}` |
| `X active` | `{{ __('admin.active_count', ['count' => count($orders)]) }}` |
| `Order #X` | `{{ __('admin.order_number', ['id' => $order->id]) }}` |
| `Kitchen Ticket` | `{{ __('admin.kitchen_ticket') }}` |
| `New` | `{{ __('admin.new') }}` |
| `Guest Order` | `{{ __('admin.guest_order') }}` |
| `Start Preparing` | `{{ __('admin.start_preparing') }}` |
| `Order Ready` | `{{ __('admin.order_ready') }}` |
| `Awaiting Next Stage` | `{{ __('admin.awaiting_next_stage') }}` |
| `No Active Orders` | `{{ __('admin.no_active_orders') }}` |

Also update `$item->product_name_snapshot_en` to `$item->translated('product_name_snapshot')`.

**Step 2: Commit**

```bash
git add resources/views/livewire/kitchen-display.blade.php
git commit -m "feat: Translate kitchen display strings for RTL support"
```

---

## Task 9: Reports Dashboard — String Migration

**Files:**
- Modify: `resources/views/livewire/admin/reports-dashboard.blade.php`

**Step 1: Replace hardcoded strings**

| Hardcoded | Replacement |
|-----------|------------|
| `Reports` (header) | `{{ __('admin.reports') }}` |
| `Export completed orders` | `{{ __('admin.export_orders') }}` |
| `Export CSV (30 days)` | `{{ __('admin.export_csv', ['days' => 30]) }}` |
| `Revenue (X days)` | `{{ __('admin.revenue_days', ['days' => $rangeDays]) }}` |
| `Orders (X days)` | `{{ __('admin.orders_days', ['days' => $rangeDays]) }}` |
| `Avg Order Value` | `{{ __('admin.avg_order_value') }}` |
| `Revenue by Day` | `{{ __('admin.revenue_by_day') }}` |
| `Orders by Hour` | `{{ __('admin.orders_by_hour') }}` |
| `Top Products (X days)` | `{{ __('admin.top_products', ['days' => $rangeDays]) }}` |
| `Product` | `{{ __('admin.product') }}` |
| `Qty` | `{{ __('admin.qty') }}` |
| `Revenue` | `{{ __('admin.revenue') }}` |
| `No sales yet...` | `{{ __('admin.no_sales_yet') }}` |
| `Payments` | `{{ __('admin.payments') }}` |
| `X orders` | `{{ __('admin.order_count', ['count' => $row->orders]) }}` |
| `No payments yet` | `{{ __('admin.no_payments_yet') }}` |

Also update `$product->product_name_snapshot_en` to `$product->translated('product_name_snapshot')`.

**Step 2: Commit**

```bash
git add resources/views/livewire/admin/reports-dashboard.blade.php
git commit -m "feat: Translate reports dashboard strings for RTL support"
```

---

## Task 10: Inline Style Fixes

**Files:**
- Modify: `resources/views/welcome.blade.php` (lines 1485-1486, 1586, 1723)
- Modify: `resources/views/invoices/order.blade.php` (line 25)

**Step 1: Fix welcome.blade.php decorative blobs**

Replace inline `left:` and `right:` with logical equivalents:

```html
<!-- Line 1485: left: -100px → inset-inline-start: -100px -->
<div class="blob blob--crema" style="width: 300px; height: 300px; top: -80px; inset-inline-start: -100px; position: absolute;"></div>

<!-- Line 1486: right: -80px → inset-inline-end: -80px -->
<div class="blob blob--signal" style="width: 250px; height: 250px; bottom: -60px; inset-inline-end: -80px; position: absolute;"></div>

<!-- Line 1586: left: -150px → inset-inline-start: -150px -->
<div class="..." style="width: 350px; height: 350px; top: 20%; inset-inline-start: -150px; position: absolute;"></div>

<!-- Line 1723: right: -100px → inset-inline-end: -100px -->
<div class="..." style="width: 300px; height: 300px; bottom: -100px; inset-inline-end: -100px; position: absolute;"></div>
```

**Step 2: Fix invoice text alignment**

In `resources/views/invoices/order.blade.php`, line ~10 in the `<style>` block:

```css
/* Before */
th, td { border-bottom: 1px solid #D1D1CB; padding: 8px 0; text-align: left; }
/* After */
th, td { border-bottom: 1px solid #D1D1CB; padding: 8px 0; text-align: start; }
```

**Step 3: Commit**

```bash
git add resources/views/welcome.blade.php resources/views/invoices/order.blade.php
git commit -m "fix: Replace directional inline styles with logical properties for RTL"
```

---

## Task 11: Simplify Layout Locale Logic

**Files:**
- Modify: `resources/views/layouts/app.blade.php` (lines 2-8)
- Modify: `resources/views/layouts/admin.blade.php` (lines 2-6)
- Modify: `resources/views/layouts/guest.blade.php` (line 2)
- Modify: `resources/views/components/layouts/app.blade.php` (line 2)

Now that `SetLocale` middleware shares `$direction` and `$currentLocale` to all views, simplify the layouts.

**Step 1: Simplify `layouts/app.blade.php`**

```php
<!-- Before (lines 2-8) -->
@php
    $shopLang = 'en';
    if (isset($shop)) {
        $shopLang = session('guest_locale', $shop->branding['language'] ?? 'en');
    }
    $direction = $shopLang === 'ar' ? 'rtl' : 'ltr';
@endphp
<html lang="{{ $shopLang }}" dir="{{ $direction }}">

<!-- After -->
<html lang="{{ $currentLocale ?? app()->getLocale() }}" dir="{{ $direction ?? 'ltr' }}">
```

**Step 2: Simplify `layouts/admin.blade.php`**

```php
<!-- Before (lines 2-6) -->
@php
    $shop = Illuminate\Support\Facades\Auth::user()?->shop;
    $adminLang = $shop ? ($shop->branding['language'] ?? 'en') : 'en';
    $adminDir = $adminLang === 'ar' ? 'rtl' : 'ltr';
@endphp
<html lang="{{ $adminLang }}" dir="{{ $adminDir }}" class="h-full">

<!-- After -->
<html lang="{{ $currentLocale ?? 'en' }}" dir="{{ $direction ?? 'ltr' }}" class="h-full">
```

**Step 3: Simplify `layouts/guest.blade.php`**

```php
<!-- Before -->
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<!-- After -->
<html lang="{{ $currentLocale ?? app()->getLocale() }}" dir="{{ $direction ?? 'ltr' }}">
```

**Step 4: Fix `components/layouts/app.blade.php`** (missing `dir` attribute)

```php
<!-- Before -->
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<!-- After -->
<html lang="{{ $currentLocale ?? app()->getLocale() }}" dir="{{ $direction ?? 'ltr' }}">
```

**Step 5: Remove ad-hoc locale setting from Livewire components**

In `GuestMenu.php`, remove `App::setLocale()` calls from `mount()` and `render()` — the middleware handles this now. Keep `switchLanguage()` method (it still writes to session and needs to set locale for the current request).

In `OrderTracker.php`, remove `App::setLocale()` calls from `mount()` and `render()`.

**Step 6: Run existing tests**

Run: `php artisan test`
Expected: All tests pass

**Step 7: Commit**

```bash
git add resources/views/layouts/ resources/views/components/layouts/ app/Livewire/GuestMenu.php app/Livewire/Guest/OrderTracker.php
git commit -m "refactor: Simplify layout locale logic — middleware handles it now"
```

---

## Task 12: Remaining Admin Views — String Migration

**Files:**
- Modify: `resources/views/livewire/admin/audit-logs.blade.php`
- Modify: `resources/views/livewire/admin/shift-report.blade.php`
- Modify: `resources/views/livewire/shop-settings.blade.php`
- Modify: `resources/views/livewire/billing-settings.blade.php`
- Modify: `resources/views/livewire/menu-builder.blade.php`
- Modify: `resources/views/livewire/product-manager.blade.php`
- Modify: `resources/views/livewire/modifier-manager.blade.php`
- Modify: `resources/views/livewire/onboarding-wizard.blade.php`

**Step 1: Read each file and identify hardcoded English strings**

For each file, replace hardcoded strings with `{{ __('admin.key') }}` or `{{ __('common.key') }}` calls. Add any new keys needed to `lang/en/admin.php` and `lang/ar/admin.php`.

This task is large — break it into sub-batches:

**Batch A:** `audit-logs`, `shift-report` (smaller views)
**Batch B:** `shop-settings`, `billing-settings` (settings pages)
**Batch C:** `menu-builder`, `product-manager`, `modifier-manager` (catalog pages)
**Batch D:** `onboarding-wizard` (onboarding flow)

For each batch:
1. Read the Blade view
2. Identify all hardcoded English strings
3. Add missing keys to both `lang/en/admin.php` and `lang/ar/admin.php`
4. Replace hardcoded strings with `__()` calls
5. Verify the page renders correctly

**Step 2: Commit after each batch**

```bash
# Batch A
git commit -m "feat: Translate audit logs and shift report for RTL"

# Batch B
git commit -m "feat: Translate settings pages for RTL"

# Batch C
git commit -m "feat: Translate catalog pages for RTL"

# Batch D
git commit -m "feat: Translate onboarding wizard for RTL"
```

---

## Task 13: Final Verification

**Step 1: Run all tests**

```bash
php artisan test
```

Expected: All existing tests pass.

**Step 2: Run the middleware tests**

```bash
php artisan test --filter=SetLocaleMiddlewareTest
```

Expected: All 6 tests pass.

**Step 3: Manual smoke test**

1. Start dev server: `php artisan serve`
2. Login as admin (`admin@bite.com` / `password`)
3. Toggle to Arabic in sidebar → verify RTL layout, Arabic labels
4. Toggle back to English → verify LTR restored
5. Visit guest menu (`/menu/demo`) → toggle to Arabic → verify RTL
6. Visit order tracker → verify it inherits guest locale
7. Check POS, KDS, Reports, Settings — all show Arabic when toggled
8. Login as super admin (`super@bite.com` / `password`) → verify always English

**Step 4: Build frontend**

```bash
npm run build
```

Expected: No errors.

**Step 5: Final commit if any fixes needed**

```bash
git commit -m "fix: RTL polish from smoke testing"
```
