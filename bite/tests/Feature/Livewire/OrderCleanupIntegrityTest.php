<?php

namespace Tests\Feature\Livewire;

use App\Livewire\PosDashboard;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
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

    public function test_clear_old_orders_audits_affected_orders_without_touching_financial_history(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);

        $expiredUnpaid = $this->makeOrder($shop, 'unpaid', expiresAt: now()->subMinute());
        $expiredWithPayment = $this->makeOrder($shop, 'unpaid', expiresAt: now()->subMinute());
        Payment::forceCreate([
            'shop_id' => $shop->id,
            'order_id' => $expiredWithPayment->id,
            'amount' => 3.000,
            'method' => 'cash',
            'created_by' => $manager->id,
            'paid_at' => now(),
        ]);
        $staleReady = $this->makeOrder($shop, 'ready');
        $freshReady = $this->makeOrder($shop, 'ready');
        $paid = $this->makeOrder($shop, 'paid', paidAt: now());

        DB::table('orders')->where('id', $staleReady->id)->update(['updated_at' => now()->subMinutes(40)]);

        Livewire::actingAs($manager)
            ->test(PosDashboard::class)
            ->call('clearOldOrders');

        $log = AuditLog::where('action', 'orders.cleared')->latest()->firstOrFail();

        $this->assertSame('cancelled', $expiredUnpaid->fresh()->status);
        $this->assertSame('unpaid', $expiredWithPayment->fresh()->status);
        $this->assertSame('completed', $staleReady->fresh()->status);
        $this->assertSame('ready', $freshReady->fresh()->status);
        $this->assertSame('paid', $paid->fresh()->status);
        $this->assertSame(1, Payment::where('order_id', $expiredWithPayment->id)->count());

        $this->assertSame(1, $log->meta['cancelled_unpaid_count']);
        $this->assertSame([$expiredUnpaid->id], $log->meta['cancelled_unpaid_order_ids']);
        $this->assertSame(1, $log->meta['completed_ready_count']);
        $this->assertSame([$staleReady->id], $log->meta['completed_ready_order_ids']);
    }

    public function test_system_reset_audits_affected_orders_without_touching_financial_history(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);

        $unpaid = $this->makeOrder($shop, 'unpaid');
        $unpaidWithPayment = $this->makeOrder($shop, 'unpaid');
        Payment::forceCreate([
            'shop_id' => $shop->id,
            'order_id' => $unpaidWithPayment->id,
            'amount' => 4.000,
            'method' => 'cash',
            'created_by' => $manager->id,
            'paid_at' => now(),
        ]);
        $ready = $this->makeOrder($shop, 'ready');
        $paid = $this->makeOrder($shop, 'paid', paidAt: now());

        Livewire::actingAs($manager)
            ->test(PosDashboard::class)
            ->call('systemReset');

        $log = AuditLog::where('action', 'orders.system_reset')->latest()->firstOrFail();

        $this->assertSame('cancelled', $unpaid->fresh()->status);
        $this->assertSame('unpaid', $unpaidWithPayment->fresh()->status);
        $this->assertSame('completed', $ready->fresh()->status);
        $this->assertSame('paid', $paid->fresh()->status);
        $this->assertSame(1, Payment::where('order_id', $unpaidWithPayment->id)->count());

        $this->assertSame(1, $log->meta['cancelled_unpaid_count']);
        $this->assertSame([$unpaid->id], $log->meta['cancelled_unpaid_order_ids']);
        $this->assertSame(1, $log->meta['completed_ready_count']);
        $this->assertSame([$ready->id], $log->meta['completed_ready_order_ids']);
    }

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

    private function makeOrder(
        Shop $shop,
        string $status,
        ?\DateTimeInterface $expiresAt = null,
        ?\DateTimeInterface $paidAt = null
    ): Order {
        return Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => $status,
            'total_amount' => 10,
            'subtotal_amount' => 10,
            'tax_amount' => 0,
            'expires_at' => $expiresAt,
            'paid_at' => $paidAt,
        ]);
    }
}
