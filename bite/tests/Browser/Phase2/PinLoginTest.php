<?php

namespace Tests\Browser\Phase2;

use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class PinLoginTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_staff_can_login_with_valid_pin(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        $server = $this->createStaffUser($shop, 'server', '5678');

        $this->browse(function (Browser $browser) use ($shop) {
            $browser->visit('/pos/pin/' . $shop->slug)
                ->waitFor('input[type="password"]')
                ->type('input[type="password"]', '5678')
                ->click('button[type="submit"]')
                ->waitForLocation('/pos')
                ->assertPathIs('/pos');
        });
    }

    public function test_wrong_pin_shows_error(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        $this->createStaffUser($shop, 'server', '5678');

        $this->browse(function (Browser $browser) use ($shop) {
            $browser->visit('/pos/pin/' . $shop->slug)
                ->waitFor('input[type="password"]')
                ->type('input[type="password"]', '9999')
                ->click('button[type="submit"]')
                ->pause(2000)
                ->assertSee('AUTHENTICATION FAILED.');
        });
    }

    public function test_correct_user_is_authenticated_after_pin_login(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        $kitchen = $this->createStaffUser($shop, 'kitchen', '4321');

        $this->browse(function (Browser $browser) use ($shop) {
            // Login as kitchen staff via PIN
            $browser->visit('/pos/pin/' . $shop->slug)
                ->waitFor('input[type="password"]')
                ->type('input[type="password"]', '4321')
                ->click('button[type="submit"]')
                ->pause(1500)
                // Kitchen users can't access /pos (role restricted),
                // but they can access /kds — proving authentication succeeded
                ->visit('/kds')
                ->waitForText('Kitchen Display')
                ->assertSee('Kitchen Display');
        });
    }
}
