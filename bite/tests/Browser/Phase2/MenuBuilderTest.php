<?php

namespace Tests\Browser\Phase2;

use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class MenuBuilderTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_can_create_category(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/menu-builder')
                ->waitFor('input[wire\\:model="newCategoryNameEn"]')
                ->type('input[wire\\:model="newCategoryNameEn"]', 'Beverages')
                ->type('input[wire\\:model="newCategoryNameAr"]', 'مشروبات')
                ->click('button[wire\\:click="createCategory"]')
                ->waitForText('Beverages')
                ->assertSee('Beverages');
        });

        $this->assertDatabaseHas('categories', [
            'shop_id' => $shop->id,
            'name_en' => 'Beverages',
            'name_ar' => 'مشروبات',
        ]);
    }

    public function test_toggle_product_visibility_hides_from_guest_menu(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, [
            'name_en' => 'Hidden Item',
            'name_ar' => 'عنصر مخفي',
            'is_visible' => true,
        ]);

        $this->browse(function (Browser $browser) use ($admin, $shop, $product) {
            // Verify product is initially visible on guest menu
            $browser->visit('/menu/'.$shop->slug)
                ->waitForText('HIDDEN ITEM')
                ->assertSee('HIDDEN ITEM');

            // Toggle visibility off in menu builder
            $productRow = 'div[data-id="'.$product->id.'"]';
            $visibilityBtn = 'button[wire\\:click="toggleVisibility('.$product->id.')"]';

            $browser->loginAs($admin)
                ->visit('/menu-builder')
                ->waitFor($productRow)
                ->mouseover($productRow)
                ->waitFor($visibilityBtn)
                ->assertSeeIn($visibilityBtn, 'VISIBLE')
                ->click($visibilityBtn)
                ->pause(500)
                ->mouseover($productRow)
                ->waitFor($visibilityBtn)
                ->assertSeeIn($visibilityBtn, 'HIDDEN');

            // Verify product is now hidden on guest menu
            $browser->visit('/menu/'.$shop->slug)
                ->pause(500)
                ->assertDontSee('HIDDEN ITEM');

            // Toggle visibility back on
            $browser->visit('/menu-builder')
                ->waitFor($productRow)
                ->mouseover($productRow)
                ->waitFor($visibilityBtn)
                ->assertSeeIn($visibilityBtn, 'HIDDEN')
                ->click($visibilityBtn)
                ->pause(500)
                ->mouseover($productRow)
                ->waitFor($visibilityBtn)
                ->assertSeeIn($visibilityBtn, 'VISIBLE');

            // Verify product is visible again on guest menu
            $browser->visit('/menu/'.$shop->slug)
                ->waitForText('HIDDEN ITEM')
                ->assertSee('HIDDEN ITEM');
        });
    }
}
