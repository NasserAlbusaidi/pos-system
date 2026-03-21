<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuThemeRenderTest extends TestCase
{
    use RefreshDatabase;

    private function createShopWithProduct(array $branding = []): Shop
    {
        $shop = Shop::create([
            'name' => 'Theme Test Shop',
            'slug' => 'theme-test',
            'branding' => $branding,
        ]);
        $category = Category::create(['shop_id' => $shop->id, 'name_en' => 'Food']);
        Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Burger',
            'price' => 3.500,
            'is_available' => true,
            'is_visible' => true,
        ]);

        return $shop;
    }

    public function test_dark_theme_renders_data_theme_dark(): void
    {
        $shop = $this->createShopWithProduct(['theme' => 'dark']);
        $response = $this->get(route('guest.menu', $shop->slug));
        $response->assertStatus(200);
        $response->assertSee('data-theme="dark"', false);
    }

    public function test_modern_theme_renders_data_theme_modern(): void
    {
        $shop = $this->createShopWithProduct(['theme' => 'modern']);
        $response = $this->get(route('guest.menu', $shop->slug));
        $response->assertStatus(200);
        $response->assertSee('data-theme="modern"', false);
    }

    public function test_no_theme_defaults_to_warm(): void
    {
        $shop = $this->createShopWithProduct([]);
        $response = $this->get(route('guest.menu', $shop->slug));
        $response->assertStatus(200);
        $response->assertSee('data-theme="warm"', false);
    }

    public function test_invalid_theme_falls_back_to_warm(): void
    {
        $shop = $this->createShopWithProduct(['theme' => 'hacked']);
        $response = $this->get(route('guest.menu', $shop->slug));
        $response->assertStatus(200);
        $response->assertSee('data-theme="warm"', false);
    }

    public function test_theme_with_brand_colors_renders_both(): void
    {
        $shop = $this->createShopWithProduct([
            'theme' => 'dark',
            'paper' => '#1A1A1E',
            'ink' => '#F0EEE8',
            'accent' => '#C8A050',
        ]);
        $response = $this->get(route('guest.menu', $shop->slug));
        $response->assertStatus(200);
        $response->assertSee('data-theme="dark"', false);
        $response->assertSee('--paper:', false);
    }
}
