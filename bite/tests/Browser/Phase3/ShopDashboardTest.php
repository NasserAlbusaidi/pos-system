<?php

namespace Tests\Browser\Phase3;

use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class ShopDashboardTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_dashboard_shows_metrics(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, ['price' => 10.000]);

        // Create completed order for today
        $order = $this->createPaidOrder($shop, $product);
        $order->update(['status' => 'completed']);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/dashboard')
                ->assertPathIs('/dashboard')
                ->assertSee('10.000');
        });
    }

    public function test_dashboard_shows_qr_code(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/dashboard')
                ->assertPresent('img[alt="QR code for guest menu"]');
        });
    }
}
