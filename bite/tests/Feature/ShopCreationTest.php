<?php

namespace Tests\Feature;

use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_shop_can_be_created(): void
    {
        $shop = Shop::create([
            'name' => 'Bite Coffee',
            'slug' => 'bite-coffee',
        ]);

        $this->assertDatabaseHas('shops', [
            'name' => 'Bite Coffee',
            'slug' => 'bite-coffee',
        ]);

        $this->assertEquals('Bite Coffee', $shop->name);
    }
}
