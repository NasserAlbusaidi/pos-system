<?php

namespace Tests\Feature\Livewire;

use App\Livewire\PosDashboard;
use App\Models\Category;
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

    public function test_clear_old_orders_completes_stale_ready_orders_once(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);

        $product = $this->makeProduct($shop->id);

        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'ready',
            'total_amount' => 8,
            'subtotal_amount' => 8,
            'tax_amount' => 0,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name_snapshot_en' => $product->name_en,
            'price_snapshot' => 8,
            'quantity' => 1,
        ]);

        DB::table('orders')->where('id', $order->id)->update(['updated_at' => now()->subMinutes(40)]);

        Livewire::actingAs($manager)
            ->test(PosDashboard::class)
            ->call('clearOldOrders')
            ->call('clearOldOrders');

        $order->refresh();

        $this->assertSame('completed', $order->status);
        $this->assertNotNull($order->fulfilled_at);
    }

    public function test_system_reset_completes_ready_orders_and_sets_fulfilled_at(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);

        $product = $this->makeProduct($shop->id);

        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'ready',
            'total_amount' => 10,
            'subtotal_amount' => 10,
            'tax_amount' => 0,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name_snapshot_en' => $product->name_en,
            'price_snapshot' => 5,
            'quantity' => 2,
        ]);

        Livewire::actingAs($manager)
            ->test(PosDashboard::class)
            ->call('systemReset');

        $order->refresh();

        $this->assertSame('completed', $order->status);
        $this->assertNotNull($order->fulfilled_at);
    }

    protected function makeProduct(int $shopId): Product
    {
        $category = Category::create([
            'shop_id' => $shopId,
            'name_en' => 'Coffee',
        ]);

        $product = Product::forceCreate([
            'shop_id' => $shopId,
            'category_id' => $category->id,
            'name_en' => 'Latte',
            'price' => 5,
        ]);

        return $product;
    }
}
