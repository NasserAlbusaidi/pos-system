<?php

namespace Tests\Feature\Livewire;

use App\Livewire\KitchenDisplay;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * SEC-01 regression tests — KitchenDisplay tenant isolation.
 *
 * These tests verify that a user authenticated for shopA cannot transition
 * or cancel orders belonging to shopB via the KitchenDisplay component.
 * Any failure here indicates a tenant isolation gap that must be fixed
 * before deployment (D-14 pre-deploy gate).
 */
class KitchenDisplayTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_transition_another_shops_order_to_preparing(): void
    {
        [$shopA, $shopB] = $this->makeShops();

        $orderB = Order::forceCreate([
            'shop_id' => $shopB->id,
            'status' => 'paid',
            'total_amount' => 15.000,
        ]);

        $kitchenA = User::factory()->create(['shop_id' => $shopA->id, 'role' => 'kitchen']);

        // Component correctly uses firstOrFail scoped to shop_id — a cross-tenant call
        // must throw ModelNotFoundException. The order must remain unchanged.
        try {
            Livewire::actingAs($kitchenA)
                ->test(KitchenDisplay::class)
                ->call('updateStatus', $orderB->id, 'preparing');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            // Expected: component correctly rejected the cross-tenant request
        }

        // shopB's order must remain at 'paid' — cross-tenant status transition blocked
        $this->assertSame('paid', $orderB->fresh()->status);
    }

    public function test_cannot_transition_another_shops_order_to_ready(): void
    {
        [$shopA, $shopB] = $this->makeShops();

        $orderB = Order::forceCreate([
            'shop_id' => $shopB->id,
            'status' => 'preparing',
            'total_amount' => 20.000,
        ]);

        $kitchenA = User::factory()->create(['shop_id' => $shopA->id, 'role' => 'kitchen']);

        // Component correctly uses firstOrFail scoped to shop_id — a cross-tenant call
        // must throw ModelNotFoundException. The order must remain unchanged.
        try {
            Livewire::actingAs($kitchenA)
                ->test(KitchenDisplay::class)
                ->call('updateStatus', $orderB->id, 'ready');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            // Expected: component correctly rejected the cross-tenant request
        }

        // shopB's order must remain at 'preparing' — cross-tenant status transition blocked
        $this->assertSame('preparing', $orderB->fresh()->status);
    }

    public function test_kds_does_not_render_another_shops_orders(): void
    {
        [$shopA, $shopB] = $this->makeShops();

        $orderA = Order::forceCreate([
            'shop_id' => $shopA->id,
            'status' => 'paid',
            'total_amount' => 10.000,
        ]);

        $orderB = Order::forceCreate([
            'shop_id' => $shopB->id,
            'status' => 'paid',
            'total_amount' => 50.000,
        ]);

        $kitchenA = User::factory()->create(['shop_id' => $shopA->id, 'role' => 'kitchen']);

        Livewire::actingAs($kitchenA)
            ->test(KitchenDisplay::class)
            ->assertSee('Ticket_'.$orderA->id)
            ->assertDontSee('Ticket_'.$orderB->id);
    }

    protected function makeShops(): array
    {
        $shopA = Shop::create(['name' => 'Shop A', 'slug' => 'shop-a']);
        $shopB = Shop::create(['name' => 'Shop B', 'slug' => 'shop-b']);

        return [$shopA, $shopB];
    }
}
