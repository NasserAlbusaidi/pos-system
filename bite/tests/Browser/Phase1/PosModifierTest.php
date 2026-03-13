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
        [$group, $option] = $this->createModifierGroup($shop, $product, required: true);

        $this->browse(function (Browser $browser) use ($shop, $product) {
            $browser->visit('/menu/' . $shop->slug)
                ->waitFor('button[wire\\:click="addToCart(' . $product->id . ')"]')
                ->click('button[wire\\:click="addToCart(' . $product->id . ')"]')
                ->waitForText('SIZE')
                ->assertSee('LARGE')
                ->assertSee('REQUIRED')
                ->assertSee('+OMR 1.000');
        });
    }

    public function test_required_modifier_must_be_selected_before_adding(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, [
            'name_en' => 'Mocha',
            'price' => 2.500,
        ]);
        [$group, $option] = $this->createModifierGroup($shop, $product, required: true);

        $this->browse(function (Browser $browser) use ($shop, $product) {
            $browser->visit('/menu/' . $shop->slug)
                ->waitFor('button[wire\\:click="addToCart(' . $product->id . ')"]')
                ->click('button[wire\\:click="addToCart(' . $product->id . ')"]')
                ->waitForText('SIZE')
                // Try to add without selecting the required modifier
                ->click('button[wire\\:click="addToCart(' . $product->id . ')"]')
                ->waitForText('SELECT AT LEAST')
                ->assertSee('SELECT AT LEAST');
        });
    }

    public function test_modifier_price_reflected_in_modal(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, [
            'name_en' => 'Espresso',
            'price' => 1.500,
        ]);
        [$group, $option] = $this->createModifierGroup($shop, $product, required: false);

        $this->browse(function (Browser $browser) use ($shop, $product, $group, $option) {
            $browser->visit('/menu/' . $shop->slug)
                ->waitFor('button[wire\\:click="addToCart(' . $product->id . ')"]')
                ->click('button[wire\\:click="addToCart(' . $product->id . ')"]')
                ->waitForText('SIZE')
                ->assertSee('OPTIONAL')
                // Modal header shows base price initially: OMR 1.500
                ->assertSee('OMR 1.500')
                // Select the Large modifier option (radio button)
                ->click('input[wire\\:model\\.live="selectedModifiers.' . $group->id . '"][value="' . $option->id . '"]')
                // Price in modal should update to 1.500 + 1.000 = 2.500
                ->waitForText('OMR 2.500')
                ->assertSee('OMR 2.500');
        });
    }
}
