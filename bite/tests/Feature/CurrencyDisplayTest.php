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
        Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name' => 'Karak',
            'price' => 0.50,
        ]);

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->assertSee('OMR 0.500')
            ->assertDontSee('$ 0');
    }

    public function test_guest_menu_renders_custom_currency_symbol(): void
    {
        $shop = Shop::create([
            'name' => 'Test Cafe',
            'slug' => 'test-cafe',
            'currency_code' => 'OMR',
            'currency_symbol' => "\xD8\xB1.\xD8\xB9.",
            'currency_decimals' => 3,
        ]);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Drinks']);
        Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name' => 'Karak',
            'price' => 0.50,
        ]);

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->assertSee("\xD8\xB1.\xD8\xB9. 0.500");
    }

    public function test_new_shop_defaults_to_omr(): void
    {
        $shop = Shop::create([
            'name' => 'New Shop',
            'slug' => 'new-shop',
        ]);

        // Refresh to pick up database column defaults
        $shop->refresh();

        $this->assertSame('OMR', $shop->currency_code);
        $this->assertSame('OMR', $shop->currency_symbol);
        $this->assertSame(3, (int) $shop->currency_decimals);
    }
}
