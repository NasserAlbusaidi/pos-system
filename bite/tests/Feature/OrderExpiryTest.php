<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderExpiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_orders_are_cancelled_automatically(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);

        // 1. Create an order that expired 1 minute ago
        $expiredOrder = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'total_amount' => 10.00,
            'expires_at' => now()->subMinute(),
        ]);

        // 2. Create a fresh order
        $activeOrder = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'total_amount' => 10.00,
            'expires_at' => now()->addMinutes(5),
        ]);

        // 3. Trigger the cleanup logic (we'll implement this as a static method for now)
        Order::cancelExpired();

        $this->assertEquals('cancelled', $expiredOrder->fresh()->status);
        $this->assertEquals('unpaid', $activeOrder->fresh()->status);
    }
}
