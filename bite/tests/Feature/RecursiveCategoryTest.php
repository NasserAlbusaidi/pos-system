<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecursiveCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_can_be_nested(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);

        $parent = Category::create([
            'shop_id' => $shop->id,
            'name_en' => 'Drinks',
        ]);

        $child = Category::create([
            'shop_id' => $shop->id,
            'name_en' => 'Hot Coffee',
            'parent_id' => $parent->id,
        ]);

        $this->assertDatabaseHas('categories', [
            'id' => $child->id,
            'parent_id' => $parent->id,
        ]);

        $this->assertTrue($parent->children->contains($child));
        $this->assertEquals($parent->id, $child->parent->id);
    }
}
