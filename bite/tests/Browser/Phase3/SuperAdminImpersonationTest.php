<?php

namespace Tests\Browser\Phase3;

use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class SuperAdminImpersonationTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_super_admin_can_impersonate_and_leave(): void
    {
        $superAdmin = $this->createSuperAdmin();
        [$shop, $admin] = $this->createShopWithAdmin();

        $this->browse(function (Browser $browser) use ($superAdmin, $admin, $shop) {
            // Auto-accept native confirm dialog
            $browser->loginAs($superAdmin)
                ->visit('/admin/shops');

            $browser->script('window.confirm = () => true;');

            $browser->click('form[action*="impersonate/'.$admin->id.'"] button')
                ->waitForLocation('/dashboard')
                ->assertPathIs('/dashboard')
                // Verify we see the impersonated shop's dashboard
                ->assertSee($shop->name)
                // Leave impersonation via direct navigation
                ->visit('/leave-impersonation')
                ->waitForLocation('/admin/shops')
                ->assertPathIs('/admin/shops');
        });
    }

    public function test_impersonated_user_sees_their_shop_dashboard(): void
    {
        $superAdmin = $this->createSuperAdmin();
        [$shop, $admin] = $this->createShopWithAdmin();

        $this->browse(function (Browser $browser) use ($superAdmin, $admin, $shop) {
            $browser->loginAs($superAdmin)
                ->visit('/admin/shops');

            $browser->script('window.confirm = () => true;');

            $browser->click('form[action*="impersonate/'.$admin->id.'"] button')
                ->waitForLocation('/dashboard')
                ->assertPathIs('/dashboard')
                // Dashboard shows the impersonated shop's name in sidebar
                ->assertSee($shop->name)
                // Verify the QR code section loads (confirms correct shop context)
                ->assertPresent('img[alt="QR code for guest menu"]');
        });
    }
}
