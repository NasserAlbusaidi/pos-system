<?php

namespace Tests\Browser\Phase3;

use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class ShiftReportTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_shift_report_shows_todays_data(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, ['price' => 15.000]);

        $order = $this->createPaidOrder($shop, $product, quantity: 3);
        $order->update(['status' => 'completed']);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/shift-report')
                ->assertPathIs('/shift-report')
                ->assertSee('45.000');
        });
    }
}
