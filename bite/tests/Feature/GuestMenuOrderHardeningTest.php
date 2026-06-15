<?php

namespace Tests\Feature;

use App\Livewire\GuestMenu;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Phase 7a (#28) — harden guest order submission against double-submit and
 * oversized / abusive carts. Backend security only; extends the existing
 * rate-limit + price re-validation + forceCreate tenant isolation in
 * GuestMenu::submitOrder() without replacing any of it.
 */
class GuestMenuOrderHardeningTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────
    // Idempotency (double-submit guard)
    // ──────────────────────────────────

    public function test_replayed_submit_with_same_token_short_circuits_to_existing_order(): void
    {
        [$shop, $product] = $this->createMenu();

        // Open the review sheet so the component mints its per-checkout token,
        // then read it. idempotencyKey is #[Locked] — the client (and the test
        // harness) cannot overwrite it; the only way to learn the token is the
        // server-minted value, which is exactly what a double-click resends.
        $component = Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->call('toggleReview');

        $token = $component->get('idempotencyKey');
        $this->assertNotEmpty($token);

        // Simulate the concurrent winner: an order already exists for this exact
        // token in this shop (the first of two in-flight double-click requests
        // committed first). The component still holds that same token.
        $winner = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'customer_name' => 'Layla',
            'loyalty_phone' => '95123456',
            'subtotal_amount' => 2.500,
            'tax_amount' => 0,
            'total_amount' => 2.500,
            'tracking_token' => (string) Str::uuid(),
            'idempotency_key' => $token,
            'expires_at' => now()->addMinutes(6),
        ]);

        $component->set('customerName', 'Layla')
            ->set('loyaltyPhone', '95123456')
            ->call('submitOrder');

        // The replay did not create a second order — the shop-scoped pre-check
        // short-circuited to the winner and redirected to its tracker.
        $this->assertSame(1, Order::count());
        $component->assertRedirect(route('guest.track', $winner->tracking_token));
    }

    public function test_idempotency_key_is_locked_against_client_override(): void
    {
        [$shop, $product] = $this->createMenu();

        // #[Locked] must reject any client-driven write to the server-minted
        // token (defeating idempotency or redirecting to a stranger's order).
        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->set('idempotencyKey', 'attacker-supplied');
    }

    public function test_successful_submit_regenerates_idempotency_token(): void
    {
        [$shop, $product] = $this->createMenu();

        $component = Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            // Opening the review sheet mints the per-checkout token.
            ->call('toggleReview');

        $tokenBefore = $component->get('idempotencyKey');
        $this->assertNotEmpty($tokenBefore);

        $component->set('customerName', 'Layla')
            ->set('loyaltyPhone', '95123456')
            ->call('submitOrder');

        // A fresh checkout must carry a new token so the next order is distinct.
        $this->assertNotSame($tokenBefore, $component->get('idempotencyKey'));
        $this->assertNotEmpty($component->get('idempotencyKey'));
    }

    public function test_order_persists_idempotency_key(): void
    {
        [$shop, $product] = $this->createMenu();

        // Token is minted server-side (review sheet) and is #[Locked], so we
        // read the value rather than setting it, then assert it was persisted.
        $component = Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->call('toggleReview');

        $token = $component->get('idempotencyKey');
        $this->assertNotEmpty($token);

        $component->set('customerName', 'Layla')
            ->set('loyaltyPhone', '95123456')
            ->call('submitOrder');

        $this->assertSame($token, Order::firstOrFail()->idempotency_key);
    }

    // ──────────────────────────────────
    // Caps
    // ──────────────────────────────────

    public function test_rejects_quantity_over_per_line_cap(): void
    {
        config(['ordering.max_quantity_per_line' => 99]);
        [$shop, $product] = $this->createMenu();

        $component = Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->set('cart', [
                $product->id.'-plain' => [
                    'id' => $product->id,
                    'name' => 'Country Loaf',
                    'price' => 2.500,
                    'quantity' => 100, // over the 99 cap
                    'selectedModifiers' => [],
                    'modifierNames' => [],
                    'note' => null,
                ],
            ])
            ->set('customerName', 'Layla')
            ->set('loyaltyPhone', '95123456')
            ->call('submitOrder')
            ->assertSet('showReviewModal', true);

        $this->assertSame(0, Order::count());
        $this->assertSame(__('guest.cart_too_large'), $component->get('orderError'));
    }

    public function test_rejects_too_many_distinct_lines(): void
    {
        config(['ordering.max_lines_per_order' => 3]);
        [$shop] = $this->createMenu();
        $category = Category::create([
            'shop_id' => $shop->id,
            'name_en' => 'More Bakery',
        ]);

        $cart = [];
        for ($i = 0; $i < 4; $i++) {
            $product = Product::forceCreate([
                'shop_id' => $shop->id,
                'category_id' => $category->id,
                'name_en' => "Loaf {$i}",
                'price' => 1.000,
                'is_available' => true,
                'is_visible' => true,
            ]);
            $cart[$product->id.'-plain'] = [
                'id' => $product->id,
                'name' => "Loaf {$i}",
                'price' => 1.000,
                'quantity' => 1,
                'selectedModifiers' => [],
                'modifierNames' => [],
                'note' => null,
            ];
        }

        $component = Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->set('cart', $cart)
            ->set('customerName', 'Layla')
            ->set('loyaltyPhone', '95123456')
            ->call('submitOrder')
            ->assertSet('showReviewModal', true);

        $this->assertSame(0, Order::count());
        $this->assertSame(__('guest.cart_too_large'), $component->get('orderError'));
    }

    public function test_rejects_order_total_over_ceiling(): void
    {
        config(['ordering.max_order_total' => 10]);
        [$shop, $product] = $this->createMenu(); // price 2.500

        $component = Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->set('cart', [
                $product->id.'-plain' => [
                    'id' => $product->id,
                    'name' => 'Country Loaf',
                    'price' => 2.500,
                    'quantity' => 50, // 50 * 2.500 = 125 OMR > 10 ceiling
                    'selectedModifiers' => [],
                    'modifierNames' => [],
                    'note' => null,
                ],
            ])
            ->set('customerName', 'Layla')
            ->set('loyaltyPhone', '95123456')
            ->call('submitOrder')
            ->assertSet('showReviewModal', true);

        $this->assertSame(0, Order::count());
        $this->assertSame(__('guest.order_total_too_high'), $component->get('orderError'));
    }

    public function test_caps_apply_to_group_path(): void
    {
        config(['ordering.max_quantity_per_line' => 99]);
        [$shop, $product] = $this->createMenu();

        $component = Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('createGroup')
            ->call('addToCart', $product->id);

        // Tamper the group cart JSON directly with an oversized quantity.
        $groupCart = $component->instance()->groupCart;
        $items = $groupCart->items;
        $items[0]['quantity'] = 100;
        $groupCart->update(['items' => $items]);

        $component->set('customerName', 'Layla')
            ->set('loyaltyPhone', '95123456')
            ->call('submitOrder')
            ->assertSet('showReviewModal', true);

        $this->assertSame(0, Order::count());
        $this->assertSame(__('guest.cart_too_large'), $component->get('orderError'));
    }

    public function test_happy_path_within_caps_still_creates_order(): void
    {
        [$shop, $product] = $this->createMenu();

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->set('customerName', 'Layla')
            ->set('loyaltyPhone', '95123456')
            ->call('submitOrder');

        $this->assertSame(1, Order::count());
        $this->assertSame('unpaid', Order::firstOrFail()->status);
    }

    /**
     * @return array{0: Shop, 1: Product}
     */
    private function createMenu(): array
    {
        $shop = Shop::create([
            'name' => 'Sourdough',
            'slug' => 'sourdough-'.Str::random(6),
        ]);
        $category = Category::create([
            'shop_id' => $shop->id,
            'name_en' => 'Bakery',
        ]);
        $product = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Country Loaf',
            'price' => 2.500,
            'is_available' => true,
            'is_visible' => true,
        ]);

        return [$shop, $product];
    }
}
