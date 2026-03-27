<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Admin\ReportsDashboard;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * SEC-01 regression tests — ReportsDashboard tenant isolation.
 *
 * These tests verify that the ReportsDashboard for shopA does not include
 * sales data from shopB. Any failure here indicates a tenant isolation gap
 * that must be fixed before deployment (D-14 pre-deploy gate).
 */
class ReportsDashboardTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_dashboard_only_shows_own_shops_revenue(): void
    {
        [$shopA, $shopB] = $this->makeShops();

        // ShopA completed order: total 25.000
        Order::forceCreate([
            'shop_id' => $shopA->id,
            'status' => 'completed',
            'total_amount' => 25.000,
            'paid_at' => now(),
        ]);

        // ShopB completed order: total 99.999 — must NOT appear in shopA reports
        Order::forceCreate([
            'shop_id' => $shopB->id,
            'status' => 'completed',
            'total_amount' => 99.999,
            'paid_at' => now(),
        ]);

        $managerA = User::factory()->create(['shop_id' => $shopA->id, 'role' => 'manager']);

        // totalRevenue and totalOrders are view variables passed from render(),
        // so we use assertViewHas to verify their values.
        Livewire::actingAs($managerA)
            ->test(ReportsDashboard::class)
            ->assertViewHas('totalRevenue', function ($totalRevenue) {
                // Only shopA's revenue (25.000) should be included, not shopB's (99.999)
                return abs($totalRevenue - 25.000) < 0.001
                    && abs($totalRevenue - 124.999) > 0.001;
            });
    }

    public function test_reports_dashboard_order_count_excludes_other_shops(): void
    {
        [$shopA, $shopB] = $this->makeShops();

        Order::forceCreate([
            'shop_id' => $shopA->id,
            'status' => 'completed',
            'total_amount' => 10.000,
            'paid_at' => now(),
        ]);

        // Two orders for shopB — must not appear in shopA's count
        Order::forceCreate([
            'shop_id' => $shopB->id,
            'status' => 'completed',
            'total_amount' => 10.000,
            'paid_at' => now(),
        ]);

        Order::forceCreate([
            'shop_id' => $shopB->id,
            'status' => 'completed',
            'total_amount' => 10.000,
            'paid_at' => now(),
        ]);

        $managerA = User::factory()->create(['shop_id' => $shopA->id, 'role' => 'manager']);

        Livewire::actingAs($managerA)
            ->test(ReportsDashboard::class)
            ->assertViewHas('totalOrders', 1);
    }

    protected function makeShops(): array
    {
        $shopA = Shop::create(['name' => 'Shop A', 'slug' => 'shop-a']);
        $shopB = Shop::create(['name' => 'Shop B', 'slug' => 'shop-b']);

        return [$shopA, $shopB];
    }
}
