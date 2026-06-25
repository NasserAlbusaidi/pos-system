<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShiftClosure;
use App\Models\Shop;
use App\Support\ShopClock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Public JSON API for the guest ordering flow (#51). All three endpoints wrap
 * GuestOrderService so pricing/validation/idempotency stay identical to the
 * Livewire path. These tests pin the HTTP contract: status codes, the
 * server-reprices-everything guarantee, and the customer-safe response shape.
 */
class GuestOrderApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @return array{0: Shop, 1: Product}
     */
    private function makeMenu(float $price = 2.000, float $taxRate = 0): array
    {
        $shop = Shop::factory()->create();
        $category = Category::create(['shop_id' => $shop->id, 'name_en' => 'Bread']);
        $product = Product::forceCreate([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name_en' => 'Sourdough',
            'name_ar' => 'خبز',
            'price' => $price,
            'tax_rate' => $taxRate ?: null,
            'is_available' => true,
            'is_visible' => true,
        ]);

        return [$shop, $product];
    }

    private function line(Product $product, int $qty = 1, array $modifiers = [], ?string $note = null): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name_en,
            'quantity' => $qty,
            'selectedModifiers' => $modifiers,
            'note' => $note,
        ];
    }

    // ---- quote -----------------------------------------------------------

    public function test_quote_reprices_cart_server_side(): void
    {
        [$shop, $product] = $this->makeMenu(2.000);

        $response = $this->postJson('/api/guest/orders/quote', [
            'shop' => $shop->slug,
            'cart' => [$this->line($product, 3)],
        ]);

        $response->assertOk()->assertJsonPath('data.items.0.quantity', 3);
        $this->assertEqualsWithDelta(6.0, (float) $response->json('data.subtotal'), 0.0001);
        $this->assertEqualsWithDelta(6.0, (float) $response->json('data.total'), 0.0001);
    }

    public function test_quote_ignores_any_client_supplied_price(): void
    {
        [$shop, $product] = $this->makeMenu(2.000);

        // A tampered client injects price/total fields; the server must ignore
        // them and reprice from the product (2.000 * 2 = 4.000).
        $line = $this->line($product, 2);
        $line['price'] = 0.001;

        $response = $this->postJson('/api/guest/orders/quote', [
            'shop' => $shop->slug,
            'cart' => [$line],
            'total' => 0.001,
        ]);

        $response->assertOk();
        $this->assertEqualsWithDelta(4.0, (float) $response->json('data.total'), 0.0001);
    }

    public function test_quote_returns_zeros_for_empty_cart(): void
    {
        [$shop] = $this->makeMenu();

        $response = $this->postJson('/api/guest/orders/quote', [
            'shop' => $shop->slug,
            'cart' => [],
        ])->assertOk();

        $this->assertEqualsWithDelta(0.0, (float) $response->json('data.total'), 0.0001);
    }

    public function test_quote_flags_unavailable_items_with_409(): void
    {
        [$shop, $product] = $this->makeMenu();
        $product->update(['is_available' => false]);

        $this->postJson('/api/guest/orders/quote', [
            'shop' => $shop->slug,
            'cart' => [$this->line($product, 1)],
        ])->assertStatus(409)
            ->assertJsonPath('unavailable_ids.0', $product->id);
    }

    public function test_quote_rejects_oversized_quantity_with_422(): void
    {
        [$shop, $product] = $this->makeMenu();

        $this->postJson('/api/guest/orders/quote', [
            'shop' => $shop->slug,
            'cart' => [$this->line($product, 1000)],
        ])->assertStatus(422);
    }

    public function test_quote_accepts_grouped_modifier_selection(): void
    {
        [$shop, $product] = $this->makeMenu(2.000);
        $group = ModifierGroup::create(['shop_id' => $shop->id, 'name_en' => 'Size', 'min_selection' => 1, 'max_selection' => 1]);
        $large = ModifierOption::create(['modifier_group_id' => $group->id, 'name_en' => 'Large', 'price_adjustment' => 0.400]);
        $product->modifierGroups()->attach($group->id);

        $response = $this->postJson('/api/guest/orders/quote', [
            'shop' => $shop->slug,
            'cart' => [$this->line($product, 1, [$group->id => [$large->id]])],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.items.0.modifiers.0.name', 'Large');
        $this->assertEqualsWithDelta(2.400, (float) $response->json('data.total'), 0.0001);
    }

    public function test_unknown_shop_returns_404(): void
    {
        $this->postJson('/api/guest/orders/quote', [
            'shop' => 'no-such-shop',
            'cart' => [],
        ])->assertStatus(404);
    }

    // ---- store -----------------------------------------------------------

    public function test_store_creates_order_and_returns_customer_safe_payload(): void
    {
        [$shop, $product] = $this->makeMenu(2.000);

        $response = $this->postJson('/api/guest/orders', [
            'shop' => $shop->slug,
            'cart' => [$this->line($product, 2)],
            'customer_name' => 'Sara',
            'loyalty_phone' => '99887766',
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'received')
            ->assertJsonPath('data.customer_name', 'Sara')
            ->assertJsonPath('data.source', 'guest');
        $this->assertEqualsWithDelta(4.0, (float) $response->json('data.total'), 0.0001);

        $token = $response->json('data.tracking_token');
        $this->assertNotEmpty($token);
        $this->assertDatabaseHas('orders', ['tracking_token' => $token, 'shop_id' => $shop->id]);

        // Never leak internal/PII fields in the create response.
        $response->assertJsonMissingPath('data.loyalty_phone')
            ->assertJsonMissingPath('data.idempotency_key');
    }

    public function test_store_requires_customer_name(): void
    {
        [$shop, $product] = $this->makeMenu();

        $this->postJson('/api/guest/orders', [
            'shop' => $shop->slug,
            'cart' => [$this->line($product, 1)],
            'loyalty_phone' => '99887766',
        ])->assertStatus(422);
    }

    public function test_store_requires_valid_phone(): void
    {
        [$shop, $product] = $this->makeMenu();

        $this->postJson('/api/guest/orders', [
            'shop' => $shop->slug,
            'cart' => [$this->line($product, 1)],
            'customer_name' => 'Sara',
            'loyalty_phone' => '12',
        ])->assertStatus(422);
    }

    public function test_store_requires_idempotency_key(): void
    {
        [$shop, $product] = $this->makeMenu();

        $this->postJson('/api/guest/orders', [
            'shop' => $shop->slug,
            'cart' => [$this->line($product, 1)],
            'customer_name' => 'Sara',
            'loyalty_phone' => '99887766',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('idempotency_key');
    }

    public function test_store_rejects_guest_order_when_shop_is_closed(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-24 12:00:00', 'Asia/Muscat'));

        [$shop, $product] = $this->makeMenu();
        $shop->update([
            'branding' => [
                'timezone' => 'Asia/Muscat',
                'business_hours' => [
                    'wednesday' => ['open' => '09:00', 'close' => '22:00', 'closed' => true],
                ],
            ],
        ]);

        $this->postJson('/api/guest/orders', [
            'shop' => $shop->slug,
            'cart' => [$this->line($product, 1)],
            'customer_name' => 'Sara',
            'loyalty_phone' => '99887766',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertStatus(422)
            ->assertJsonPath('message', __('guest.shop_closed'))
            ->assertJsonPath('field', 'order');

        $this->assertSame(0, Order::where('shop_id', $shop->id)->count());
    }

    public function test_quote_rejects_guest_cart_when_shop_is_closed(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-24 12:00:00', 'Asia/Muscat'));

        [$shop, $product] = $this->makeMenu();
        $shop->update([
            'branding' => [
                'timezone' => 'Asia/Muscat',
                'business_hours' => [
                    'wednesday' => ['open' => '09:00', 'close' => '22:00', 'closed' => true],
                ],
            ],
        ]);

        $this->postJson('/api/guest/orders/quote', [
            'shop' => $shop->slug,
            'cart' => [$this->line($product, 1)],
        ])->assertStatus(422)
            ->assertJsonPath('message', __('guest.shop_closed'))
            ->assertJsonPath('field', 'order');
    }

    public function test_quote_rejects_guest_cart_after_shift_close(): void
    {
        [$shop, $product] = $this->makeMenu();
        $this->closeShiftFor($shop);

        $this->postJson('/api/guest/orders/quote', [
            'shop' => $shop->slug,
            'cart' => [$this->line($product, 1)],
        ])->assertStatus(422)
            ->assertJsonPath('message', __('guest.shift_closed'))
            ->assertJsonPath('field', 'order');
    }

    public function test_store_rejects_guest_order_after_shift_close(): void
    {
        [$shop, $product] = $this->makeMenu();
        $this->closeShiftFor($shop);

        $this->postJson('/api/guest/orders', [
            'shop' => $shop->slug,
            'cart' => [$this->line($product, 1)],
            'customer_name' => 'Sara',
            'loyalty_phone' => '99887766',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertStatus(422)
            ->assertJsonPath('message', __('guest.shift_closed'))
            ->assertJsonPath('field', 'order');

        $this->assertSame(0, Order::where('shop_id', $shop->id)->count());
    }

    public function test_store_is_idempotent_on_repeated_key(): void
    {
        [$shop, $product] = $this->makeMenu(2.000);
        $key = (string) Str::uuid();

        $first = $this->postJson('/api/guest/orders', [
            'shop' => $shop->slug,
            'cart' => [$this->line($product, 1)],
            'customer_name' => 'Sara',
            'loyalty_phone' => '99887766',
            'idempotency_key' => $key,
        ])->assertCreated();

        $second = $this->postJson('/api/guest/orders', [
            'shop' => $shop->slug,
            'cart' => [$this->line($product, 1)],
            'customer_name' => 'Sara',
            'loyalty_phone' => '99887766',
            'idempotency_key' => $key,
        ])->assertOk();

        $this->assertSame(
            $first->json('data.tracking_token'),
            $second->json('data.tracking_token'),
        );
        $this->assertSame(1, Order::where('shop_id', $shop->id)->count());
    }

    public function test_store_never_trusts_client_total(): void
    {
        [$shop, $product] = $this->makeMenu(2.000);

        $line = $this->line($product, 3);
        $line['price'] = 0.001;

        $response = $this->postJson('/api/guest/orders', [
            'shop' => $shop->slug,
            'cart' => [$line],
            'total' => 0.001,
            'customer_name' => 'Sara',
            'loyalty_phone' => '99887766',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertCreated();

        $this->assertEqualsWithDelta(6.0, (float) $response->json('data.total'), 0.0001);
    }

    public function test_store_accepts_grouped_modifier_selection(): void
    {
        [$shop, $product] = $this->makeMenu(2.000);
        $group = ModifierGroup::create(['shop_id' => $shop->id, 'name_en' => 'Size', 'min_selection' => 1, 'max_selection' => 1]);
        $large = ModifierOption::create(['modifier_group_id' => $group->id, 'name_en' => 'Large', 'price_adjustment' => 0.400]);
        $product->modifierGroups()->attach($group->id);

        $line = $this->line($product, 1, [$group->id => [$large->id]]);

        $response = $this->postJson('/api/guest/orders', [
            'shop' => $shop->slug,
            'cart' => [$line],
            'customer_name' => 'Sara',
            'loyalty_phone' => '99887766',
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.items.0.modifiers.0.name', 'Large');
        $this->assertEqualsWithDelta(2.400, (float) $response->json('data.total'), 0.0001);
    }

    // ---- show ------------------------------------------------------------

    public function test_show_returns_customer_safe_status_without_pii(): void
    {
        [$shop, $product] = $this->makeMenu(2.000);

        $token = $this->postJson('/api/guest/orders', [
            'shop' => $shop->slug,
            'cart' => [$this->line($product, 1)],
            'customer_name' => 'Sara',
            'loyalty_phone' => '99887766',
            'idempotency_key' => (string) Str::uuid(),
        ])->json('data.tracking_token');

        $this->getJson("/api/guest/orders/{$token}")
            ->assertOk()
            ->assertJsonPath('data.status', 'received')
            ->assertJsonPath('data.tracking_token', $token)
            ->assertJsonMissingPath('data.loyalty_phone')
            ->assertJsonMissingPath('data.idempotency_key')
            ->assertJsonMissingPath('data.shop_id');
    }

    public function test_show_maps_internal_status_to_customer_safe_label(): void
    {
        [$shop, $product] = $this->makeMenu();
        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'preparing',
            'customer_name' => 'Sara',
            'subtotal_amount' => 2.0,
            'tax_amount' => 0,
            'total_amount' => 2.0,
            'tracking_token' => (string) Str::uuid(),
        ]);

        $this->getJson("/api/guest/orders/{$order->tracking_token}")
            ->assertOk()
            ->assertJsonPath('data.status', 'preparing');
    }

    public function test_pay_at_counter_guest_order_stays_received_after_short_counter_wait(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-24 12:00:00', 'Asia/Muscat'));

        [$shop, $product] = $this->makeMenu(2.000);

        $token = $this->postJson('/api/guest/orders', [
            'shop' => $shop->slug,
            'cart' => [$this->line($product, 1)],
            'customer_name' => 'Sara',
            'loyalty_phone' => '99887766',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertCreated()->json('data.tracking_token');

        Carbon::setTestNow(Carbon::parse('2026-06-24 12:07:00', 'Asia/Muscat'));

        $this->getJson("/api/guest/orders/{$token}")
            ->assertOk()
            ->assertJsonPath('data.status', 'received');

        $this->assertSame('unpaid', Order::where('tracking_token', $token)->firstOrFail()->status);
    }

    public function test_show_cancels_expired_unpaid_order_before_returning_status(): void
    {
        [$shop, $product] = $this->makeMenu();
        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'customer_name' => 'Sara',
            'subtotal_amount' => 2.0,
            'tax_amount' => 0,
            'total_amount' => 2.0,
            'tracking_token' => (string) Str::uuid(),
            'expires_at' => now()->subMinute(),
        ]);

        $this->getJson("/api/guest/orders/{$order->tracking_token}")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertSame('cancelled', $order->fresh()->status);
    }

    public function test_show_unknown_token_returns_404(): void
    {
        $this->getJson('/api/guest/orders/'.Str::uuid())->assertStatus(404);
    }

    private function closeShiftFor(Shop $shop): void
    {
        ShiftClosure::forceCreate([
            'shop_id' => $shop->id,
            'business_date' => ShopClock::localDate($shop),
            'closed_by' => null,
            'expected_cash' => 0.000,
            'actual_cash' => 0.000,
            'difference' => 0.000,
            'shift_summary' => [
                'total_orders' => 0,
                'total_revenue' => 0.000,
                'cash_total' => 0.000,
                'card_total' => 0.000,
                'voucher_total' => 0.000,
            ],
            'closed_at' => now(),
        ]);
    }
}
