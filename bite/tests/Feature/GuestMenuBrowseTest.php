<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 2 (#22): Menu browse re-skin — popular rail, search, category tabs.
 * The skin is a re-render of the existing, server-validated GuestMenu flow.
 */
class GuestMenuBrowseTest extends TestCase
{
    use RefreshDatabase;

    private function seedShop(): Shop
    {
        $shop = Shop::create([
            'name' => 'Sourdough',
            'slug' => 'sourdough',
            'branding' => [],
        ]);

        $coffee = Category::create(['shop_id' => $shop->id, 'name_en' => 'Coffee', 'name_ar' => 'قهوة', 'sort_order' => 1]);
        $pastries = Category::create(['shop_id' => $shop->id, 'name_en' => 'Pastries', 'name_ar' => 'معجنات', 'sort_order' => 2]);

        Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $coffee->id,
            'name_en' => 'Flat White',
            'name_ar' => 'فلات وايت',
            'price' => 1.500,
            'is_available' => true,
            'is_visible' => true,
            'sort_order' => 1,
        ]);

        Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $pastries->id,
            'name_en' => 'Cinnamon Roll',
            'name_ar' => 'سينابون',
            'price' => 1.600,
            'discount_type' => 'fixed',
            'discount_value' => 0.400,
            'is_on_sale' => true,
            'is_available' => true,
            'is_visible' => true,
            'sort_order' => 1,
        ]);

        return $shop;
    }

    public function test_browse_renders_search_box(): void
    {
        $shop = $this->seedShop();

        $response = $this->get(route('guest.menu', ['shop' => $shop->slug, 'view' => 'menu']));

        $response->assertStatus(200);
        $response->assertSee('guest-search');
    }

    public function test_browse_renders_category_tabs_from_real_categories(): void
    {
        $shop = $this->seedShop();

        $response = $this->get(route('guest.menu', ['shop' => $shop->slug, 'view' => 'menu']));

        $response->assertStatus(200);
        $response->assertSee('guest-tabs');
        $response->assertSee('Coffee');
        $response->assertSee('Pastries');
        // "All" tab to clear the filter.
        $response->assertSee('All');
    }

    public function test_browse_renders_popular_rail_with_on_sale_item(): void
    {
        $shop = $this->seedShop();

        $response = $this->get(route('guest.menu', $shop->slug));

        $response->assertStatus(200);
        $response->assertSee('guest-popular');
        // On-sale items are surfaced first in the popular rail.
        $response->assertSee('Cinnamon Roll');
    }

    public function test_full_menu_products_use_the_popular_card_pattern(): void
    {
        $shop = $this->seedShop();

        $response = $this->get(route('guest.menu', ['shop' => $shop->slug, 'view' => 'menu']));

        $response->assertStatus(200);
        $response->assertSee('bite-menu-card-grid', false);
        $response->assertSee('product-card bite-popular-card bite-menu-card', false);
        $response->assertSee('product-open bite-popular-card__image', false);
        $response->assertDontSee('bite-menu-row__open', false);
    }

    public function test_browse_preserves_sold_out_state(): void
    {
        $shop = $this->seedShop();
        Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $shop->categories()->first()->id,
            'name_en' => 'Sold Loaf',
            'price' => 2.000,
            'is_available' => false,
            'is_visible' => true,
        ]);

        $response = $this->get(route('guest.menu', ['shop' => $shop->slug, 'view' => 'menu']));

        $response->assertStatus(200);
        $response->assertSee('Sold Out');
    }

    public function test_browse_tabs_localized_in_arabic(): void
    {
        $shop = $this->seedShop();

        $response = $this->withSession(['guest_locale' => 'ar'])
            ->get(route('guest.menu', ['shop' => $shop->slug, 'view' => 'menu']));

        $response->assertStatus(200);
        $response->assertSee('قهوة', false);
        $response->assertSee('معجنات', false);
    }
}
