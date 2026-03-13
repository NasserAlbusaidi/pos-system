<?php

namespace Tests\Browser\Phase3;

use App\Models\Shop;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class SuperAdminShopManagementTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_can_view_shop_list(): void
    {
        $superAdmin = $this->createSuperAdmin();
        Shop::factory()->create(['name' => 'Test Cafe', 'slug' => 'test-cafe-' . uniqid()]);

        $this->browse(function (Browser $browser) use ($superAdmin) {
            $browser->loginAs($superAdmin)
                ->visit('/admin/shops')
                ->assertSee('Test Cafe');
        });
    }

    public function test_can_create_new_shop(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $uid = uniqid();

        $this->browse(function (Browser $browser) use ($superAdmin, $uid) {
            $browser->loginAs($superAdmin)
                ->visit('/admin/shops/create')
                ->type('[wire\\:model\\.live="name"]', 'New Restaurant ' . $uid)
                ->waitFor('[wire\\:model="slug"]')
                ->type('[wire\\:model="ownerName"]', 'Owner Name')
                ->type('[wire\\:model="ownerEmail"]', 'owner-' . $uid . '@test.com')
                ->type('[wire\\:model="ownerPassword"]', 'password123')
                // Button text has CSS uppercase
                ->click('form[wire\\:submit="save"] button[type="submit"]')
                ->waitForRoute('super-admin.shops.index')
                ->assertSee('New Restaurant ' . $uid);
        });
    }

    public function test_can_edit_shop(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $shop = Shop::factory()->create([
            'name' => 'Old Name',
            'slug' => 'edit-test-' . uniqid(),
        ]);

        $this->browse(function (Browser $browser) use ($superAdmin, $shop) {
            $browser->loginAs($superAdmin)
                ->visit('/admin/shops/' . $shop->id . '/edit')
                ->clear('[wire\\:model\\.live="name"]')
                ->type('[wire\\:model\\.live="name"]', 'Renamed Shop')
                // Button text has CSS uppercase
                ->click('form[wire\\:submit="save"] button[type="submit"]')
                ->waitForRoute('super-admin.shops.index')
                ->assertSee('Renamed Shop');
        });
    }
}
