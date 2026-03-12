<?php

namespace Tests\Feature\Livewire;

use App\Livewire\GuestMenu;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GuestMenuSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_manipulate_price_in_cart(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $category = Category::create(['shop_id' => $shop->id, 'name_en' => 'Coffee']);
        $product = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Latte',
            'price' => 10.00,
        ]);

        // Maliciously set cart state with wrong price
        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->set('cart', [
                $product->id => [
                    'id' => $product->id,
                    'name' => 'Latte',
                    'price' => 0.01, // Hacked price
                    'quantity' => 1,
                ],
            ])
            ->call('submitOrder');

        // Order should be created with REAL price (10.00), not hacked price (0.01)
        $this->assertDatabaseHas('orders', [
            'total_amount' => 10.00,
        ]);
    }
}
