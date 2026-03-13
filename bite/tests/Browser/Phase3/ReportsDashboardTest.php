<?php

namespace Tests\Browser\Phase3;

use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class ReportsDashboardTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_reports_page_loads_with_data(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, ['price' => 25.000]);

        $order = $this->createPaidOrder($shop, $product);
        $order->update(['status' => 'completed']);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/reports')
                ->assertPathIs('/reports')
                ->assertSee('25.000');
        });
    }

    public function test_reports_scoped_to_current_shop(): void
    {
        [$shop1, $admin1] = $this->createShopWithAdmin();
        [$shop2, $admin2] = $this->createShopWithAdmin();

        [$cat1, $prod1] = $this->createProductWithCategory($shop1, ['name_en' => 'Shop1 Item', 'price' => 50.000]);
        [$cat2, $prod2] = $this->createProductWithCategory($shop2, ['name_en' => 'Shop2 Item', 'price' => 99.000]);

        $order1 = $this->createPaidOrder($shop1, $prod1);
        $order1->update(['status' => 'completed']);

        $order2 = $this->createPaidOrder($shop2, $prod2);
        $order2->update(['status' => 'completed']);

        $this->browse(function (Browser $browser) use ($admin1) {
            $browser->loginAs($admin1)
                ->visit('/reports')
                ->assertSee('50.000')
                ->assertDontSee('99.000');
        });
    }
}
