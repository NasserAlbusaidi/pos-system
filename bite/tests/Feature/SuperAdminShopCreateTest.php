<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\Shops\Manage;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SuperAdminShopCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_shop_page_loads()
    {
        $admin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($admin)->get(route('super-admin.shops.create'));

        $response->assertStatus(200);
        $response->assertSee('Onboard New Shop');
    }

    public function test_can_create_new_shop()
    {
        $admin = User::factory()->superAdmin()->create();

        Livewire::actingAs($admin)
            ->test(Manage::class)
            ->set('name', 'New Coffee Shop')
            ->set('status', 'active')
            ->set('ownerName', 'John Doe')
            ->set('ownerEmail', 'john@example.com')
            ->set('ownerPassword', 'password123')
            ->call('save')
            ->assertRedirect(route('super-admin.shops.index'));

        $this->assertDatabaseHas('shops', [
            'name' => 'New Coffee Shop',
            'slug' => 'new-coffee-shop', // auto-generated
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'admin',
        ]);
    }

    public function test_can_edit_existing_shop()
    {
        $admin = User::factory()->superAdmin()->create();
        $shop = Shop::factory()->create(['name' => 'Old Name']);

        Livewire::actingAs($admin)
            ->test(Manage::class, ['shop' => $shop])
            ->set('name', 'New Name')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('super-admin.shops.index'));

        $this->assertDatabaseHas('shops', [
            'id' => $shop->id,
            'name' => 'New Name',
        ]);
    }
}
