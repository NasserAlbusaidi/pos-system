<?php

namespace Tests\Feature\Livewire;

use App\Livewire\GuestMenu;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class GuestMenuRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear the guest order rate limiter before each test to prevent pollution
        RateLimiter::clear('guest-order:127.0.0.1');
    }

    protected function tearDown(): void
    {
        RateLimiter::clear('guest-order:127.0.0.1');
        parent::tearDown();
    }

    private function createShopWithProduct(): array
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $category = Category::create(['shop_id' => $shop->id, 'name_en' => 'Coffee']);
        $product = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Latte',
            'price' => 4.50,
            'is_available' => true,
        ]);

        return [$shop, $product];
    }

    public function test_guest_ordering_shows_friendly_error_after_10_orders_in_15_minutes(): void
    {
        [$shop, $product] = $this->createShopWithProduct();

        $component = Livewire::test(GuestMenu::class, ['shop' => $shop]);

        // Submit 10 orders successfully (at the limit, not over it)
        for ($i = 0; $i < 10; $i++) {
            $component
                ->call('addToCart', $product->id)
                ->call('submitOrder');

            // Clear the cart for next iteration (re-add item)
            $component->set('cart', []);
        }

        // 11th attempt should hit the rate limit
        $component
            ->call('addToCart', $product->id)
            ->call('submitOrder');

        // Check that orderError contains the friendly rate limit message
        $orderError = $component->get('orderError');
        $this->assertNotNull($orderError, 'orderError should be set when rate limited');
        $this->assertStringContainsString("You're ordering too quickly", $orderError);
    }

    public function test_guest_ordering_rate_limit_uses_15_minute_window(): void
    {
        [$shop, $product] = $this->createShopWithProduct();

        $component = Livewire::test(GuestMenu::class, ['shop' => $shop]);

        // Hit the rate limiter directly to simulate being over the limit
        for ($i = 0; $i < 10; $i++) {
            RateLimiter::hit('guest-order:127.0.0.1', 900); // 900 seconds = 15 minutes
        }

        // Next attempt should be rate limited
        $component
            ->call('addToCart', $product->id)
            ->call('submitOrder');

        $orderError = $component->get('orderError');
        $this->assertNotNull($orderError);
        $this->assertStringContainsString("You're ordering too quickly", $orderError);
    }

    public function test_guest_ordering_allows_10_orders_before_rate_limiting(): void
    {
        [$shop, $product] = $this->createShopWithProduct();

        // The limit is 10 attempts — verify 10 orders can be submitted without error
        for ($i = 0; $i < 10; $i++) {
            $component = Livewire::test(GuestMenu::class, ['shop' => $shop])
                ->call('addToCart', $product->id)
                ->call('submitOrder');

            // Should not be rate limited
            $orderError = $component->get('orderError');
            $this->assertNull($orderError, "Order $i should not be rate limited (null orderError expected)");
        }
    }
}
