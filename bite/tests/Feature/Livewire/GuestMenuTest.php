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
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Coffee']);
        $product = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name' => 'Latte',
            'price' => 4.50,
        ]);

        // Simulating visiting the component.
        // Note: In real app, we'd pass shop via route model binding or prop.
        // For component test, we pass it as a prop.

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->assertSee('Coffee')
            ->assertSee('Latte')
            ->assertSee('OMR 4.500');
    }

    public function test_guest_can_add_item_to_cart(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Coffee']);
        $product = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name' => 'Latte',
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
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Coffee']);
        $product = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name' => 'Latte',
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
}
