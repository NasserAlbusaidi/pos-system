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
            'slug' => 'prototype-livewire',
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
        $response->assertSee('See all');
        $response->assertSee('Powered by');
        $response->assertSee('<span lang="en" dir="ltr">The Nitro Bar</span>', false);
        $response->assertSee('lang="en"', false);
        $response->assertSee('dir="ltr"', false);
        $response->assertSee('EN');
    }

    public function test_guest_menu_home_markup_matches_vercel_prototype_structure(): void
    {
        $shop = $this->seedShop();

        $response = $this->withSession(['guest_locale' => 'en'])
            ->get(route('guest.menu', ['shop' => $shop->slug, 'table' => '12']));

        $response->assertOk();
        $response->assertSee('web-screen home-screen', false);
        $response->assertSee('web-hero', false);
        $response->assertSee('web-hero-bg', false);
        $response->assertSee('web-hero-top', false);
        $response->assertSee('web-hero-copy', false);
        $response->assertSee('web-main', false);
        $response->assertSee('web-context-strip', false);
        $response->assertSee('offer-card highlight-card', false);
        $response->assertSee('highlight-kicker', false);
        $response->assertSee('recommended-section', false);
        $response->assertSee('product-grid web-product-grid', false);
        $response->assertSee('product-card', false);
        $response->assertSee('powered-by-bite', false);
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

    public function test_prototype_css_uses_bai_for_english_and_ge_dinar_for_arabic(): void
    {
        $css = file_get_contents(resource_path('css/guest-prototype-shell.css'));

        $this->assertFileExists(resource_path('fonts/customer-ordering/BaiJamjuree-Regular.woff2'));
        $this->assertFileExists(resource_path('fonts/customer-ordering/BaiJamjuree-Medium.woff2'));
        $this->assertFileExists(resource_path('fonts/customer-ordering/BaiJamjuree-SemiBold.woff2'));
        $this->assertFileExists(resource_path('fonts/customer-ordering/BaiJamjuree-Bold.woff2'));
        $this->assertFileExists(resource_path('fonts/customer-ordering/GE-Dinar-One-Light.woff2'));
        $this->assertFileExists(resource_path('fonts/customer-ordering/GE-Dinar-One-Medium.ttf'));
        $this->assertFileExists(resource_path('fonts/customer-ordering/GE-Dinar-One-Bold.otf'));
        $this->assertStringNotContainsString('fonts.googleapis.com', $css);
        $this->assertStringNotContainsString('fonts.gstatic.com', $css);
        $this->assertStringContainsString('../fonts/customer-ordering/BaiJamjuree-Regular.woff2', $css);
        $this->assertStringContainsString('../fonts/customer-ordering/GE-Dinar-One-Light.woff2', $css);
        $this->assertStringContainsString('../fonts/customer-ordering/GE-Dinar-One-Medium.ttf', $css);
        $this->assertStringContainsString('../fonts/customer-ordering/GE-Dinar-One-Bold.otf', $css);
        $this->assertStringContainsString("font-weight: 300 400;", $css);
        $this->assertStringContainsString("font-weight: 500 600;", $css);
        $this->assertStringContainsString("font-weight: 700 900;", $css);
        $this->assertStringNotContainsString('/customer-ordering/assets/fonts/BaiJamjuree-Regular.woff2', $css);
        $this->assertStringContainsString("font-family: 'GE Dinar One';", $css);
        $this->assertStringContainsString("font-family: 'Bai Jamjuree', ui-sans-serif", $css);
        $this->assertStringContainsString('body:has(.bite-ordering-stage)', $css);
        $this->assertStringContainsString('.bite-ordering-stage :where(*)', $css);
        $this->assertStringContainsString("[dir=\"rtl\"] .bite-ordering-stage", $css);
        $this->assertStringContainsString('[dir="rtl"] .bite-ordering-stage :where(*)', $css);
        $this->assertStringContainsString('.bite-ordering-stage :lang(en)', $css);
        $this->assertStringContainsString('.bite-ordering-stage :where([lang="en"], [dir="ltr"])', $css);
        $this->assertStringContainsString('.bite-ordering-stage :lang(ar)', $css);
        $this->assertStringContainsString("font-family: 'GE Dinar One', 'Bai Jamjuree'", $css);
        $this->assertStringNotContainsString("font-family: 'Bai Jamjuree', 'GE Dinar One'", $css);
        $this->assertStringNotContainsString('Thmanyah Sans', $css);
    }

    public function test_prototype_buttons_and_icons_use_bite_green_accent(): void
    {
        $css = file_get_contents(resource_path('css/guest-prototype-shell.css'));
        $menuCss = file_get_contents(resource_path('css/guest-prototype-menu.css'));
        $sheetCss = file_get_contents(resource_path('css/guest-prototype-sheets.css'));

        $this->assertStringContainsString('--bite-primary: #006334;', $css);
        $this->assertStringContainsString('--bite-secondary: #006334;', $css);
        $this->assertStringContainsString('--primary-500: #006334;', $css);
        $this->assertStringContainsString('--bite-accent: #006334;', $css);
        $this->assertStringNotContainsString('#006836', $css);
        $this->assertStringNotContainsString('#0b6b2e', $css);
        $this->assertStringNotContainsString('#004f2c', $menuCss);
        $this->assertStringNotContainsString('rgba(0, 66, 37', $menuCss);
        $this->assertStringNotContainsString('rgba(0, 79, 43', $sheetCss);
    }

    public function test_checkout_voucher_input_has_distinct_white_field_surface(): void
    {
        $css = file_get_contents(resource_path('css/guest-prototype-sheets.css'));

        $this->assertSame(1, preg_match('/\.bite-voucher-field button\s*\{(?<block>[^}]*)\}/', $css, $buttonMatches));
        $this->assertStringContainsString('align-self: end;', $buttonMatches['block']);
        $this->assertStringContainsString('height: 42px;', $buttonMatches['block']);

        $this->assertSame(1, preg_match('/\.bite-voucher-field input\s*\{(?<block>[^}]*)\}/', $css, $inputMatches));
        $this->assertStringContainsString('background: #fff;', $inputMatches['block']);
        $this->assertStringContainsString('border: 1px solid rgba(0, 99, 52, 0.14);', $inputMatches['block']);
        $this->assertStringContainsString('border-radius: 8px;', $inputMatches['block']);
        $this->assertStringContainsString('padding: 0 12px;', $inputMatches['block']);

        $this->assertSame(1, preg_match('/\.bite-voucher-field input:focus\s*\{(?<block>[^}]*)\}/', $css, $focusMatches));
        $this->assertStringContainsString('border-color: rgba(0, 99, 52, 0.34);', $focusMatches['block']);
        $this->assertStringContainsString('box-shadow: 0 0 0 3px rgba(0, 99, 52, 0.10);', $focusMatches['block']);
    }

    public function test_checkout_payment_picker_matches_custom_dropdown_reference(): void
    {
        $css = file_get_contents(resource_path('css/guest-prototype-sheets.css'));

        $this->assertStringNotContainsString('.bite-payment-select select', $css);

        $this->assertSame(1, preg_match('/\.bite-payment-picker\s*\{(?<block>[^}]*)\}/', $css, $pickerMatches));
        $this->assertStringContainsString('display: grid;', $pickerMatches['block']);
        $this->assertStringContainsString('gap: 12px;', $pickerMatches['block']);

        $this->assertSame(1, preg_match('/\.bite-payment-picker__summary\s*\{(?<block>[^}]*)\}/', $css, $summaryMatches));
        $this->assertStringContainsString('min-height: 86px;', $summaryMatches['block']);
        $this->assertStringContainsString('border-radius: 8px;', $summaryMatches['block']);
        $this->assertStringContainsString('background: #f1f3ef;', $summaryMatches['block']);

        $this->assertSame(1, preg_match('/\.bite-payment-picker__options\s*\{(?<block>[^}]*)\}/', $css, $optionsMatches));
        $this->assertStringContainsString('border: 1px solid rgba(111, 126, 72, 0.32);', $optionsMatches['block']);
        $this->assertStringContainsString('border-radius: 8px;', $optionsMatches['block']);
        $this->assertStringContainsString('background: #fff;', $optionsMatches['block']);

        $this->assertSame(1, preg_match('/\.bite-payment-picker__option\.is-selected\s*\{(?<block>[^}]*)\}/', $css, $selectedMatches));
        $this->assertStringContainsString('background: #f1f3ef;', $selectedMatches['block']);
        $this->assertStringContainsString('color: var(--bite-secondary);', $selectedMatches['block']);
    }

    public function test_popular_card_badge_css_does_not_capture_price_spans(): void
    {
        $css = file_get_contents(resource_path('css/guest-prototype-menu.css'));
        $partial = file_get_contents(resource_path('views/livewire/partials/guest-popular-rail.blade.php'));

        $this->assertStringContainsString('bite-popular-card__badge', $partial);
        $this->assertStringNotContainsString('.bite-popular-card__image span {', $css);
        $this->assertStringContainsString('.bite-popular-card__image .bite-popular-card__badge', $css);
        $this->assertStringContainsString('.product-open.bite-popular-card__image strong .price-display', $css);
    }

    public function test_full_menu_search_uses_a_single_clean_control_frame(): void
    {
        $css = file_get_contents(resource_path('css/guest-prototype-menu.css'));

        $this->assertStringContainsString('.bite-search {', $css);
        $this->assertStringContainsString('height: 48px;', $css);
        $this->assertStringContainsString('border-radius: 16px;', $css);
        $this->assertStringContainsString('box-shadow: 0 10px 24px rgba(16, 26, 20, 0.08);', $css);
        $this->assertStringContainsString('.bite-search input {', $css);
        $this->assertStringContainsString('height: 100%;', $css);
        $this->assertStringContainsString('border: 0;', $css);
        $this->assertStringContainsString('background: transparent;', $css);
    }

    public function test_review_order_button_is_a_floating_cart_action(): void
    {
        $css = file_get_contents(resource_path('css/guest-prototype-menu.css'));

        $this->assertStringContainsString('.screen.bite-menu-screen:has(.bite-cart-bar) .bite-menu-main', $css);
        $this->assertStringContainsString('padding-bottom: calc(150px + env(safe-area-inset-bottom));', $css);
        $this->assertStringContainsString('.bite-cart-bar {', $css);
        $this->assertStringContainsString('position: fixed;', $css);
        $this->assertStringContainsString('left: 50%;', $css);
        $this->assertStringContainsString('bottom: max(18px, env(safe-area-inset-bottom));', $css);
        $this->assertStringContainsString('width: min(calc(100vw - 28px), calc(var(--screen-width) - 28px));', $css);
        $this->assertStringContainsString('transform: translateX(-50%);', $css);
        $this->assertStringContainsString('border-radius: 18px;', $css);
    }

    public function test_full_menu_powered_by_footer_uses_minimal_empty_space(): void
    {
        $css = file_get_contents(resource_path('css/guest-prototype-menu.css'));

        $this->assertStringContainsString('.bite-full-menu-screen.screen.bite-menu-screen.web-screen', $css);
        $this->assertStringContainsString('padding-bottom: max(12px, env(safe-area-inset-bottom));', $css);
        $this->assertStringContainsString('.bite-full-menu-screen .web-main.bite-menu-main', $css);
        $this->assertStringContainsString('padding-bottom: 16px;', $css);
        $this->assertStringContainsString('.powered-by-bite.bite-powered.bite-powered--page', $css);
        $this->assertStringContainsString('margin: 8px auto 0;', $css);
        $this->assertStringContainsString('padding: 4px 16px 8px;', $css);
    }

    public function test_home_menu_powered_by_footer_uses_minimal_empty_space(): void
    {
        $css = file_get_contents(resource_path('css/guest-prototype-menu.css'));

        $this->assertStringContainsString('.home-screen.screen.bite-menu-screen.web-screen', $css);
        $this->assertStringContainsString('padding-bottom: max(12px, env(safe-area-inset-bottom));', $css);
        $this->assertStringContainsString('.home-screen .web-main.bite-menu-main', $css);
        $this->assertStringContainsString('padding-bottom: 16px;', $css);
    }

    public function test_product_card_price_and_add_button_share_a_clear_action_row(): void
    {
        $css = file_get_contents(resource_path('css/guest-prototype-menu.css'));

        $this->assertStringContainsString('padding: 0 0 52px;', $css);
        $this->assertStringContainsString('margin-inline-end: 60px;', $css);
        $this->assertStringContainsString('.product-open.bite-popular-card__image strong {', $css);
        $this->assertStringContainsString('bottom: 7px;', $css);
        $this->assertStringContainsString('line-height: 31px;', $css);
        $this->assertMatchesRegularExpression(
            '/\.product-open\.bite-popular-card__image strong \.price-display \{(?<block>[^}]*)\}/',
            $css,
        );
        preg_match('/\.product-open\.bite-popular-card__image strong \.price-display \{(?<block>[^}]*)\}/', $css, $matches);
        $this->assertStringContainsString('height: 31px;', $matches['block']);
        $this->assertStringContainsString('align-items: center;', $matches['block']);
        $this->assertStringContainsString('.mini-plus.bite-add-mini {', $css);
    }
}
