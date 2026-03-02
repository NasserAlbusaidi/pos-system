<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_product_can_be_created(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Coffee']);

        $product = Product::create([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name' => 'Latte',
            'description' => 'Milky coffee',
            'price' => 4.50,
            'is_available' => true,
        ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Latte',
            'shop_id' => $shop->id,
            'price' => 4.50,
        ]);

        $this->assertEquals($category->id, $product->category_id);
    }
}
