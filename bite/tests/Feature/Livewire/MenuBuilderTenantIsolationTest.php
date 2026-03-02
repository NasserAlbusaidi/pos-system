<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Admin\MenuBuilder;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MenuBuilderTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_assign_product_to_category_from_another_shop(): void
    {
        [$shopA, $shopB] = $this->makeShops();

        $categoryA = Category::create(['shop_id' => $shopA->id, 'name' => 'Coffee']);
        $categoryB = Category::create(['shop_id' => $shopB->id, 'name' => 'Other']);

        $productA = Product::create([
            'shop_id' => $shopA->id,
            'category_id' => $categoryA->id,
            'name' => 'Espresso',
            'price' => 3.50,
        ]);

        $manager = User::factory()->create(['shop_id' => $shopA->id, 'role' => 'manager']);

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($manager)
            ->test(MenuBuilder::class)
            ->call('updateProductCategory', $productA->id, $categoryB->id);
    }

    public function test_cross_shop_sort_payload_ids_are_rejected(): void
    {
        [$shopA, $shopB] = $this->makeShops();

        $categoryA = Category::create(['shop_id' => $shopA->id, 'name' => 'Coffee']);

        Product::create([
            'shop_id' => $shopA->id,
            'category_id' => $categoryA->id,
            'name' => 'Latte',
            'price' => 4.00,
        ]);

        $otherProduct = Product::create([
            'shop_id' => $shopB->id,
            'category_id' => Category::create(['shop_id' => $shopB->id, 'name' => 'Tea'])->id,
            'name' => 'Other Shop Product',
            'price' => 2.00,
        ]);

        $manager = User::factory()->create(['shop_id' => $shopA->id, 'role' => 'manager']);

        Livewire::actingAs($manager)
            ->test(MenuBuilder::class)
            ->call('updateOrder', [
                ['value' => $otherProduct->id, 'order' => 1],
            ])
            ->assertStatus(422);
    }

    protected function makeShops(): array
    {
        $shopA = Shop::create(['name' => 'Bite A', 'slug' => 'bite-a']);
        $shopB = Shop::create(['name' => 'Bite B', 'slug' => 'bite-b']);

        return [$shopA, $shopB];
    }
}
