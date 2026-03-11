<?php

namespace Tests\Feature;

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

    public function test_loads_dashboard_with_correct_stats()
    {
        $shop = Shop::factory()->create();
        $user = User::factory()->create(['shop_id' => $shop->id]);

        // Create some orders
        Order::forceCreate([
            'shop_id' => $shop->id,
            'total_amount' => 100.00,
            'status' => 'completed',
            'paid_at' => now(),
            'created_at' => now(),
        ]);

        Order::forceCreate([
            'shop_id' => $shop->id,
            'total_amount' => 50.00,
            'status' => 'preparing',
            'paid_at' => now(),
            'created_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(ShopDashboard::class)
            ->assertSee('100.00') // Daily Revenue (only completed)
            ->assertSee('2') // Orders Count (all today)
            ->assertSee('Active Orders')
            ->assertSee('1'); // Active orders (non-completed/cancelled)
    }
}
