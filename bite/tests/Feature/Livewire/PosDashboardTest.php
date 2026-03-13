<?php

namespace Tests\Feature\Livewire;

use App\Livewire\PosDashboard;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PosDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_see_unpaid_orders_on_dashboard(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'server']);

        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'total_amount' => 10.00,
        ]);

        $otherShop = Shop::create(['name' => 'Other', 'slug' => 'other']);
        $otherOrder = Order::forceCreate([
            'shop_id' => $otherShop->id,
            'status' => 'unpaid',
            'total_amount' => 5.00,
        ]);

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->assertSee('ID_'.$order->id)
            ->assertSee('10.000')
            ->assertSeeHtml('class="omr-symbol"');
    }

    public function test_staff_can_mark_order_as_paid(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'server']);

        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'total_amount' => 10.00,
        ]);

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->call('markAsPaid', $order->id, 'card');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
            'payment_method' => 'card',
        ]);

        $this->assertNotNull($order->fresh()->paid_at);
    }

    public function test_staff_can_mark_order_as_delivered(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'server']);

        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'ready',
            'total_amount' => 10.00,
        ]);

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->call('markAsDelivered', $order->id);

        $this->assertEquals('completed', $order->fresh()->status);
    }
}
