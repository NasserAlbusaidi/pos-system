<?php

namespace Tests\Browser\Phase1;

use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class GuestOrderTrackingTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_guest_can_track_order_by_token(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, [
            'price' => 2.500,
        ]);

        $token = Str::uuid()->toString();
        $order = $this->createPaidOrder($shop, $product);
        $order->update(['tracking_token' => $token]);

        $this->browse(function (Browser $browser) use ($token) {
            $browser->visit('/track/'.$token)
                ->waitForText('OMR 2.500')
                ->assertSee('OMR 2.500')
                // Status timeline shows PAID as active
                ->assertSee('PAID')
                // Status message for paid orders
                ->assertSee('Payment confirmed');
        });
    }

    public function test_tracking_page_reflects_status_changes(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, [
            'price' => 3.000,
        ]);

        $token = Str::uuid()->toString();
        $order = $this->createPaidOrder($shop, $product);
        $order->update(['tracking_token' => $token]);

        $this->browse(function (Browser $browser) use ($order, $token) {
            $browser->visit('/track/'.$token)
                ->waitForText('Payment confirmed')
                ->assertSee('Payment confirmed');

            // Transition to preparing
            $order->update(['status' => 'preparing']);

            $browser->refresh()
                ->waitForText('Kitchen is actively preparing')
                ->assertSee('Kitchen is actively preparing');

            // Transition to ready
            $order->update(['status' => 'ready']);

            $browser->refresh()
                ->waitForText('Order is complete')
                ->assertSee('Order is complete');
        });
    }
}
