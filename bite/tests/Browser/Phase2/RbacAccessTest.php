<?php

namespace Tests\Browser\Phase2;

use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class RbacAccessTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_server_can_access_pos_but_not_settings(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        $server = $this->createStaffUser($shop, 'server');

        $this->browse(function (Browser $browser) use ($server) {
            $browser->loginAs($server)
                ->visit('/pos')
                ->assertPathIs('/pos')
                // POS page should load without a 403
                ->assertDontSee('403')
                // Server is not manager|admin, so /settings should 403
                ->visit('/settings')
                ->assertSee('403');
        });
    }

    public function test_kitchen_can_access_kds_but_not_pos(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        $kitchen = $this->createStaffUser($shop, 'kitchen');

        $this->browse(function (Browser $browser) use ($kitchen) {
            $browser->loginAs($kitchen)
                ->visit('/kds')
                ->assertPathIs('/kds')
                // KDS page should load without a 403
                ->assertDontSee('403')
                // Kitchen is not server|manager|admin, so /pos should 403
                ->visit('/pos')
                ->assertSee('403');
        });
    }

    public function test_manager_can_access_pos_kds_reports_settings(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        $manager = $this->createStaffUser($shop, 'manager');

        $this->browse(function (Browser $browser) use ($manager) {
            $browser->loginAs($manager)
                // Manager is in server|manager|admin group
                ->visit('/pos')
                ->assertPathIs('/pos')
                ->assertDontSee('403')
                // Manager is in kitchen|manager|admin group
                ->visit('/kds')
                ->assertPathIs('/kds')
                ->assertDontSee('403')
                // Manager is in manager|admin group
                ->visit('/reports')
                ->assertPathIs('/reports')
                ->assertDontSee('403')
                // Manager is in manager|admin group
                ->visit('/settings')
                ->assertPathIs('/settings')
                ->assertDontSee('403');
        });
    }

    public function test_admin_can_access_billing(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/billing')
                ->assertPathIs('/billing')
                ->assertDontSee('403');
        });
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $this->browse(function (Browser $browser) {
            // Ensure logged out state
            $browser->logout()
                // Dashboard requires auth
                ->visit('/dashboard')
                ->assertPathIs('/login')
                // POS requires auth
                ->visit('/pos')
                ->assertPathIs('/login')
                // KDS requires auth
                ->visit('/kds')
                ->assertPathIs('/login');
        });
    }
}
