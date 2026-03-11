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
use Livewire\Livewire;
use Tests\TestCase;

class FulfillmentIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_ready_to_completed_transition_is_idempotent(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $server = User::factory()->create(['shop_id' => $shop->id, 'role' => 'server']);

        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Coffee']);
        $product = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name' => 'Latte',
            'price' => 5.00,
        ]);

        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'ready',
            'total_amount' => 10.00,
            'subtotal_amount' => 10.00,
            'tax_amount' => 0,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name_snapshot' => 'Latte',
            'price_snapshot' => 5.00,
            'quantity' => 2,
        ]);

        Livewire::actingAs($server)
            ->test(PosDashboard::class)
            ->call('markAsDelivered', $order->id)
            ->call('markAsDelivered', $order->id);

        $order->refresh();

        $this->assertSame('completed', $order->status);
        $this->assertNotNull($order->fulfilled_at);
    }
}
