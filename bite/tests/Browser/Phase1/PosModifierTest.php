<?php

namespace Tests\Browser\Phase1;

use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class PosModifierTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_product_with_required_modifier_shows_modal(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, [
            'name_en' => 'Latte',
            'price' => 2.000,
        ]);
        $this->createAdditionalProduct($shop, $category);
        [$group, $option] = $this->createModifierGroup($shop, $product, required: true);

        $this->browse(function (Browser $browser) use ($shop, $product) {
            $browser->visit('/menu/'.$shop->slug)
                ->tap(fn (Browser $browser) => $this->enterGuestMenu($browser))
                ->waitFor($this->quickAddSelector($product->id))
                ->click($this->quickAddSelector($product->id))
                ->waitForText('Size')
                ->assertSee('Large')
                ->assertSee('REQUIRED')
                ->assertSee('1.000');
        });
    }

    public function test_required_modifier_must_be_selected_before_adding(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, [
            'name_en' => 'Mocha',
            'price' => 2.500,
        ]);
        $this->createAdditionalProduct($shop, $category);
        [$group, $option] = $this->createModifierGroup($shop, $product, required: true);

        $this->browse(function (Browser $browser) use ($shop, $product) {
            $browser->visit('/menu/'.$shop->slug)
                ->tap(fn (Browser $browser) => $this->enterGuestMenu($browser))
                ->waitFor($this->quickAddSelector($product->id))
                ->click($this->quickAddSelector($product->id))
                ->waitForText('Size')
                // Try to add without selecting the required modifier
                ->click('button[wire\\:click="addToCart('.$product->id.')"]')
                ->waitForText('Select at least')
                ->assertSee('Select at least');
        });
    }

    public function test_modifier_price_reflected_in_modal(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, [
            'name_en' => 'Espresso',
            'price' => 1.500,
        ]);
        $this->createAdditionalProduct($shop, $category);
        [$group, $option] = $this->createModifierGroup($shop, $product, required: false);

        $this->browse(function (Browser $browser) use ($shop, $product, $group, $option) {
            $browser->visit('/menu/'.$shop->slug)
                ->tap(fn (Browser $browser) => $this->enterGuestMenu($browser))
                ->waitFor($this->quickAddSelector($product->id))
                ->click($this->quickAddSelector($product->id))
                ->waitForText('Size')
                ->assertSee('OPTIONAL')
                // Modal header shows base price initially.
                ->assertSee('1.500')
                // Select the Large modifier option.
                ->click('button[wire\\:click="selectModifier('.$group->id.', '.$option->id.', false)"]')
                // Price in modal should update to 1.500 + 1.000 = 2.500
                ->waitForText('2.500')
                ->assertSee('2.500');
        });
    }
}
