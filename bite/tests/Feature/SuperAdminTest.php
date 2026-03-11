<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_dashboard()
    {
        $admin = User::factory()->superAdmin()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('super-admin.dashboard'));

        $response->assertStatus(200);
    }

    public function test_non_super_admin_cannot_access_dashboard()
    {
        $user = User::factory()->create(['is_super_admin' => false]);

        $response = $this->actingAs($user)->get(route('super-admin.dashboard'));

        $response->assertStatus(403);
    }

    public function test_super_admin_can_create_shop()
    {
        $admin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($admin)->get(route('super-admin.shops.create'));
        $response->assertStatus(200);
    }

    public function test_impersonation()
    {
        $admin = User::factory()->superAdmin()->create();
        $shop = Shop::factory()->create();
        $targetUser = User::factory()->create(['shop_id' => $shop->id]);

        $response = $this->actingAs($admin)->post(route('super-admin.impersonate', $targetUser->id));

        $response->assertRedirect(route('dashboard'));
        $this->assertEquals($targetUser->id, auth()->id());
        $this->assertEquals($admin->id, session('impersonator_id'));
    }

    public function test_cannot_impersonate_super_admin()
    {
        $admin = User::factory()->superAdmin()->create();
        $otherSuperAdmin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($admin)->post(route('super-admin.impersonate', $otherSuperAdmin->id));

        $response->assertStatus(403);
    }

    public function test_impersonation_requires_post()
    {
        $admin = User::factory()->superAdmin()->create();
        $shop = Shop::factory()->create();
        $targetUser = User::factory()->create(['shop_id' => $shop->id]);

        $response = $this->actingAs($admin)->get(route('super-admin.impersonate', $targetUser->id));

        $response->assertStatus(405); // Method Not Allowed
    }
}
