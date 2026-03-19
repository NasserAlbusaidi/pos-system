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

        $this->browse(function (Browser $browser) use ($shop, $product) {
            $browser->visit('/menu/'.$shop->slug)
                // Product names have CSS uppercase
                ->waitForText('ICED LATTE')
                ->assertSee('OMR 3.000')
                ->click('button[wire\\:click="addToCart('.$product->id.')"]')
                // "Review Order" button appears in the fixed bottom bar
                ->waitForText('Review Order')
                ->click('button[wire\\:click="toggleReview"]')
                ->waitForText('Your Order')
                ->assertSee('OMR 3.000')
                // Click "Place Order" which opens the Alpine confirm modal
                ->click('.btn-primary[x-on\\:click*="confirm-action"]')
                ->waitForText('Send order to kitchen?')
                // Click "Confirm" in the confirm modal
                ->click('[x-on\\:click="confirm()"]')
                // Wait for tracking page to load (Livewire navigate: true)
                ->waitForText('GUEST PICKUP', 10)
                ->assertPathBeginsWith('/track/');
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

        $this->browse(function (Browser $browser) use ($shop) {
            $browser->visit('/menu/'.$shop->slug)
                ->waitForText('Test Category')
                ->assertSee('Test Category')
                // Product names have CSS uppercase
                ->assertSee('TEST COFFEE')
                ->assertSee('OMR 2.500');
        });
    }

    public function test_guest_can_add_product_with_modifiers(): void
    {
        [$shop, $admin] = $this->createShopWithAdmin();
        [$category, $product] = $this->createProductWithCategory($shop, ['price' => 2.000]);
        [$group, $option] = $this->createModifierGroup($shop, $product, required: false);

        $this->browse(function (Browser $browser) use ($shop, $product) {
            $browser->visit('/menu/'.$shop->slug)
                // Product names have CSS uppercase
                ->waitForText('TEST COFFEE')
                ->click('button[wire\\:click="addToCart('.$product->id.')"]')
                // Modifier group/option names also have CSS uppercase
                ->waitForText('SIZE')
                ->assertSee('LARGE')
                ->assertSee('OMR 1.000');
        });
    }
}
