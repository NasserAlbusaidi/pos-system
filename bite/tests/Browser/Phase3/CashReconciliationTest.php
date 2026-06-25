<?php

namespace Tests\Browser\Phase3;

use App\Models\Payment;
use App\Models\ShiftClosure;
use App\Support\ShopClock;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class CashReconciliationTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_manager_can_close_shift_and_pos_surfaces_payment_lock(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, [
            'name_en' => 'Closeout Coffee',
            'price' => 8.000,
        ]);
        $paidOrder = $this->createPaidOrder($shop, $product);
        $unpaidOrder = $this->createUnpaidOrder($shop, $product);

        Payment::forceCreate([
            'shop_id' => $shop->id,
            'order_id' => $paidOrder->id,
            'amount' => 8.000,
            'method' => 'cash',
            'created_by' => $admin->id,
            'paid_at' => $paidOrder->paid_at,
        ]);

        $this->browse(function (Browser $browser) use ($admin, $unpaidOrder) {
            $browser->loginAs($admin)
                ->visit('/cash-reconciliation')
                ->waitForText('Cash Reconciliation')
                ->assertSee('8.000')
                ->type('#actualCash', '8.000')
                ->click('button[wire\\:click="reconcile"]')
                ->waitForText('Reconciliation Result')
                ->assertSee('BALANCED')
                ->click('button[wire\\:click="closeShift"]')
                ->waitForLocation('/dashboard')
                ->visit('/pos')
                ->waitFor('button[wire\\:click="markAsPaid('.$unpaidOrder->id.', \'cash\')"]')
                ->click('button[wire\\:click="markAsPaid('.$unpaidOrder->id.', \'cash\')"]')
                ->waitFor('@pos-payment-error')
                ->assertSee('SHIFT IS CLOSED FOR TODAY. PAYMENTS ARE LOCKED UNTIL THE NEXT BUSINESS DAY.');
        });

        $this->assertDatabaseHas('shift_closures', [
            'shop_id' => $shop->id,
            'business_date' => ShopClock::localDate($shop),
            'closed_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $unpaidOrder->id,
            'status' => 'unpaid',
        ]);
        $this->assertDatabaseMissing('payments', [
            'order_id' => $unpaidOrder->id,
        ]);

        $this->assertSame(1, ShiftClosure::where('shop_id', $shop->id)->count());
    }
}
