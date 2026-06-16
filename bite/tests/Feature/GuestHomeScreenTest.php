<?php

namespace Tests\Feature;

use App\Livewire\GuestMenu;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GuestHomeScreenTest extends TestCase
{
    use RefreshDatabase;

    private Shop $shop;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shop = Shop::create([
            'name' => 'Test Cafe',
            'slug' => 'test-cafe',
            'currency_code' => 'OMR',
            'currency_symbol' => 'ر.ع.',
            'currency_decimals' => 3,
            'tax_rate' => 0,
            'branding' => [
                'onboarding_completed' => true,
            ],
        ]);
        $this->shop->status = 'active';
        $this->shop->trial_ends_at = now()->addYears(10);
        $this->shop->save();

        $this->category = Category::create([
            'shop_id' => $this->shop->id,
            'name_en' => 'Pastries',
            'name_ar' => 'معجنات',
            'sort_order' => 1,
        ]);
    }

    private function product(string $nameEn, int $sort, bool $available = true): Product
    {
        $product = new Product([
            'name_en' => $nameEn,
            'name_ar' => $nameEn,
            'price' => 1.000,
            'is_available' => $available,
            'is_visible' => true,
            'sort_order' => $sort,
        ]);
        $product->shop_id = $this->shop->id;
        $product->category_id = $this->category->id;
        $product->save();

        return $product;
    }

    public function test_guest_lands_on_home_screen_by_default(): void
    {
        // Two products: the leading item fills Today's Highlight, the next the
        // popular grid (whose header carries the "See all" → full-menu control).
        $this->product('Croissant', 1);
        $this->product('Sourdough', 2);

        Livewire::test(GuestMenu::class, ['shop' => $this->shop])
            ->assertSet('screen', 'home')
            ->assertSee(__('guest.todays_highlight'))
            ->assertSee(__('guest.see_all'));
    }

    public function test_home_renders_highlight_and_popular_products(): void
    {
        $this->product('Hero Loaf', 1);
        $this->product('Side Bun', 2);

        $component = Livewire::test(GuestMenu::class, ['shop' => $this->shop]);

        $component->assertSet('screen', 'home');
        $component->assertSee('Hero Loaf');
        $component->assertSee('Side Bun');
    }

    public function test_see_all_switches_to_full_menu_screen(): void
    {
        $this->product('Croissant', 1);

        $component = Livewire::test(GuestMenu::class, ['shop' => $this->shop]);

        $component->call('showMenu')
            ->assertSet('screen', 'menu')
            ->assertSee(__('guest.search_menu'));

        $component->call('showHome')
            ->assertSet('screen', 'home');
    }

    public function test_full_menu_screen_renders_subheader_and_menu_rows(): void
    {
        $this->product('Croissant', 1);

        Livewire::test(GuestMenu::class, ['shop' => $this->shop])
            ->call('showMenu')
            ->assertSet('screen', 'menu')
            ->assertSee(__('guest.full_menu'))
            ->assertSeeHtml('menu-row')
            ->assertSee('Croissant');
    }
}
