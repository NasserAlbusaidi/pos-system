<?php

namespace Tests\Browser\Phase2;

use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class AuthLoginTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_user_can_login_with_valid_credentials(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->visit('/login')
                ->waitFor('#email')
                ->type('#email', $admin->email)
                ->type('#password', 'password')
                ->press('LOG IN')
                ->waitForLocation('/dashboard')
                ->assertPathIs('/dashboard');
        });
    }

    public function test_invalid_credentials_show_error(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->logout()
                ->visit('/login')
                ->waitFor('#email')
                ->type('#email', 'wrong@test.com')
                ->type('#password', 'wrongpassword')
                ->press('LOG IN')
                ->waitForText('These credentials do not match our records', ignoreCase: true)
                ->assertSee('These credentials do not match our records', ignoreCase: true);
        });
    }

    public function test_user_can_logout(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/dashboard')
                ->waitForText('Dashboard')
                ->assertPathIs('/dashboard')
                ->click('aside button[wire\\:click="logout"]')
                ->waitForLocation('/')
                ->visit('/dashboard')
                ->waitForLocation('/login')
                ->assertPathIs('/login');
        });
    }
}
