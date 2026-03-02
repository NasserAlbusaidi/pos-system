<?php

namespace Tests\Feature\Livewire;

use App\Livewire\PosDashboard;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class OrderCleanupIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_clear_old_orders_completes_stale_ready_orders_with_inventory_once(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);

        [$product, $ingredient] = $this->makeProductWithIngredient($shop->id, 10, 2);

        $order = Order::create([
            'shop_id' => $shop->id,
            'status' => 'ready',
            'total_amount' => 8,
            'subtotal_amount' => 8,
            'tax_amount' => 0,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'price_snapshot' => 8,
            'quantity' => 1,
        ]);

        DB::table('orders')->where('id', $order->id)->update(['updated_at' => now()->subMinutes(40)]);

        Livewire::actingAs($manager)
            ->test(PosDashboard::class)
            ->call('clearOldOrders')
            ->call('clearOldOrders');

        $order->refresh();
        $ingredient->refresh();

        $this->assertSame('completed', $order->status);
        $this->assertNotNull($order->fulfilled_at);
        $this->assertEquals(8.0, (float) $ingredient->stock_quantity);
    }

    public function test_system_reset_completes_ready_orders_and_sets_fulfilled_at(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);

        [$product, $ingredient] = $this->makeProductWithIngredient($shop->id, 10, 1.5);

        $order = Order::create([
            'shop_id' => $shop->id,
            'status' => 'ready',
            'total_amount' => 10,
            'subtotal_amount' => 10,
            'tax_amount' => 0,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'price_snapshot' => 5,
            'quantity' => 2,
        ]);

        Livewire::actingAs($manager)
            ->test(PosDashboard::class)
            ->call('systemReset');

        $order->refresh();
        $ingredient->refresh();

        $this->assertSame('completed', $order->status);
        $this->assertNotNull($order->fulfilled_at);
        $this->assertEquals(7.0, (float) $ingredient->stock_quantity);
    }

    protected function makeProductWithIngredient(int $shopId, float $stockQty, float $pivotQty): array
    {
        $category = Category::create([
            'shop_id' => $shopId,
            'name' => 'Coffee',
        ]);

        $product = Product::create([
            'shop_id' => $shopId,
            'category_id' => $category->id,
            'name' => 'Latte',
            'price' => 5,
        ]);

        $ingredient = Ingredient::create([
            'shop_id' => $shopId,
            'name' => 'Milk',
            'unit' => 'ml',
            'stock_quantity' => $stockQty,
            'reorder_threshold' => 1,
        ]);

        $product->ingredients()->attach($ingredient->id, ['quantity' => $pivotQty]);

        return [$product, $ingredient];
    }
}
