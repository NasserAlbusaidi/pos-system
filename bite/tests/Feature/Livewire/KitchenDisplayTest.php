<?php

namespace Tests\Feature\Livewire;

use App\Livewire\KitchenDisplay;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KitchenDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_kitchen_sees_paid_orders_only(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'kitchen']);

        $paidOrder = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'paid',
            'total_amount' => 15.00,
        ]);

        $unpaidOrder = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'total_amount' => 10.00,
        ]);

        Livewire::actingAs($user)
            ->test(KitchenDisplay::class)
            ->assertSee('Ticket_'.$paidOrder->id)
            ->assertDontSee('Ticket_'.$unpaidOrder->id);
    }

    public function test_kitchen_can_update_order_status(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'kitchen']);

        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'paid',
            'total_amount' => 15.00,
        ]);

        Livewire::actingAs($user)
            ->test(KitchenDisplay::class)
            ->call('updateStatus', $order->id, 'preparing');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'preparing',
        ]);
    }
}
