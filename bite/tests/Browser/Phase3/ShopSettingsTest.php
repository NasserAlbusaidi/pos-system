<?php

namespace Tests\Browser\Phase3;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class ShopSettingsTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_can_update_shop_name(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/settings')
                ->clear('[wire\\:model="name"]')
                ->type('[wire\\:model="name"]', 'Updated Shop Name')
                ->click('form[wire\\:submit\\.prevent="save"] button[type="submit"]')
                ->waitForText('SHOP SETTINGS SAVED')
                ->assertSee('SHOP SETTINGS SAVED');
        });

        $shop->refresh();
        $this->assertEquals('Updated Shop Name', $shop->name);
    }

    public function test_can_add_staff_member(): void
    {
        // Give shop a trial so Pro features (unlimited staff) are available
        [$shop, $admin] = $this->createShopWithAdmin([
            'trial_ends_at' => now()->addDays(14),
        ]);

        $email = 'waiter-' . uniqid() . '@test.com';

        $this->browse(function (Browser $browser) use ($admin, $shop, $email) {
            $browser->loginAs($admin)
                ->visit('/settings')
                ->type('#staff-name', 'New Waiter')
                ->type('#staff-email', $email)
                ->select('#staff-role', 'manager')
                ->type('#staff-pin', '7777')
                ->pause(500);

            // Submit the staff form
            $browser->script(
                "document.querySelector('form[wire\\\\:submit\\\\.prevent=\"addStaff\"]').requestSubmit();"
            );

            $browser->waitForText('STAFF MEMBER ADDED', 10)
                ->assertSee('New Waiter');
        });

        // Verify staff was actually created
        $staff = User::where('shop_id', $shop->id)
            ->where('email', $email)
            ->first();

        $this->assertNotNull($staff);
        $this->assertEquals('New Waiter', $staff->name);
    }
}
