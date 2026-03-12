<?php

namespace Tests\Feature;

use App\Livewire\Admin\MenuBuilder;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_builder_reorder_product(): void
    {
        $shop = Shop::factory()->create();
        $user = User::factory()->create(['shop_id' => $shop->id]);
        $category1 = Category::factory()->create(['shop_id' => $shop->id, 'name_en' => 'Cat 1', 'sort_order' => 1]);
        $category2 = Category::factory()->create(['shop_id' => $shop->id, 'name_en' => 'Cat 2', 'sort_order' => 2]);
        $product = Product::factory()->create(['shop_id' => $shop->id, 'category_id' => $category1->id]);

        $items = [
            ['value' => $product->id, 'order' => 0],
        ];

        Livewire::actingAs($user)
            ->test(MenuBuilder::class)
            ->call('reorderProduct', $product->id, $category2->id, $items);

        $this->assertEquals($category2->id, $product->fresh()->category_id);
    }

    public function test_floor_planner_route_is_removed(): void
    {
        $shop = Shop::factory()->create();
        $user = User::factory()->create(['shop_id' => $shop->id]);

        $this->actingAs($user)
            ->get('/floor-planner')
            ->assertNotFound();
    }
}
