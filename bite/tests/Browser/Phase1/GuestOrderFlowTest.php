<?php

namespace Tests\Browser\Phase1;

use App\Models\Order;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\SeedsTestData;
use Tests\DuskTestCase;

class GuestOrderFlowTest extends DuskTestCase
{
    use SeedsTestData;

    public function test_guest_can_browse_menu_and_place_order(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, [
            'name_en' => 'Iced Latte',
            'price' => 3.000,
        ]);
        $this->createAdditionalProduct($shop, $category);

        $this->browse(function (Browser $browser) use ($shop, $product) {
            $browser->visit('/menu/'.$shop->slug)
                ->tap(fn (Browser $browser) => $this->enterGuestMenu($browser))
                ->waitForText('Iced Latte')
                ->assertSee('3.000')
                ->click($this->quickAddSelector($product->id))
                ->waitForText('View cart')
                ->click('.guest-cta')
                ->waitForText('Your Order')
                ->assertSee('3.000')
                ->type('#guest-name', 'Maha')
                ->type('#guest-phone', '5551234567')
                ->click('button[x-on\\:click*="confirm-action"]')
                ->waitForText('Send order to kitchen?')
                ->click('[x-on\\:click="confirm()"]')
                ->waitForText('Order received', 10)
                ->assertPathBeginsWith('/track/');

            $this->assertSame([], $this->severeConsoleMessages($browser));
        });

        $order = Order::where('shop_id', $shop->id)->first();
        $this->assertNotNull($order);
        $this->assertNotNull($order->tracking_token);
        $this->assertEquals('unpaid', $order->status);
    }

    public function test_guest_menu_shows_categories_and_products(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop);
        $this->createAdditionalProduct($shop, $category);

        $this->browse(function (Browser $browser) use ($shop) {
            $browser->visit('/menu/'.$shop->slug)
                ->tap(fn (Browser $browser) => $this->enterGuestMenu($browser))
                ->waitForText('Test Category')
                ->assertSee('Test Category')
                ->assertSee('Test Coffee')
                ->assertSee('2.500');

            $this->assertSame([], $this->severeConsoleMessages($browser));
        });
    }

    public function test_guest_can_add_product_with_modifiers(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, ['price' => 2.000]);
        $this->createAdditionalProduct($shop, $category);
        [$group, $option] = $this->createModifierGroup($shop, $product, required: false);

        $this->browse(function (Browser $browser) use ($shop, $product) {
            $browser->visit('/menu/'.$shop->slug)
                ->tap(fn (Browser $browser) => $this->enterGuestMenu($browser))
                ->waitForText('Test Coffee')
                ->click($this->quickAddSelector($product->id))
                ->waitForText('Size')
                ->assertSee('Large')
                ->assertSee('1.000');

            $this->assertSame([], $this->severeConsoleMessages($browser));
        });
    }
}
