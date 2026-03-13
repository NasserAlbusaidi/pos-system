<?php

namespace Tests\Browser\Phase3;

use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class SuperAdminDashboardTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_super_admin_can_access_dashboard(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $this->browse(function (Browser $browser) use ($superAdmin) {
            $browser->loginAs($superAdmin)
                ->visit('/admin')
                ->assertPathIs('/admin');
        });
    }

    public function test_non_super_admin_gets_403(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/admin')
                ->assertSee('403');
        });
    }
}
