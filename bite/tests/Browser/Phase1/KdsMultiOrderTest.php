<?php

namespace Tests\Browser\Phase1;

use App\Models\Product;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class KdsMultiOrderTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_kds_shows_multiple_orders_at_different_statuses(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        $kitchen = $this->createStaffUser($shop, 'kitchen');

        [$cat, $product1] = $this->createProductWithCategory($shop, ['name_en' => 'Coffee']);
        $product2 = Product::factory()->create([
            'shop_id' => $shop->id,
            'category_id' => $cat->id,
            'name_en' => 'Tea',
            'name_ar' => 'شاي',
            'price' => 1.500,
            'is_available' => true,
            'is_visible' => true,
        ]);

        // Order 1: paid status (shows on KDS)
        $this->createPaidOrder($shop, $product1);

        // Order 2: preparing status (shows on KDS)
        $order2 = $this->createPaidOrder($shop, $product2);
        $order2->update(['status' => 'preparing']);

        $this->browse(function (Browser $browser) use ($kitchen) {
            $browser->loginAs($kitchen)
                ->visit('/kds')
                // Both orders visible (product names are CSS uppercase)
                ->waitForText('COFFEE')
                ->assertSee('COFFEE')
                ->assertSee('TEA');
        });
    }

    public function test_transitioning_one_order_does_not_affect_others(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        $kitchen = $this->createStaffUser($shop, 'kitchen');
        [$cat, $product] = $this->createProductWithCategory($shop, ['name_en' => 'Item A']);

        $order1 = $this->createPaidOrder($shop, $product);
        $order2 = $this->createPaidOrder($shop, $product);

        $this->browse(function (Browser $browser) use ($kitchen, $order1) {
            $browser->loginAs($kitchen)
                ->visit('/kds')
                ->waitFor('button[wire\\:click="updateStatus('.$order1->id.', \'preparing\')"]')
                // Transition only order1 to preparing
                ->click('button[wire\\:click="updateStatus('.$order1->id.', \'preparing\')"]')
                // Wait for the button to change to "Order Ready" for order1
                ->waitFor('button[wire\\:click="updateStatus('.$order1->id.', \'ready\')"]');
        });

        $order1->refresh();
        $order2->refresh();
        $this->assertEquals('preparing', $order1->status);
        $this->assertEquals('paid', $order2->status);
    }
}
