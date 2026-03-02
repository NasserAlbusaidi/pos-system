<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacRouteAccessTest extends TestCase
{
    use RefreshDatabase;

    protected Shop $shop;

    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $this->order = Order::create([
            'shop_id' => $this->shop->id,
            'status' => 'paid',
            'total_amount' => 10,
            'subtotal_amount' => 10,
            'tax_amount' => 0,
        ]);
    }

    public function test_server_is_blocked_from_manager_and_kitchen_routes(): void
    {
        $server = $this->makeUser('server');

        $this->actingAs($server)->get('/pos')->assertOk();
        $this->actingAs($server)->get(route('admin.orders.invoice', $this->order))->assertOk();

        $this->actingAs($server)->get('/kds')->assertForbidden();
        $this->actingAs($server)->get('/products')->assertForbidden();
    }

    public function test_kitchen_is_blocked_from_pos_and_admin_routes(): void
    {
        $kitchen = $this->makeUser('kitchen');

        $this->actingAs($kitchen)->get('/kds')->assertOk();
        $this->actingAs($kitchen)->get('/pos')->assertForbidden();
        $this->actingAs($kitchen)->get('/products')->assertForbidden();
    }

    public function test_manager_can_access_pos_kds_and_admin_modules(): void
    {
        $manager = $this->makeUser('manager');

        $this->actingAs($manager)->get('/pos')->assertOk();
        $this->actingAs($manager)->get('/kds')->assertOk();
        $this->actingAs($manager)->get('/products')->assertOk();
        $this->actingAs($manager)->get('/menu-builder')->assertOk();
    }

    public function test_admin_can_access_pos_kds_and_admin_modules(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get('/pos')->assertOk();
        $this->actingAs($admin)->get('/kds')->assertOk();
        $this->actingAs($admin)->get('/products')->assertOk();
        $this->actingAs($admin)->get('/menu-builder')->assertOk();
    }

    protected function makeUser(string $role): User
    {
        return User::factory()->create([
            'shop_id' => $this->shop->id,
            'role' => $role,
        ]);
    }
}
