<?php

namespace Tests\Feature\Livewire;

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

    public function test_admin_can_create_product_via_livewire(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Coffee']);

        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        Livewire::actingAs($user)
            ->test(ProductManager::class)
            ->set('name', 'Espresso')
            ->set('price', 2.50)
            ->set('category_id', $category->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'shop_id' => $shop->id,
            'name' => 'Espresso',
            'price' => 2.50,
        ]);
    }

    public function test_admin_can_attach_modifiers_to_product(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Coffee']);
        $group = \App\Models\ModifierGroup::create(['shop_id' => $shop->id, 'name' => 'Milk']);

        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        Livewire::actingAs($user)
            ->test(\App\Livewire\ProductManager::class)
            ->set('name', 'Latte')
            ->set('price', 4.50)
            ->set('category_id', $category->id)
            ->set('selectedModifierGroups', [$group->id])
            ->call('save')
            ->assertHasNoErrors();

        $product = \App\Models\Product::where('name', 'Latte')->first();
        $this->assertTrue($product->modifierGroups->contains($group->id));
    }
}
