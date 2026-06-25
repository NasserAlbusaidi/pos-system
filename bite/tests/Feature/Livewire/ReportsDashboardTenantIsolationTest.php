<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Admin\ReportsDashboard;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

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

    public function test_reports_dashboard_counts_paid_in_progress_orders_as_revenue(): void
    {
        [$shopA, $shopB] = $this->makeShops();

        foreach (['paid', 'preparing', 'ready', 'completed'] as $index => $status) {
            Order::forceCreate([
                'shop_id' => $shopA->id,
                'status' => $status,
                'total_amount' => 10.000 + $index,
                'paid_at' => now(),
            ]);
        }

        Order::forceCreate([
            'shop_id' => $shopA->id,
            'status' => 'unpaid',
            'total_amount' => 77.000,
            'paid_at' => null,
        ]);

        Order::forceCreate([
            'shop_id' => $shopA->id,
            'status' => 'cancelled',
            'total_amount' => 88.000,
            'paid_at' => now(),
        ]);

        Order::forceCreate([
            'shop_id' => $shopB->id,
            'status' => 'paid',
            'total_amount' => 99.000,
            'paid_at' => now(),
        ]);

        $managerA = User::factory()->create(['shop_id' => $shopA->id, 'role' => 'manager']);

        Livewire::actingAs($managerA)
            ->test(ReportsDashboard::class)
            ->assertViewHas('totalRevenue', fn ($totalRevenue) => abs($totalRevenue - 46.000) < 0.001)
            ->assertViewHas('totalOrders', 4);
    }

    public function test_reports_payment_summary_uses_payment_ledger_for_split_tenders(): void
    {
        [$shopA, $shopB] = $this->makeShops();

        $order = Order::forceCreate([
            'shop_id' => $shopA->id,
            'status' => 'completed',
            'payment_method' => 'split',
            'total_amount' => 10.000,
            'paid_at' => now(),
        ]);

        Payment::forceCreate([
            'shop_id' => $shopA->id,
            'order_id' => $order->id,
            'amount' => 6.000,
            'method' => 'cash',
            'paid_at' => now(),
        ]);
        Payment::forceCreate([
            'shop_id' => $shopA->id,
            'order_id' => $order->id,
            'amount' => 4.000,
            'method' => 'card',
            'paid_at' => now(),
        ]);

        $otherOrder = Order::forceCreate([
            'shop_id' => $shopB->id,
            'status' => 'completed',
            'payment_method' => 'cash',
            'total_amount' => 99.000,
            'paid_at' => now(),
        ]);
        Payment::forceCreate([
            'shop_id' => $shopB->id,
            'order_id' => $otherOrder->id,
            'amount' => 99.000,
            'method' => 'cash',
            'paid_at' => now(),
        ]);

        $managerA = User::factory()->create(['shop_id' => $shopA->id, 'role' => 'manager']);

        Livewire::actingAs($managerA)
            ->test(ReportsDashboard::class)
            ->assertViewHas('paymentSummary', function ($summary) {
                $byMethod = $summary->keyBy('payment_method');

                return $byMethod->has('cash')
                    && $byMethod->has('card')
                    && ! $byMethod->has('split')
                    && abs((float) $byMethod['cash']->total - 6.000) < 0.001
                    && abs((float) $byMethod['card']->total - 4.000) < 0.001;
            });
    }

    public function test_reports_payment_summary_excludes_payments_for_non_revenue_orders(): void
    {
        [$shopA, $shopB] = $this->makeShops();

        $included = Order::forceCreate([
            'shop_id' => $shopA->id,
            'status' => 'completed',
            'payment_method' => 'cash',
            'total_amount' => 10.000,
            'paid_at' => now(),
        ]);
        Payment::forceCreate([
            'shop_id' => $shopA->id,
            'order_id' => $included->id,
            'amount' => 10.000,
            'method' => 'cash',
            'paid_at' => now(),
        ]);

        $cancelled = Order::forceCreate([
            'shop_id' => $shopA->id,
            'status' => 'cancelled',
            'payment_method' => 'cash',
            'total_amount' => 99.000,
            'paid_at' => now(),
        ]);
        Payment::forceCreate([
            'shop_id' => $shopA->id,
            'order_id' => $cancelled->id,
            'amount' => 99.000,
            'method' => 'cash',
            'paid_at' => now(),
        ]);

        $unpaid = Order::forceCreate([
            'shop_id' => $shopA->id,
            'status' => 'unpaid',
            'payment_method' => null,
            'total_amount' => 77.000,
            'paid_at' => null,
        ]);
        Payment::forceCreate([
            'shop_id' => $shopA->id,
            'order_id' => $unpaid->id,
            'amount' => 77.000,
            'method' => 'cash',
            'paid_at' => now(),
        ]);

        $otherOrder = Order::forceCreate([
            'shop_id' => $shopB->id,
            'status' => 'completed',
            'payment_method' => 'cash',
            'total_amount' => 55.000,
            'paid_at' => now(),
        ]);
        Payment::forceCreate([
            'shop_id' => $shopB->id,
            'order_id' => $otherOrder->id,
            'amount' => 55.000,
            'method' => 'cash',
            'paid_at' => now(),
        ]);

        $managerA = User::factory()->create(['shop_id' => $shopA->id, 'role' => 'manager']);

        Livewire::actingAs($managerA)
            ->test(ReportsDashboard::class)
            ->assertViewHas('totalRevenue', fn ($totalRevenue) => abs($totalRevenue - 10.000) < 0.001)
            ->assertViewHas('paymentSummary', function ($summary) {
                $cash = $summary->firstWhere('payment_method', 'cash');

                return $summary->count() === 1
                    && $cash
                    && abs((float) $cash->total - 10.000) < 0.001
                    && (int) $cash->orders === 1;
            });
    }

    public function test_reports_payment_summary_counts_refund_reversals_inside_the_selected_range(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-25 12:00:00', 'UTC'));
        [$shopA] = $this->makeShops();

        $originalPaidAt = now()->subDays(40);
        $order = Order::forceCreate([
            'shop_id' => $shopA->id,
            'status' => 'cancelled',
            'payment_method' => 'refunded',
            'total_amount' => 10.000,
            'paid_at' => $originalPaidAt,
        ]);
        $original = Payment::forceCreate([
            'shop_id' => $shopA->id,
            'order_id' => $order->id,
            'amount' => 10.000,
            'method' => 'cash',
            'paid_at' => $originalPaidAt,
        ]);
        Payment::forceCreate([
            'shop_id' => $shopA->id,
            'order_id' => $order->id,
            'amount' => -10.000,
            'method' => 'cash',
            'paid_at' => now(),
            'reverses_payment_id' => $original->id,
        ]);

        $managerA = User::factory()->create(['shop_id' => $shopA->id, 'role' => 'manager']);

        Livewire::actingAs($managerA)
            ->test(ReportsDashboard::class)
            ->assertViewHas('totalRevenue', fn ($totalRevenue) => abs((float) $totalRevenue) < 0.001)
            ->assertViewHas('paymentSummary', function ($summary) {
                $cash = $summary->firstWhere('payment_method', 'cash');

                return $summary->count() === 1
                    && $cash
                    && abs((float) $cash->total + 10.000) < 0.001
                    && (int) $cash->orders === 1;
            });
    }

    public function test_reports_export_link_matches_selected_dashboard_range(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-25 12:00:00', 'UTC'));
        [$shopA] = $this->makeShops();
        $managerA = User::factory()->create(['shop_id' => $shopA->id, 'role' => 'manager']);
        $expectedRoute = route('admin.reports.export', [
            'from' => '2026-06-19',
            'to' => '2026-06-25',
        ]);

        Livewire::actingAs($managerA)
            ->test(ReportsDashboard::class)
            ->set('rangeDays', 7)
            ->assertViewHas('exportQuery', fn (array $query): bool => $query === [
                'from' => '2026-06-19',
                'to' => '2026-06-25',
            ])
            ->assertSeeHtml(e($expectedRoute));
    }

    protected function makeShops(): array
    {
        $shopA = Shop::create(['name' => 'Shop A', 'slug' => 'shop-a']);
        $shopB = Shop::create(['name' => 'Shop B', 'slug' => 'shop-b']);
        $shopA->forceFill(['trial_ends_at' => now()->addDays(14)])->save();
        $shopB->forceFill(['trial_ends_at' => now()->addDays(14)])->save();

        return [$shopA, $shopB];
    }
}
