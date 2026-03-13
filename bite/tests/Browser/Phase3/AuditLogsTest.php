<?php

namespace Tests\Browser\Phase3;

use App\Models\AuditLog;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class AuditLogsTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_audit_log_shows_recorded_actions(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();

        AuditLog::create([
            'shop_id' => $shop->id,
            'user_id' => $admin->id,
            'action' => 'order.paid',
            'meta' => ['payment_method' => 'cash'],
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/audit-logs')
                ->assertPathIs('/audit-logs')
                // Table cells have CSS uppercase
                ->assertSee('ORDER.PAID');
        });
    }

    public function test_audit_log_search_filters_results(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();

        AuditLog::create([
            'shop_id' => $shop->id,
            'user_id' => $admin->id,
            'action' => 'product.created',
            'meta' => [],
        ]);

        AuditLog::create([
            'shop_id' => $shop->id,
            'user_id' => $admin->id,
            'action' => 'order.paid',
            'meta' => [],
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/audit-logs')
                // Wait for both records to load
                ->waitForText('PRODUCT.CREATED')
                ->assertSee('ORDER.PAID')
                // Type search and wait for Livewire to filter
                ->type('[wire\\:model\\.live="search"]', 'product')
                ->pause(1000)
                ->assertSee('PRODUCT.CREATED')
                ->assertDontSee('ORDER.PAID');
        });
    }
}
