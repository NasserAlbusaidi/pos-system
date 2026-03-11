<?php

namespace Tests\Feature\Livewire;

use App\Livewire\ShopDashboard;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShopDashboardTest extends TestCase
{
    use RefreshDatabase;

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
            ->assertSet('ordersTodayCount', 2);
    }
}
