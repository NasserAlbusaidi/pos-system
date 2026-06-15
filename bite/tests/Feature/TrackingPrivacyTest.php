<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TrackingPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_based_tracking_route_works_for_valid_token(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);

        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'total_amount' => 12.50,
            'subtotal_amount' => 12.50,
            'tax_amount' => 0,
            'tracking_token' => (string) Str::uuid(),
        ]);

        // Re-skin (screen 7, #25) replaces the "Order #" header with a counter
        // code card. The prefix is tenant-derived (first 2 slug chars,
        // uppercased) so each shop gets its own code — slug 'bite' => 'BI-'.
        $prefix = strtoupper(substr($shop->slug, 0, 2));
        $this->get(route('guest.track', $order->tracking_token))
            ->assertOk()
            ->assertSee($prefix.'-'.str_pad((string) $order->id, 3, '0', STR_PAD_LEFT));
    }

    public function test_numeric_tracking_endpoint_is_not_accessible(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);

        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'total_amount' => 9.99,
            'subtotal_amount' => 9.99,
            'tax_amount' => 0,
        ]);

        $this->get('/track/'.$order->id)->assertNotFound();
    }
}
