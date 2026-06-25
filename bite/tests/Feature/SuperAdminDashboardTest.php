<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\Dashboard;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SuperAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_stats_and_actions()
    {
        $admin = User::factory()->superAdmin()->create();
        $shop = Shop::factory()->create();

        Livewire::actingAs($admin)
            ->test(Dashboard::class)
            ->assertSee($shop->name)
            ->assertSee('active')
            // Test Toggle
            ->call('toggleStatus', $shop->id)
            ->assertSee('suspended');

        $this->assertEquals('suspended', $shop->fresh()->status);

        // Test Delete
        Livewire::actingAs($admin)
            ->test(Dashboard::class)
            ->call('deleteShop', $shop->id);

        $this->assertDatabaseMissing('shops', ['id' => $shop->id]);
    }

    public function test_shop_with_financial_history_is_suspended_instead_of_deleted(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $shop = Shop::factory()->create(['status' => 'active']);
        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'paid',
            'subtotal_amount' => 10.000,
            'tax_amount' => 0,
            'total_amount' => 10.000,
            'paid_at' => now(),
        ]);
        $payment = Payment::forceCreate([
            'shop_id' => $shop->id,
            'order_id' => $order->id,
            'amount' => 10.000,
            'method' => 'cash',
            'paid_at' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(Dashboard::class)
            ->call('deleteShop', $shop->id)
            ->assertDispatched('toast', variant: 'error');

        $this->assertDatabaseHas('shops', [
            'id' => $shop->id,
            'status' => 'suspended',
        ]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'shop_id' => $shop->id]);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'shop_id' => $shop->id]);
    }
}
