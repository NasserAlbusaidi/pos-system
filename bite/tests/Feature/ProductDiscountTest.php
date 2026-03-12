<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDiscountTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_calculates_discounted_price(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $category = Category::create(['shop_id' => $shop->id, 'name_en' => 'Coffee']);

        // 1. Percentage Discount
        $productPercent = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Latte',
            'price' => 10.00,
            'discount_value' => 20,
            'discount_type' => 'percentage',
            'is_on_sale' => true,
        ]);

        $this->assertEquals(8.00, $productPercent->final_price);

        // 2. Fixed Discount
        $productFixed = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Cake',
            'price' => 10.00,
            'discount_value' => 2.00,
            'discount_type' => 'fixed',
            'is_on_sale' => true,
        ]);

        $this->assertEquals(8.00, $productFixed->final_price);

        // 3. Sale Inactive
        $productInactive = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Water',
            'price' => 5.00,
            'discount_value' => 1.00,
            'discount_type' => 'fixed',
            'is_on_sale' => false,
        ]);

        $this->assertEquals(5.00, $productInactive->final_price);
    }
}
