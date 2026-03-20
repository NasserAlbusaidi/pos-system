<?php

namespace Tests\Feature;

use App\Livewire\GuestMenu;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Livewire\Livewire;
use Tests\TestCase;

class SourdoughDemoTest extends TestCase
{
    use RefreshDatabase;

    private Shop $shop;

    private Category $breadCategory;

    private Category $beverageCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shop = Shop::create([
            'name' => 'Sourdough Oman',
            'slug' => 'sourdough',
            'currency_code' => 'OMR',
            'currency_symbol' => 'ر.ع.',
            'currency_decimals' => 3,
            'tax_rate' => 0,
            'branding' => [
                'paper' => '#F5F0E8',
                'accent' => '#C4975A',
                'ink' => '#2C2520',
                'onboarding_completed' => true,
            ],
        ]);
        $this->shop->status = 'active';
        $this->shop->trial_ends_at = now()->addYears(10);
        $this->shop->save();

        $this->breadCategory = Category::create([
            'shop_id' => $this->shop->id,
            'name_en' => 'Breads',
            'name_ar' => 'خبز',
            'sort_order' => 1,
        ]);

        $this->beverageCategory = Category::create([
            'shop_id' => $this->shop->id,
            'name_en' => 'Beverages',
            'name_ar' => 'مشروبات',
            'sort_order' => 2,
        ]);

        $bread = new Product([
            'name_en' => 'Sourdough Loaf',
            'name_ar' => 'خبز العجين المخمر',
            'description_en' => 'Classic artisan sourdough bread',
            'description_ar' => 'خبز العجين المخمر الكلاسيكي',
            'price' => 1.200,
            'is_available' => true,
            'is_visible' => true,
            'sort_order' => 1,
        ]);
        $bread->shop_id = $this->shop->id;
        $bread->category_id = $this->breadCategory->id;
        $bread->save();

        $coffee = new Product([
            'name_en' => 'Cappuccino',
            'name_ar' => 'كابتشينو',
            'description_en' => 'Double shot espresso with steamed milk',
            'description_ar' => 'إسبريسو مزدوج مع حليب مبخر',
            'price' => 1.500,
            'is_available' => true,
            'is_visible' => true,
            'sort_order' => 1,
        ]);
        $coffee->shop_id = $this->shop->id;
        $coffee->category_id = $this->beverageCategory->id;
        $coffee->save();
    }

    public function test_guest_menu_loads_sourdough_shop(): void
    {
        $this->get('/menu/sourdough')
            ->assertStatus(200)
            ->assertSee('Sourdough Oman');
    }

    public function test_guest_menu_shows_branding_tokens(): void
    {
        $response = $this->get('/menu/sourdough');

        $response->assertSee('--canvas:', false);
        $response->assertSee('--panel:', false);
    }

    public function test_guest_menu_shows_products_from_all_categories(): void
    {
        Livewire::test(GuestMenu::class, ['shop' => $this->shop])
            ->assertSee('Sourdough Loaf')
            ->assertSee('Cappuccino');
    }

    public function test_guest_menu_shows_bilingual_names(): void
    {
        App::setLocale('ar');

        Livewire::test(GuestMenu::class, ['shop' => $this->shop])
            ->assertSee('خبز العجين المخمر');
    }
}
