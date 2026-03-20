<?php

namespace Tests\Feature\Livewire;

use App\Livewire\GuestMenu;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GuestMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_see_menu(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $category = Category::create(['shop_id' => $shop->id, 'name_en' => 'Coffee']);
        $product = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Latte',
            'price' => 4.50,
        ]);

        // Simulating visiting the component.
        // Note: In real app, we'd pass shop via route model binding or prop.
        // For component test, we pass it as a prop.

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->assertSee('Coffee')
            ->assertSee('Latte')
            ->assertSee('4.500')
            ->assertSeeHtml('class="omr-symbol"');
    }

    public function test_guest_can_add_item_to_cart(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $category = Category::create(['shop_id' => $shop->id, 'name_en' => 'Coffee']);
        $product = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Latte',
            'price' => 4.50,
        ]);

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->assertSet('cart', [
                $product->id.'-plain' => [
                    'id' => $product->id,
                    'name' => 'Latte',
                    'price' => 4.50,
                    'quantity' => 1,
                    'selectedModifiers' => [],
                    'modifierNames' => [],
                ],
            ])
            ->call('addToCart', $product->id)
            ->assertSet('cart', [
                $product->id.'-plain' => [
                    'id' => $product->id,
                    'name' => 'Latte',
                    'price' => 4.50,
                    'quantity' => 2,
                    'selectedModifiers' => [],
                    'modifierNames' => [],
                ],
            ]);
    }

    public function test_guest_can_submit_order(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $category = Category::create(['shop_id' => $shop->id, 'name_en' => 'Coffee']);
        $product = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Latte',
            'price' => 4.50,
        ]);

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->call('submitOrder');

        $this->assertDatabaseHas('orders', [
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'total_amount' => 4.50,
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'price_snapshot' => 4.50,
        ]);
    }

    public function test_submit_order_rejects_unavailable_items(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $category = Category::create(['shop_id' => $shop->id, 'name_en' => 'Coffee']);
        $available = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Latte',
            'price' => 4.50,
            'is_available' => true,
        ]);
        $eightySixed = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Croissant',
            'price' => 2.00,
            'is_available' => true,
        ]);

        // Customer adds both items while they are available
        $component = Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $available->id)
            ->call('addToCart', $eightySixed->id);

        // Staff 86's the croissant after the customer loaded the menu
        $eightySixed->update(['is_available' => false]);

        // Customer tries to submit — should be rejected
        $component->call('submitOrder')
            ->assertSet('showReviewModal', true)
            ->assertNotSet('orderError', null);

        // Verify the error message contains the unavailable item name
        $orderError = $component->get('orderError');
        $this->assertStringContainsString('Croissant', $orderError);

        // No order should have been created
        $this->assertDatabaseMissing('orders', [
            'shop_id' => $shop->id,
        ]);
    }

    public function test_submit_order_succeeds_when_all_items_available(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $category = Category::create(['shop_id' => $shop->id, 'name_en' => 'Coffee']);
        $product = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Latte',
            'price' => 4.50,
            'is_available' => true,
        ]);

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->call('submitOrder')
            ->assertSet('orderError', null);

        $this->assertDatabaseHas('orders', [
            'shop_id' => $shop->id,
            'status' => 'unpaid',
        ]);
    }

    public function test_add_to_cart_ignores_unavailable_product(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $category = Category::create(['shop_id' => $shop->id, 'name_en' => 'Coffee']);
        $product = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Croissant',
            'price' => 2.00,
            'is_available' => false,
        ]);

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->assertSet('cart', []);
    }

    public function test_product_image_url_includes_storage_prefix(): void
    {
        $shop = Shop::create(['name' => 'Sourdough', 'slug' => 'sourdough']);
        $category = Category::create(['shop_id' => $shop->id, 'name_en' => 'Bread']);
        Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Sourdough Loaf',
            'price' => 2.500,
            'image_url' => 'products/sourdough.jpg',
            'is_available' => true,
            'is_visible' => true,
        ]);

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->assertSeeHtml('/storage/products/sourdough.jpg');
    }
}
