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
        $this->shop->trial_ends_at = now()->addDays(14);
        $this->shop->save();
        $this->order = Order::forceCreate([
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

    public function test_server_navigation_only_shows_server_accessible_modules(): void
    {
        $server = $this->makeUser('server');

        $response = $this->actingAs($server)->get('/pos');

        $response
            ->assertOk()
            ->assertSeeText('Dashboard')
            ->assertSeeText('POS Register')
            ->assertDontSeeText('Kitchen Display')
            ->assertDontSeeText('Reports')
            ->assertDontSeeText('Shift Report')
            ->assertDontSeeText('Audit Logs')
            ->assertDontSeeText('Menu Builder')
            ->assertDontSeeText('Product Catalog')
            ->assertDontSeeText('Modifiers')
            ->assertDontSeeText('Pricing Rules')
            ->assertDontSeeText('Settings')
            ->assertDontSeeText('Billing');
    }

    public function test_kitchen_is_blocked_from_pos_and_admin_routes(): void
    {
        $kitchen = $this->makeUser('kitchen');

        $this->actingAs($kitchen)->get('/kds')->assertOk();
        $this->actingAs($kitchen)->get('/pos')->assertForbidden();
        $this->actingAs($kitchen)->get('/products')->assertForbidden();
    }

    public function test_kitchen_navigation_only_shows_kitchen_accessible_modules(): void
    {
        $kitchen = $this->makeUser('kitchen');

        $response = $this->actingAs($kitchen)->get('/kds');

        $response
            ->assertOk()
            ->assertSeeText('Kitchen Display')
            ->assertDontSeeText('Dashboard')
            ->assertDontSeeText('POS Register')
            ->assertDontSeeText('Reports')
            ->assertDontSeeText('Shift Report')
            ->assertDontSeeText('Audit Logs')
            ->assertDontSeeText('Menu Builder')
            ->assertDontSeeText('Product Catalog')
            ->assertDontSeeText('Modifiers')
            ->assertDontSeeText('Pricing Rules')
            ->assertDontSeeText('Settings')
            ->assertDontSeeText('Billing');
    }

    public function test_manager_can_access_pos_kds_and_admin_modules(): void
    {
        $manager = $this->makeUser('manager');

        $this->actingAs($manager)->get('/pos')->assertOk();
        $this->actingAs($manager)->get('/kds')->assertOk();
        $this->actingAs($manager)->get('/products')->assertOk();
        $this->actingAs($manager)->get('/menu-builder')->assertOk();
    }

    public function test_manager_navigation_shows_operational_and_admin_modules_except_billing(): void
    {
        $manager = $this->makeUser('manager');

        $response = $this->actingAs($manager)->get('/pos');

        $response
            ->assertOk()
            ->assertSeeText('Dashboard')
            ->assertSeeText('POS Register')
            ->assertSeeText('Kitchen Display')
            ->assertSeeText('Reports')
            ->assertSeeText('Shift Report')
            ->assertSeeText('Audit Logs')
            ->assertSeeText('Menu Builder')
            ->assertSeeText('Product Catalog')
            ->assertSeeText('Modifiers')
            ->assertSeeText('Pricing Rules')
            ->assertSeeText('Settings')
            ->assertDontSeeText('Billing');
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
