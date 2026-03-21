<?php

namespace Tests\Feature;

use App\Livewire\GuestMenu;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GuestMenuAvailabilityTest extends TestCase
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
                'paper' => '#FFFFFF',
                'accent' => '#000000',
                'ink' => '#000000',
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

    public function test_unavailable_product_visible_in_guest_menu(): void
    {
        $available = new Product([
            'name_en' => 'Croissant',
            'name_ar' => 'كرواسون',
            'price' => 0.500,
            'is_available' => true,
            'is_visible' => true,
            'sort_order' => 1,
        ]);
        $available->shop_id = $this->shop->id;
        $available->category_id = $this->category->id;
        $available->save();

        $unavailable = new Product([
            'name_en' => 'Danish',
            'name_ar' => 'دنماركي',
            'price' => 0.600,
            'is_available' => false,
            'is_visible' => true,
            'sort_order' => 2,
        ]);
        $unavailable->shop_id = $this->shop->id;
        $unavailable->category_id = $this->category->id;
        $unavailable->save();

        $component = Livewire::test(GuestMenu::class, ['shop' => $this->shop]);
        $component->assertSee('Croissant');
        $component->assertSee('Danish');
    }

    public function test_cannot_add_unavailable_product_to_cart(): void
    {
        $product = new Product([
            'name_en' => 'Sold Out Item',
            'name_ar' => 'عنصر نفد',
            'price' => 1.000,
            'is_available' => false,
            'is_visible' => true,
            'sort_order' => 1,
        ]);
        $product->shop_id = $this->shop->id;
        $product->category_id = $this->category->id;
        $product->save();

        $component = Livewire::test(GuestMenu::class, ['shop' => $this->shop]);
        $component->call('addToCart', $product->id);

        // Cart should remain empty because addToCart guards with is_available=true
        $this->assertEmpty($component->get('cart'));
    }

    public function test_checkout_removes_stale_unavailable_items_from_cart(): void
    {
        $productA = new Product([
            'name_en' => 'Muffin',
            'name_ar' => 'مافن',
            'price' => 0.800,
            'is_available' => true,
            'is_visible' => true,
            'sort_order' => 1,
        ]);
        $productA->shop_id = $this->shop->id;
        $productA->category_id = $this->category->id;
        $productA->save();

        $productB = new Product([
            'name_en' => 'Cookie',
            'name_ar' => 'بسكويت',
            'price' => 0.300,
            'is_available' => true,
            'is_visible' => true,
            'sort_order' => 2,
        ]);
        $productB->shop_id = $this->shop->id;
        $productB->category_id = $this->category->id;
        $productB->save();

        $component = Livewire::test(GuestMenu::class, ['shop' => $this->shop]);

        // Manually populate the cart with both products (bypassing addToCart)
        $component->set('cart', [
            $productA->id.'-plain' => [
                'id' => $productA->id,
                'name' => 'Muffin',
                'price' => 0.800,
                'quantity' => 1,
                'selectedModifiers' => [],
                'modifierNames' => [],
            ],
            $productB->id.'-plain' => [
                'id' => $productB->id,
                'name' => 'Cookie',
                'price' => 0.300,
                'quantity' => 1,
                'selectedModifiers' => [],
                'modifierNames' => [],
            ],
        ]);

        // Mark product A as unavailable (simulating a 86/sold-out event happening after the guest added it)
        $productA->is_available = false;
        $productA->save();

        // Trigger order submission
        $component->call('submitOrder');

        // The orderError should contain the sold-out item's name
        $this->assertStringContainsString('Muffin', $component->get('orderError'));

        // Product A should be auto-removed from cart
        $cart = $component->get('cart');
        $cartProductIds = collect($cart)->pluck('id')->all();
        $this->assertNotContains($productA->id, $cartProductIds);

        // Product B should still be in the cart
        $this->assertContains($productB->id, $cartProductIds);
    }

    public function test_category_with_only_unavailable_products_still_shown(): void
    {
        $unavailable = new Product([
            'name_en' => 'Special Cake',
            'name_ar' => 'كيك خاص',
            'price' => 2.000,
            'is_available' => false,
            'is_visible' => true,
            'sort_order' => 1,
        ]);
        $unavailable->shop_id = $this->shop->id;
        $unavailable->category_id = $this->category->id;
        $unavailable->save();

        $component = Livewire::test(GuestMenu::class, ['shop' => $this->shop]);

        // The category name should appear even though its only product is unavailable
        $component->assertSee('Pastries');
        // The product itself should also appear (greyed out, not hidden)
        $component->assertSee('Special Cake');
    }
}
