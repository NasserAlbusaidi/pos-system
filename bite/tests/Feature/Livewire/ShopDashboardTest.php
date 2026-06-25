<?php

namespace Tests\Feature\Livewire;

use App\Livewire\ShopDashboard;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ShopDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dashboard_shows_real_time_stats(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = User::factory()->create(['shop_id' => $shop->id]);

        // Create a completed order today
        Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'completed',
            'total_amount' => 50.00,
            'paid_at' => now(),
        ]);

        // Create an unpaid order today
        Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'total_amount' => 20.00,
        ]);

        // 3. Create an order from yesterday (should not be counted in daily)
        $yesterdayOrder = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'completed',
            'total_amount' => 100.00,
            'paid_at' => now()->subDay(),
            'created_at' => now()->subDay(),
        ]);

        Livewire::actingAs($user)
            ->test(ShopDashboard::class)
            ->assertSet('dailyRevenue', 50.00)
            ->assertSet('ordersTodayCount', 2)
            // Yesterday's completed revenue is tracked for the hero "vs yesterday" delta.
            ->assertSet('yesterdayRevenue', 100.00)
            // today 50 vs yesterday 100 → -50%
            ->assertSet('revenueDelta', -50);
    }

    public function test_dashboard_counts_paid_in_progress_orders_as_revenue(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = User::factory()->create(['shop_id' => $shop->id]);

        foreach (['paid', 'preparing', 'ready', 'completed'] as $index => $status) {
            Order::forceCreate([
                'shop_id' => $shop->id,
                'status' => $status,
                'total_amount' => 10.000 + $index,
                'paid_at' => now(),
            ]);
        }

        Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'total_amount' => 77.000,
            'paid_at' => null,
        ]);

        Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'cancelled',
            'total_amount' => 88.000,
            'paid_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(ShopDashboard::class)
            ->assertSet('dailyRevenue', 46.0)
            ->assertSet('avgOrderValue', 11.5);
    }

    public function test_revenue_delta_is_null_when_no_yesterday_revenue(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = User::factory()->create(['shop_id' => $shop->id]);

        Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'completed',
            'total_amount' => 50.00,
            'paid_at' => now(),
        ]);

        // No yesterday revenue → delta is undefined, never a fabricated number.
        Livewire::actingAs($user)
            ->test(ShopDashboard::class)
            ->assertSet('yesterdayRevenue', 0.0)
            ->assertSet('revenueDelta', null);
    }

    public function test_payment_summary_uses_payment_ledger_for_split_tenders(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = User::factory()->create(['shop_id' => $shop->id]);

        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'paid',
            'payment_method' => 'split',
            'total_amount' => 10.000,
            'paid_at' => now(),
        ]);

        Payment::forceCreate([
            'shop_id' => $shop->id,
            'order_id' => $order->id,
            'amount' => 6.000,
            'method' => 'cash',
            'paid_at' => now(),
        ]);
        Payment::forceCreate([
            'shop_id' => $shop->id,
            'order_id' => $order->id,
            'amount' => 4.000,
            'method' => 'card',
            'paid_at' => now(),
        ]);

        $otherShop = Shop::create(['name' => 'Other', 'slug' => 'other']);
        $otherOrder = Order::forceCreate([
            'shop_id' => $otherShop->id,
            'status' => 'paid',
            'payment_method' => 'cash',
            'total_amount' => 99.000,
            'paid_at' => now(),
        ]);
        Payment::forceCreate([
            'shop_id' => $otherShop->id,
            'order_id' => $otherOrder->id,
            'amount' => 99.000,
            'method' => 'cash',
            'paid_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(ShopDashboard::class)
            ->assertSet('paymentSummary.cash.total', 6.0)
            ->assertSet('paymentSummary.cash.orders', 1)
            ->assertSet('paymentSummary.card.total', 4.0)
            ->assertSet('paymentSummary.card.orders', 1)
            ->assertSet('paymentSummary.split', null);
    }

    public function test_payment_summary_counts_refunds_on_the_day_the_reversal_happens(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-25 12:00:00', 'UTC'));

        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = User::factory()->create(['shop_id' => $shop->id]);
        $yesterday = Carbon::parse('2026-06-24 10:00:00', 'UTC');

        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'cancelled',
            'payment_method' => 'refunded',
            'total_amount' => 10.000,
            'paid_at' => $yesterday,
        ]);
        $original = Payment::forceCreate([
            'shop_id' => $shop->id,
            'order_id' => $order->id,
            'amount' => 10.000,
            'method' => 'cash',
            'paid_at' => $yesterday,
        ]);
        Payment::forceCreate([
            'shop_id' => $shop->id,
            'order_id' => $order->id,
            'amount' => -10.000,
            'method' => 'cash',
            'paid_at' => now(),
            'reverses_payment_id' => $original->id,
        ]);

        Livewire::actingAs($user)
            ->test(ShopDashboard::class)
            ->assertSet('dailyRevenue', 0.0)
            ->assertSet('paymentSummary.cash.total', -10.0)
            ->assertSet('paymentSummary.cash.orders', 1);
    }

    public function test_heatmap_currency_symbol_is_javascript_encoded(): void
    {
        $shop = Shop::create([
            'name' => 'Bite',
            'slug' => 'bite',
            'currency_symbol' => "');x=1;//",
        ]);
        $user = User::factory()->create(['shop_id' => $shop->id]);

        $html = Livewire::actingAs($user)
            ->test(ShopDashboard::class)
            ->html();

        $this->assertStringNotContainsString(
            "revenueHeatmap([], '&#039;);x=1;//')",
            $html
        );
    }

    public function test_server_cannot_update_daily_goal(): void
    {
        $shop = Shop::create([
            'name' => 'Bite',
            'slug' => 'bite',
            'branding' => ['daily_goal' => 25.000],
        ]);
        $server = User::factory()->create(['shop_id' => $shop->id, 'role' => 'server']);

        Livewire::actingAs($server)
            ->test(ShopDashboard::class)
            ->call('setDailyGoal', 100.000)
            ->assertForbidden();

        $this->assertEqualsWithDelta(25.000, (float) $shop->fresh()->branding['daily_goal'], 0.0001);
    }

    public function test_manager_can_update_daily_goal(): void
    {
        $shop = Shop::create([
            'name' => 'Bite',
            'slug' => 'bite',
            'branding' => ['daily_goal' => 25.000],
        ]);
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);

        Livewire::actingAs($manager)
            ->test(ShopDashboard::class)
            ->call('setDailyGoal', 100.000)
            ->assertOk();

        $this->assertEqualsWithDelta(100.000, (float) $shop->fresh()->branding['daily_goal'], 0.0001);
    }
}
