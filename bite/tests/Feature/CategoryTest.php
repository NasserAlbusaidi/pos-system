<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_category_can_be_created_for_a_shop(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);

        $category = Category::create([
            'shop_id' => $shop->id,
            'name_en' => 'Coffee',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('categories', [
            'shop_id' => $shop->id,
            'name_en' => 'Coffee',
        ]);

        $this->assertEquals('Coffee', $category->name_en);
    }
}
