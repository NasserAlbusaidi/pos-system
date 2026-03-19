<?php

namespace Tests\Browser\Phase1;

use App\Models\Order;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class PosSplitOrderTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_can_split_unpaid_order(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, [
            'name_en' => 'Espresso',
            'price' => 10.000,
        ]);
        $order = $this->createUnpaidOrder($shop, $product, quantity: 2);
        $item = $order->items()->first();

        $this->browse(function (Browser $browser) use ($admin, $order, $item) {
            $browser->loginAs($admin)
                ->visit('/pos')
                ->waitForText('OMR 20.000')
                ->click('button[wire\\:click="openSplit('.$order->id.')"]')
                ->waitForText('Split Order')
                ->waitFor('input[wire\\:model\\.live="splitQuantities.'.$item->id.'"]')
                ->clear('input[wire\\:model\\.live="splitQuantities.'.$item->id.'"]')
                ->type('input[wire\\:model\\.live="splitQuantities.'.$item->id.'"]', '1')
                ->click('button[wire\\:click="applySplit"]')
                ->waitUntilMissing('button[wire\\:click="applySplit"]');
        });

        $splitOrders = Order::where('split_group_id', '!=', null)
            ->where('shop_id', $shop->id)
            ->get();

        $this->assertGreaterThanOrEqual(2, $splitOrders->count());

        // Original order should have 1 qty remaining at 10.000
        $original = $order->fresh();
        $this->assertEquals(10.000, (float) $original->total_amount);

        // New split order should also have 1 qty at 10.000
        $split = Order::where('parent_order_id', $order->id)->first();
        $this->assertNotNull($split);
        $this->assertEquals('unpaid', $split->status);
        $this->assertEquals(10.000, (float) $split->total_amount);
    }

    public function test_split_validation_requires_at_least_one_item(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop);
        $order = $this->createUnpaidOrder($shop, $product, quantity: 2);

        $this->browse(function (Browser $browser) use ($admin, $order) {
            $browser->loginAs($admin)
                ->visit('/pos')
                ->waitFor('button[wire\\:click="openSplit('.$order->id.')"]')
                ->click('button[wire\\:click="openSplit('.$order->id.')"]')
                ->waitForText('Split Order')
                // Don't set any split quantities (all default to 0)
                ->click('button[wire\\:click="applySplit"]')
                // Error text has CSS uppercase, so innerText is uppercased
                ->waitForText('SELECT AT LEAST ONE ITEM TO SPLIT');
        });
    }
}
