<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_modifier_group_can_be_created(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);

        $group = ModifierGroup::create([
            'shop_id' => $shop->id,
            'name' => 'Milk Options',
            'min_selection' => 1,
            'max_selection' => 1,
        ]);

        $this->assertDatabaseHas('modifier_groups', [
            'shop_id' => $shop->id,
            'name' => 'Milk Options',
            'min_selection' => 1,
        ]);

        $this->assertEquals($shop->id, $group->shop_id);
    }

    public function test_modifier_option_can_be_created(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $group = ModifierGroup::create(['shop_id' => $shop->id, 'name' => 'Milk']);

        $option = ModifierOption::create([
            'modifier_group_id' => $group->id,
            'name' => 'Oat Milk',
            'price_adjustment' => 1.00,
        ]);

        $this->assertDatabaseHas('modifier_options', [
            'modifier_group_id' => $group->id,
            'name' => 'Oat Milk',
            'price_adjustment' => 1.00,
        ]);
    }

    public function test_product_can_have_modifiers(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Coffee']);
        $product = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name' => 'Latte',
            'price' => 4.50,
        ]);

        $group = ModifierGroup::create(['shop_id' => $shop->id, 'name' => 'Milk']);

        // Attach
        $product->modifierGroups()->attach($group->id);

        $this->assertDatabaseHas('product_modifier_group', [
            'product_id' => $product->id,
            'modifier_group_id' => $group->id,
        ]);

        $this->assertTrue($product->fresh()->modifierGroups->contains($group->id));
    }
}
