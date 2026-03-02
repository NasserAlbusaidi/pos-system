<?php

namespace Tests\Feature;

use App\Livewire\ProductManager;
use App\Models\Category;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_product()
    {
        $shop = Shop::factory()->create();
        $user = User::factory()->create(['shop_id' => $shop->id]);
        $category = Category::factory()->create(['shop_id' => $shop->id]);

        Livewire::actingAs($user)
            ->test(ProductManager::class)
            ->set('name', 'Latte')
            ->set('price', 4.50)
            ->set('category_id', $category->id)
            ->call('save')
            ->assertSet('name', null); // Resets after save

        $this->assertDatabaseHas('products', [
            'name' => 'Latte',
            'price' => 4.50,
            'shop_id' => $shop->id,
        ]);
    }
}
