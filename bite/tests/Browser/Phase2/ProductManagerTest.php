<?php

namespace Tests\Browser\Phase2;

use App\Models\Category;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class ProductManagerTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_can_create_product(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        $category = Category::factory()->create([
            'shop_id' => $shop->id,
            'name_en' => 'Drinks',
            'name_ar' => 'مشروبات',
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($admin, $category) {
            $browser->loginAs($admin)
                ->visit('/products')
                ->waitForText('Add New Product')
                // Fill the product form
                ->type('input[wire\\:model="name_en"]', 'Mocha')
                ->type('input[wire\\:model="name_ar"]', 'موكا')
                ->type('input[wire\\:model="price"]', '4.500')
                ->select('select[wire\\:model="category_id"]', $category->id)
                ->click('button[type="submit"]')
                // After save, Livewire re-renders the product list — product name is uppercased via CSS
                ->waitForText('MOCHA')
                ->assertSee('MOCHA');
        });

        $this->assertDatabaseHas('products', [
            'shop_id' => $admin->shop_id,
            'name_en' => 'Mocha',
            'name_ar' => 'موكا',
        ]);
    }

    public function test_can_edit_product(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, [
            'name_en' => 'Old Latte',
            'name_ar' => 'لاتيه قديم',
            'price' => 3.000,
        ]);

        $this->browse(function (Browser $browser) use ($admin, $product) {
            $browser->loginAs($admin)
                ->visit('/products')
                // Product names are uppercased via CSS text-transform in the list
                ->waitForText('OLD LATTE')
                // Click the Edit button for this product
                ->click('button[wire\\:click="editProduct('.$product->id.')"]')
                ->waitForText('Edit Product')
                // Clear and fill new values
                ->clear('input[wire\\:model="name_en"]')
                ->type('input[wire\\:model="name_en"]', 'New Latte')
                ->clear('input[wire\\:model="price"]')
                ->type('input[wire\\:model="price"]', '5.500')
                ->click('button[type="submit"]')
                // After update, the product list re-renders with the new name (uppercased via CSS)
                ->waitForText('NEW LATTE')
                ->assertSee('NEW LATTE');
        });

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name_en' => 'New Latte',
        ]);
    }
}
