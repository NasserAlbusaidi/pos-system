<?php

namespace Tests\Feature\Livewire;

use App\Livewire\PosDashboard;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Walk-in / counter order entry (#56): staff build a cart from the menu and
 * settle it at the counter. The order is re-priced server-side (never trusts a
 * client price), tenant-scoped, and full payment is taken on charge.
 */
class PosWalkInOrderTest extends TestCase
{
    use RefreshDatabase;

    private Shop $shop;

    private Product $loaf;   // 2.500

    private Product $coffee; // 1.200

    protected function setUp(): void
    {
        parent::setUp();

        $this->shop = Shop::create(['name' => 'Bite', 'slug' => 'bite', 'tax_rate' => 0]);
        $category = Category::create(['shop_id' => $this->shop->id, 'name_en' => 'Bakery', 'is_active' => true]);

        $this->loaf = $this->makeProduct($category, 'Sourdough loaf', 2.500);
        $this->coffee = $this->makeProduct($category, 'Latte', 1.200);
    }

    private function makeProduct(Category $category, string $name, float $price): Product
    {
        $product = new Product([
            'category_id' => $category->id,
            'name_en' => $name,
            'price' => $price,
            'is_available' => true,
            'is_visible' => true,
        ]);
        $product->shop_id = $this->shop->id; // guarded
        $product->save();

        return $product;
    }

    private function server(): User
    {
        return User::factory()->create(['shop_id' => $this->shop->id, 'role' => 'server']);
    }

    public function test_staff_can_create_and_charge_a_walk_in_order(): void
    {
        Livewire::actingAs($this->server())
            ->test(PosDashboard::class)
            ->call('openNewOrder')
            ->call('addToCart', $this->loaf->id)
            ->call('addToCart', $this->loaf->id)
            ->call('addToCart', $this->coffee->id)
            ->set('newOrderName', 'Aisha')
            ->call('chargeNewOrder', 'cash')
            ->assertSet('newOrderError', null)
            ->assertSet('showNewOrder', false);

        $order = Order::where('shop_id', $this->shop->id)->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertSame('paid', $order->status);
        $this->assertSame('Aisha', $order->customer_name);
        // Re-priced server-side: 2.500 x2 + 1.200 = 6.200
        $this->assertEqualsWithDelta(6.200, (float) $order->total_amount, 0.0001);
        $this->assertSame(2, $order->items()->count());

        $this->assertSame(1, Payment::where('order_id', $order->id)->count());
        $this->assertEqualsWithDelta(6.200, (float) Payment::where('order_id', $order->id)->sum('amount'), 0.0001);
        $this->assertSame('cash', $order->fresh()->payment_method);
    }

    public function test_blank_customer_name_defaults_to_walk_in(): void
    {
        Livewire::actingAs($this->server())
            ->test(PosDashboard::class)
            ->call('openNewOrder')
            ->call('addToCart', $this->loaf->id)
            ->call('chargeNewOrder', 'card')
            ->assertSet('showNewOrder', false);

        $order = Order::where('shop_id', $this->shop->id)->latest('id')->first();
        $this->assertSame('Walk-in', $order->customer_name);
        $this->assertSame('paid', $order->status);
    }

    public function test_empty_cart_cannot_be_charged(): void
    {
        Livewire::actingAs($this->server())
            ->test(PosDashboard::class)
            ->call('openNewOrder')
            ->call('chargeNewOrder', 'cash')
            ->assertNotSet('newOrderError', null)
            ->assertSet('showNewOrder', true);

        $this->assertSame(0, Order::where('shop_id', $this->shop->id)->count());
    }

    public function test_foreign_shop_product_cannot_be_added(): void
    {
        $otherShop = Shop::create(['name' => 'Other', 'slug' => 'other']);
        $otherCat = Category::create(['shop_id' => $otherShop->id, 'name_en' => 'X', 'is_active' => true]);
        $foreign = $this->makeProductFor($otherShop, $otherCat, 'Foreign cake', 9.000);

        Livewire::actingAs($this->server())
            ->test(PosDashboard::class)
            ->call('openNewOrder')
            ->call('addToCart', $foreign->id)
            ->assertSet('posCart', []);
    }

    private function makeProductFor(Shop $shop, Category $category, string $name, float $price): Product
    {
        $product = new Product([
            'category_id' => $category->id,
            'name_en' => $name,
            'price' => $price,
            'is_available' => true,
            'is_visible' => true,
        ]);
        $product->shop_id = $shop->id;
        $product->save();

        return $product;
    }
}
