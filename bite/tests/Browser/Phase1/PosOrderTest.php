<?php

namespace Tests\Browser\Phase1;

use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class PosOrderTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_admin_can_pay_order_with_cash(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, [
            'name_en' => 'Cappuccino',
            'price' => 3.500,
        ]);
        $order = $this->createUnpaidOrder($shop, $product);

        $this->browse(function (Browser $browser) use ($admin, $order) {
            $browser->loginAs($admin)
                ->visit('/pos')
                ->waitForText('OMR 3.500')
                ->assertSeeIn('.surface-card', 'Cappuccino')
                ->click('button[wire\\:click="markAsPaid('.$order->id.', \'cash\')"]')
                ->waitUntilMissing('button[wire\\:click="markAsPaid('.$order->id.', \'cash\')"]');
        });

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'shop_id' => $shop->id,
            'status' => 'paid',
            'payment_method' => 'cash',
        ]);
    }

    public function test_admin_can_pay_order_with_card(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop);
        $order = $this->createUnpaidOrder($shop, $product);

        $this->browse(function (Browser $browser) use ($admin, $order) {
            $browser->loginAs($admin)
                ->visit('/pos')
                ->waitFor('button[wire\\:click="markAsPaid('.$order->id.', \'card\')"]')
                ->click('button[wire\\:click="markAsPaid('.$order->id.', \'card\')"]')
                ->waitUntilMissing('button[wire\\:click="markAsPaid('.$order->id.', \'card\')"]');
        });

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'shop_id' => $shop->id,
            'status' => 'paid',
            'payment_method' => 'card',
        ]);
    }

    public function test_pos_displays_omr_three_decimal_places(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, [
            'price' => 1.750,
        ]);
        $this->createUnpaidOrder($shop, $product);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/pos')
                ->waitForText('OMR 1.750')
                ->assertSee('OMR 1.750');
        });
    }
}
