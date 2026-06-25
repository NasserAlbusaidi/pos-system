<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicShopEntitlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_plan_shop_public_menu_and_quote_remain_available(): void
    {
        [$shop, $product] = $this->makeMenu();

        $this->get(route('guest.menu', $shop))
            ->assertOk();

        $this->postJson(route('api.guest.orders.quote'), [
            'shop' => $shop->slug,
            'cart' => [
                [
                    'id' => $product->id,
                    'name' => $product->name_en,
                    'quantity' => 1,
                    'selectedModifiers' => [],
                ],
            ],
        ])->assertOk()
            ->assertJsonPath('data.total', 2);
    }

    public function test_expired_subscription_shop_guest_menu_is_not_publicly_available(): void
    {
        [$shop] = $this->makeMenu();
        $this->addExpiredSubscription($shop);

        $this->get(route('guest.menu', $shop))
            ->assertNotFound();
    }

    public function test_expired_subscription_shop_guest_api_rejects_new_order_quotes(): void
    {
        [$shop, $product] = $this->makeMenu();
        $this->addExpiredSubscription($shop);

        $this->postJson(route('api.guest.orders.quote'), [
            'shop' => $shop->slug,
            'cart' => [
                [
                    'id' => $product->id,
                    'name' => $product->name_en,
                    'quantity' => 1,
                    'selectedModifiers' => [],
                ],
            ],
        ])->assertNotFound();
    }

    public function test_expired_subscription_shop_guest_api_rejects_new_order_creation(): void
    {
        [$shop, $product] = $this->makeMenu();
        $this->addExpiredSubscription($shop);

        $this->postJson(route('api.guest.orders.store'), [
            'shop' => $shop->slug,
            'cart' => [
                [
                    'id' => $product->id,
                    'name' => $product->name_en,
                    'quantity' => 1,
                    'selectedModifiers' => [],
                ],
            ],
            'customer_name' => 'Aisha',
            'loyalty_phone' => '+968 9000 0000',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertNotFound();
    }

    public function test_expired_subscription_shop_pin_terminal_is_not_publicly_available(): void
    {
        [$shop] = $this->makeMenu();
        $this->addExpiredSubscription($shop);

        $this->get(route('pos.pin', $shop))
            ->assertNotFound();
    }

    public function test_expired_subscription_shop_guest_menu_qr_is_not_publicly_available(): void
    {
        [$shop] = $this->makeMenu();
        $this->addExpiredSubscription($shop);

        $this->get(route('guest.menu.qr', $shop))
            ->assertNotFound();
    }

    public function test_expired_subscription_shop_order_tracker_is_not_publicly_available(): void
    {
        [$shop] = $this->makeMenu();
        $order = $this->makeOrder($shop);
        $this->addExpiredSubscription($shop);

        $this->get(route('guest.track', $order->tracking_token))
            ->assertNotFound();
    }

    public function test_expired_subscription_shop_guest_api_rejects_order_status_reads(): void
    {
        [$shop] = $this->makeMenu();
        $order = $this->makeOrder($shop);
        $this->addExpiredSubscription($shop);

        $this->getJson(route('api.guest.orders.show', $order->tracking_token))
            ->assertNotFound();
    }

    public function test_suspended_shop_public_surfaces_are_not_available(): void
    {
        [$shop, $product] = $this->makeMenu();
        $order = $this->makeOrder($shop);
        $shop->forceFill(['status' => 'suspended'])->save();

        $cart = [
            [
                'id' => $product->id,
                'name' => $product->name_en,
                'quantity' => 1,
                'selectedModifiers' => [],
            ],
        ];

        $this->get(route('guest.menu', $shop))
            ->assertNotFound();

        $this->postJson(route('api.guest.orders.quote'), [
            'shop' => $shop->slug,
            'cart' => $cart,
        ])->assertNotFound();

        $this->postJson(route('api.guest.orders.store'), [
            'shop' => $shop->slug,
            'cart' => $cart,
            'customer_name' => 'Aisha',
            'loyalty_phone' => '+968 9000 0000',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertNotFound();

        $this->get(route('guest.track', $order->tracking_token))
            ->assertNotFound();

        $this->getJson(route('api.guest.orders.show', $order->tracking_token))
            ->assertNotFound();

        $this->get(route('pos.pin', $shop))
            ->assertNotFound();
    }

    /**
     * @return array{0: Shop, 1: Product}
     */
    private function makeMenu(): array
    {
        $shop = Shop::factory()->create([
            'trial_ends_at' => null,
            'branding' => ['onboarding_completed' => true],
        ]);

        $category = Category::create(['shop_id' => $shop->id, 'name_en' => 'Bowls']);
        $product = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Market Bowl',
            'price' => 2.000,
            'is_available' => true,
            'is_visible' => true,
        ]);

        return [$shop, $product];
    }

    private function addExpiredSubscription(Shop $shop): void
    {
        $shop->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_lapsed_'.$shop->id,
            'stripe_status' => 'canceled',
            'stripe_price' => 'price_test',
            'quantity' => 1,
            'ends_at' => now()->subDay(),
        ]);
    }

    private function makeOrder(Shop $shop): Order
    {
        return Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'paid',
            'total_amount' => 2.000,
            'subtotal_amount' => 2.000,
            'tax_amount' => 0,
            'paid_at' => now(),
        ]);
    }
}
