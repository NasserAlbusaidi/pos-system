<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminShopsIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_shops_index_page_loads_with_shop_data()
    {
        $admin = User::factory()->superAdmin()->create();

        // Create a shop with an owner to trigger the $shop->users()->first() logic in the view
        $shop = Shop::factory()->create();
        $owner = User::factory()->create(['shop_id' => $shop->id]);

        $response = $this->actingAs($admin)->get(route('super-admin.shops.index'));

        $response->assertStatus(200);
        $response->assertSee($shop->name);
        $response->assertSee('Login As Owner');
    }
}
