<?php

namespace Tests\Browser\Phase1;

use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class KdsLifecycleTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_kitchen_user_sees_paid_orders(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        $kitchen = $this->createStaffUser($shop, 'kitchen');
        [$category, $product] = $this->createProductWithCategory($shop, ['name_en' => 'Burger']);
        $this->createPaidOrder($shop, $product);

        $this->browse(function (Browser $browser) use ($kitchen) {
            $browser->loginAs($kitchen)
                ->visit('/kds')
                // Product names are CSS uppercase
                ->waitForText('BURGER')
                ->assertSee('BURGER');
        });
    }

    public function test_order_transitions_through_full_lifecycle(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        $kitchen = $this->createStaffUser($shop, 'kitchen');
        [$category, $product] = $this->createProductWithCategory($shop, ['name_en' => 'Sandwich']);
        $order = $this->createPaidOrder($shop, $product);

        $this->browse(function (Browser $browser) use ($kitchen, $order) {
            $browser->loginAs($kitchen)
                ->visit('/kds')
                ->waitForText('SANDWICH')
                ->assertSee('SANDWICH')
                // paid -> preparing: click "Start Preparing"
                ->click('button[wire\\:click="updateStatus(' . $order->id . ', \'preparing\')"]')
                // After transition, button changes to "Order Ready"
                ->waitFor('button[wire\\:click="updateStatus(' . $order->id . ', \'ready\')"]')
                // preparing -> ready: click "Order Ready"
                ->click('button[wire\\:click="updateStatus(' . $order->id . ', \'ready\')"]')
                // After ready, order disappears from KDS (only paid+preparing shown)
                ->waitUntilMissingText('SANDWICH');
        });

        $order->refresh();
        $this->assertEquals('ready', $order->status);
    }
}
