<?php

namespace Tests\Feature\Livewire;

use App\Livewire\ProductManager;
use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\Product;
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
        $category = Category::create(['shop_id' => $shop->id, 'name_en' => 'Coffee']);

        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        Livewire::actingAs($user)
            ->test(ProductManager::class)
            ->set('name_en', 'Espresso')
            ->set('price', 2.50)
            ->set('category_id', $category->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'shop_id' => $shop->id,
            'name_en' => 'Espresso',
            'price' => 2.50,
        ]);
    }

    public function test_admin_can_attach_modifiers_to_product(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $category = Category::create(['shop_id' => $shop->id, 'name_en' => 'Coffee']);
        $group = ModifierGroup::create(['shop_id' => $shop->id, 'name_en' => 'Milk']);

        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);

        Livewire::actingAs($user)
            ->test(ProductManager::class)
            ->set('name_en', 'Latte')
            ->set('price', 4.50)
            ->set('category_id', $category->id)
            ->set('selectedModifierGroups', [$group->id])
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::where('name_en', 'Latte')->first();
        $this->assertTrue($product->modifierGroups->contains($group->id));
    }

    public function test_admin_cannot_create_product_in_another_shops_category(): void
    {
        [$shopA, $shopB] = $this->makeShops();
        $categoryB = Category::create(['shop_id' => $shopB->id, 'name_en' => 'Other Coffee']);
        $adminA = User::factory()->create(['shop_id' => $shopA->id, 'role' => 'admin']);

        Livewire::actingAs($adminA)
            ->test(ProductManager::class)
            ->set('name_en', 'Cross Shop Latte')
            ->set('price', 4.500)
            ->set('category_id', $categoryB->id)
            ->call('save')
            ->assertHasErrors(['category_id']);

        $this->assertDatabaseMissing('products', [
            'shop_id' => $shopA->id,
            'name_en' => 'Cross Shop Latte',
        ]);
    }

    public function test_admin_cannot_attach_another_shops_modifier_group_to_product(): void
    {
        [$shopA, $shopB] = $this->makeShops();
        $categoryA = Category::create(['shop_id' => $shopA->id, 'name_en' => 'Coffee']);
        $groupB = ModifierGroup::create(['shop_id' => $shopB->id, 'name_en' => 'Other Extras']);
        $adminA = User::factory()->create(['shop_id' => $shopA->id, 'role' => 'admin']);

        Livewire::actingAs($adminA)
            ->test(ProductManager::class)
            ->set('name_en', 'Safe Latte')
            ->set('price', 4.500)
            ->set('category_id', $categoryA->id)
            ->set('selectedModifierGroups', [$groupB->id])
            ->call('save')
            ->assertHasErrors(['selectedModifierGroups.0']);

        $this->assertDatabaseMissing('products', [
            'shop_id' => $shopA->id,
            'name_en' => 'Safe Latte',
        ]);
    }

    /**
     * @return array{0: Shop, 1: Shop}
     */
    private function makeShops(): array
    {
        return [
            Shop::create(['name' => 'Bite A', 'slug' => 'bite-a']),
            Shop::create(['name' => 'Bite B', 'slug' => 'bite-b']),
        ];
    }
}
