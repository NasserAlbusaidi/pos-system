<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_belongs_to_a_shop(): void
    {
        $shop = Shop::create([
            'name' => 'Bite Demo',
            'slug' => 'bite-demo',
        ]);

        $user = User::factory()->create([
            'shop_id' => $shop->id,
            'name' => 'Nasser',
            'email' => 'nasser@bite.com',
            'role' => 'admin', // We'll add this column too per spec
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'nasser@bite.com',
            'shop_id' => $shop->id,
            'role' => 'admin',
        ]);

        $this->assertEquals($shop->id, $user->shop_id);
    }
}
