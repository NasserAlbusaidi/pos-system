<?php

namespace Tests\Feature\Livewire;

use App\Livewire\PosDashboard;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * SEC-01 regression tests — PosDashboard tenant isolation.
 *
 * These tests verify that a user authenticated for shopA cannot access,
 * mutate, or view orders belonging to shopB via the PosDashboard component.
 * Any failure here indicates a tenant isolation gap that must be fixed
 * before deployment (D-14 pre-deploy gate).
 */
class PosDashboardTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_mark_another_shops_order_as_paid(): void
    {
        [$shopA, $shopB] = $this->makeShops();

        $orderB = Order::forceCreate([
            'shop_id' => $shopB->id,
            'status' => 'unpaid',
            'total_amount' => 12.500,
        ]);

        $userA = User::factory()->create(['shop_id' => $shopA->id, 'role' => 'server']);

        // Component correctly uses findOrFail scoped to shop_id — a cross-tenant call
        // must throw ModelNotFoundException (no order found for shopA with shopB's id).
        // The order must remain unpaid and no payment must be recorded.
        try {
            Livewire::actingAs($userA)
                ->test(PosDashboard::class)
                ->call('markAsPaid', $orderB->id, 'cash');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            // Expected: component correctly rejected the cross-tenant request
        }

        // shopB's order must remain unpaid — cross-tenant mutation blocked
        $this->assertSame('unpaid', $orderB->fresh()->status);
        $this->assertDatabaseMissing('payments', ['order_id' => $orderB->id]);
    }

    public function test_cannot_cancel_another_shops_order(): void
    {
        [$shopA, $shopB] = $this->makeShops();

        $orderB = Order::forceCreate([
            'shop_id' => $shopB->id,
            'status' => 'unpaid',
            'total_amount' => 8.000,
        ]);

        // Use admin so manager override is not required
        $adminA = User::factory()->create(['shop_id' => $shopA->id, 'role' => 'admin']);

        // Component correctly uses findOrFail scoped to shop_id — must throw on cross-tenant ID.
        try {
            Livewire::actingAs($adminA)
                ->test(PosDashboard::class)
                ->call('cancelOrder', $orderB->id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            // Expected: component correctly rejected the cross-tenant request
        }

        // shopB's order must remain unpaid — cross-tenant cancel blocked
        $this->assertSame('unpaid', $orderB->fresh()->status);
    }

    public function test_dashboard_does_not_render_another_shops_orders(): void
    {
        [$shopA, $shopB] = $this->makeShops();

        $orderA = Order::forceCreate([
            'shop_id' => $shopA->id,
            'status' => 'unpaid',
            'total_amount' => 5.000,
        ]);

        $orderB = Order::forceCreate([
            'shop_id' => $shopB->id,
            'status' => 'unpaid',
            'total_amount' => 99.000,
        ]);

        $userA = User::factory()->create(['shop_id' => $shopA->id, 'role' => 'server']);

        Livewire::actingAs($userA)
            ->test(PosDashboard::class)
            ->assertSee('ID_'.$orderA->id)
            ->assertDontSee('ID_'.$orderB->id);
    }

    protected function makeShops(): array
    {
        $shopA = Shop::create(['name' => 'Shop A', 'slug' => 'shop-a']);
        $shopB = Shop::create(['name' => 'Shop B', 'slug' => 'shop-b']);

        return [$shopA, $shopB];
    }
}
