<?php

namespace Tests\Feature;

use App\Livewire\GuestMenu;
use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Phase 6 (#26) — EN/AR + RTL parity across every guest screen.
 *
 * Each screen is loaded in the Arabic locale and asserted to: render in an RTL
 * layout, surface expected Arabic copy, and never leak a raw translation key
 * (the substring "guest." appearing in output means __() failed to resolve).
 * A separate test guards en/ar key parity so a missing key fails CI, not the UI.
 */
class GuestMenuBilingualParityTest extends TestCase
{
    use RefreshDatabase;

    private function seedShop(): Shop
    {
        $shop = Shop::create([
            'name' => 'Sourdough',
            'slug' => 'sourdough-'.Str::random(6),
            'branding' => [],
        ]);

        $coffee = Category::create([
            'shop_id' => $shop->id,
            'name_en' => 'Coffee',
            'name_ar' => 'قهوة',
            'sort_order' => 1,
        ]);

        $product = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $coffee->id,
            'name_en' => 'Flat White',
            'name_ar' => 'فلات وايت',
            'price' => 1.500,
            'is_available' => true,
            'is_visible' => true,
            'sort_order' => 1,
        ]);

        $group = ModifierGroup::create([
            'shop_id' => $shop->id,
            'name_en' => 'Milk',
            'name_ar' => 'الحليب',
            'min_selection' => 1,
            'max_selection' => 1,
        ]);
        ModifierOption::create([
            'modifier_group_id' => $group->id,
            'name_en' => 'Oat',
            'name_ar' => 'شوفان',
            'price_adjustment' => 0.200,
        ]);
        $product->modifierGroups()->attach($group->id);

        return $shop;
    }

    /**
     * No raw translation key should appear in rendered guest output. If __()
     * cannot resolve a key it echoes the key verbatim (e.g. "guest.subtotal").
     *
     * We scan for each real key from lang/en/guest.php rather than the bare
     * "guest." substring, because Livewire serializes the component alias
     * ("guest.order-tracker") into its wire:snapshot metadata — that is a
     * framework artifact, not a failed translation lookup.
     */
    private function assertNoRawTranslationKey(string $html): void
    {
        $keys = array_keys(require base_path('lang/en/guest.php'));

        foreach ($keys as $key) {
            $this->assertStringNotContainsString(
                'guest.'.$key,
                $html,
                "Raw translation key \"guest.{$key}\" leaked into the rendered HTML — a __() lookup failed."
            );
        }
    }

    public function test_menu_browse_and_hero_render_rtl_in_arabic(): void
    {
        $shop = $this->seedShop();

        $response = $this->withSession(['guest_locale' => 'ar'])
            ->get(route('guest.menu', $shop->slug));

        $response->assertOk();
        // Layout flips to RTL via the SetLocale-shared $direction.
        $response->assertSee('dir="rtl"', false);
        // Hero + browse Arabic copy.
        $response->assertSee(__('guest.status_open'), false);
        $response->assertSee(__('guest.dine_in'), false);
        $response->assertSee(__('guest.popular_today'), false);

        $fullMenuResponse = $this->withSession(['guest_locale' => 'ar'])
            ->get(route('guest.menu', ['shop' => $shop->slug, 'view' => 'menu']));

        $fullMenuResponse->assertOk();
        $fullMenuResponse->assertSee(__('guest.category_all'), false);
        // Localized category name (translated()).
        $fullMenuResponse->assertSee('قهوة', false);

        $this->assertNoRawTranslationKey($response->getContent());
        $this->assertNoRawTranslationKey($fullMenuResponse->getContent());
    }

    public function test_language_gate_renders_without_key_leak(): void
    {
        $shop = $this->seedShop();

        // No locale chosen yet → gate is shown. It is intentionally bilingual.
        $html = Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->assertSet('showLanguageGate', true)
            ->assertSeeHtml('guest-gate screen web-screen language-screen')
            ->assertSee('Choose your language')
            ->assertSee('اختر لغتك')
            ->html();

        $this->assertNoRawTranslationKey($html);
    }

    public function test_product_detail_sheet_renders_arabic_without_key_leak(): void
    {
        $shop = $this->seedShop();
        session(['guest_locale' => 'ar']);
        $this->app->setLocale('ar');

        $product = $shop->products()->first();

        $html = Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->assertSet('showModifierModal', true)
            ->assertSee(__('guest.required'))
            ->assertSee(__('guest.add_to_order'))
            ->assertSee(__('guest.item_note_label'))
            // Localized product + modifier names (translated()).
            ->assertSee('فلات وايت')
            ->assertSee('الحليب')
            ->assertSee('شوفان')
            ->html();

        $this->assertNoRawTranslationKey($html);
    }

    public function test_cart_and_checkout_render_arabic_without_key_leak(): void
    {
        $shop = $this->seedShop();
        session(['guest_locale' => 'ar']);
        $this->app->setLocale('ar');

        $product = $shop->products()->first();

        $component = Livewire::test(GuestMenu::class, ['shop' => $shop])
            // Product has modifiers → opens the sheet; add with the required choice.
            ->call('addToCart', $product->id)
            ->assertSet('showModifierModal', true);

        $group = $product->modifierGroups()->first();
        $option = $group->options()->first();

        $component->call('selectModifier', $group->id, $option->id, false)
            ->call('addToCart', $product->id)
            ->assertSet('showModifierModal', false)
            ->call('toggleReview')
            ->assertSet('showReviewModal', true);

        $html = $component
            ->assertSee(__('guest.your_order'))
            ->assertSee(__('guest.subtotal'))
            ->assertSee(__('guest.total'))
            ->assertSee(__('guest.confirm_your_order'))
            ->assertSee(__('guest.your_name'))
            ->assertSee(__('guest.pay_at_counter'))
            ->assertSee(__('guest.place_order'))
            ->html();

        $this->assertNoRawTranslationKey($html);
    }

    public function test_order_tracker_renders_rtl_in_arabic_without_key_leak(): void
    {
        $shop = $this->seedShop();

        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'preparing',
            'customer_name' => 'ليلى',
            'total_amount' => 1.700,
            'subtotal_amount' => 1.500,
            'tax_amount' => 0.200,
        ]);

        $response = $this->withSession(['guest_locale' => 'ar'])
            ->get(route('guest.track', $order->tracking_token));

        $response->assertOk();
        $response->assertSee('dir="rtl"', false);
        $response->assertSee(__('guest.track_received_title'), false);
        $response->assertSee(__('guest.track_received_body', ['shop' => $shop->name]), false);
        $response->assertSee(__('guest.track_step_received'), false);
        $response->assertSee(__('guest.track_step_preparing'), false);
        $response->assertSee(__('guest.track_simulate_next'), false);
        $response->assertSee(__('guest.rate_your_visit'), false);

        $this->assertNoRawTranslationKey($response->getContent());
    }

    public function test_en_and_ar_guest_keys_are_in_parity(): void
    {
        $en = require base_path('lang/en/guest.php');
        $ar = require base_path('lang/ar/guest.php');

        $missingInAr = array_diff(array_keys($en), array_keys($ar));
        $missingInEn = array_diff(array_keys($ar), array_keys($en));

        $this->assertSame(
            [],
            array_values($missingInAr),
            'Keys present in lang/en/guest.php but missing from lang/ar/guest.php.'
        );
        $this->assertSame(
            [],
            array_values($missingInEn),
            'Keys present in lang/ar/guest.php but missing from lang/en/guest.php.'
        );
    }
}
