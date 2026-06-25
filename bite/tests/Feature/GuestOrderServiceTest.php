<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ShiftClosure;
use App\Models\Shop;
use App\Services\GuestOrderService;
use App\Support\ShopClock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GuestOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function service(): GuestOrderService
    {
        return app(GuestOrderService::class);
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

    public function test_quote_prices_a_clean_cart(): void
    {
        [$shop, $product] = $this->makeMenu(2.000);

        $quote = $this->service()->quote($shop, [$this->line($product, 2)]);

        $this->assertSame('ok', $quote['outcome']);
        $this->assertEqualsWithDelta(4.000, $quote['subtotal'], 0.0001);
        $this->assertEqualsWithDelta(0.0, $quote['tax'], 0.0001);
        $this->assertEqualsWithDelta(4.000, $quote['total'], 0.0001);
        $this->assertCount(1, $quote['items']);
        $this->assertSame(2, $quote['items'][0]['quantity']);
    }

    public function test_quote_recomputes_tax_from_product_rate(): void
    {
        [$shop, $product] = $this->makeMenu(10.000, 5);

        $quote = $this->service()->quote($shop, [$this->line($product, 1)]);

        $this->assertEqualsWithDelta(10.000, $quote['subtotal'], 0.0001);
        $this->assertEqualsWithDelta(0.500, $quote['tax'], 0.0001);
        $this->assertEqualsWithDelta(10.500, $quote['total'], 0.0001);
    }

    public function test_quote_adds_modifier_price(): void
    {
        [$shop, $product] = $this->makeMenu(1.500);
        $group = ModifierGroup::create(['shop_id' => $shop->id, 'name_en' => 'Size', 'min_selection' => 0, 'max_selection' => 1]);
        $large = ModifierOption::create(['modifier_group_id' => $group->id, 'name_en' => 'Large', 'price_adjustment' => 0.400]);
        $product->modifierGroups()->attach($group->id);

        $quote = $this->service()->quote($shop, [$this->line($product, 1, [$group->id => [$large->id]])]);

        $this->assertSame('ok', $quote['outcome']);
        $this->assertEqualsWithDelta(1.900, $quote['subtotal'], 0.0001);
        $this->assertEqualsWithDelta(1.900, $quote['items'][0]['price_snapshot'], 0.0001);
        $this->assertCount(1, $quote['items'][0]['modifiers']);
    }

    public function test_quote_never_prices_a_line_below_zero_after_modifier_discounts(): void
    {
        [$shop, $product] = $this->makeMenu(1.000);
        $group = ModifierGroup::create(['shop_id' => $shop->id, 'name_en' => 'Discounts', 'min_selection' => 0, 'max_selection' => 1]);
        $comped = ModifierOption::create(['modifier_group_id' => $group->id, 'name_en' => 'Comped', 'price_adjustment' => -2.000]);
        $product->modifierGroups()->attach($group->id);

        $quote = $this->service()->quote($shop, [$this->line($product, 1, [$group->id => [$comped->id]])]);

        $this->assertSame('ok', $quote['outcome']);
        $this->assertEqualsWithDelta(0.0, $quote['subtotal'], 0.0001);
        $this->assertEqualsWithDelta(0.0, $quote['total'], 0.0001);
        $this->assertEqualsWithDelta(0.0, $quote['items'][0]['price_snapshot'], 0.0001);
    }

    public function test_quote_rejects_cart_over_quantity_cap(): void
    {
        [$shop, $product] = $this->makeMenu();

        $quote = $this->service()->quote($shop, [$this->line($product, 100)]);

        $this->assertSame('invalid', $quote['outcome']);
        $this->assertSame('order', $quote['error_field']);
    }

    #[DataProvider('invalidQuantityCarts')]
    public function test_quote_rejects_non_positive_or_missing_quantities(callable $cartMutator): void
    {
        [$shop, $product] = $this->makeMenu();
        $line = $this->line($product, 1);
        $cart = [$cartMutator($line)];

        $quote = $this->service()->quote($shop, $cart);

        $this->assertSame('invalid', $quote['outcome']);
        $this->assertSame('order', $quote['error_field']);
    }

    #[DataProvider('invalidQuantityCarts')]
    public function test_create_rejects_non_positive_or_missing_quantities_without_persisting(callable $cartMutator): void
    {
        [$shop, $product] = $this->makeMenu();
        $line = $this->line($product, 1);
        $cart = [$cartMutator($line)];

        $result = $this->service()->create($shop, $cart, [
            'idempotency_key' => (string) Str::uuid(),
            'customer_name' => 'Layla',
            'loyalty_phone' => '95123456',
        ]);

        $this->assertSame('invalid', $result['outcome']);
        $this->assertSame('order', $result['error_field']);
        $this->assertSame(0, Order::count());
    }

    public static function invalidQuantityCarts(): array
    {
        return [
            'zero quantity' => [fn (array $line): array => array_merge($line, ['quantity' => 0])],
            'negative quantity' => [fn (array $line): array => array_merge($line, ['quantity' => -2])],
            'missing quantity' => [function (array $line): array {
                unset($line['quantity']);

                return $line;
            }],
        ];
    }

    public function test_quote_rejects_total_over_cap(): void
    {
        config(['ordering.max_order_total' => 1]);
        [$shop, $product] = $this->makeMenu(2.000);

        $quote = $this->service()->quote($shop, [$this->line($product, 1)]);

        $this->assertSame('invalid', $quote['outcome']);
        $this->assertSame(__('guest.order_total_too_high'), $quote['error']);
    }

    public function test_quote_flags_unavailable_product(): void
    {
        [$shop, $product] = $this->makeMenu();
        $cart = [$this->line($product, 1)];

        $product->update(['is_available' => false]);

        $quote = $this->service()->quote($shop, $cart);

        $this->assertSame('unavailable', $quote['outcome']);
        $this->assertSame([$product->id], $quote['unavailable_ids']);
        $this->assertContains('Sourdough', $quote['unavailable']);
    }

    public function test_create_persists_order_items_and_modifiers(): void
    {
        [$shop, $product] = $this->makeMenu(2.000);
        $group = ModifierGroup::create(['shop_id' => $shop->id, 'name_en' => 'Size', 'min_selection' => 0, 'max_selection' => 1]);
        $large = ModifierOption::create(['modifier_group_id' => $group->id, 'name_en' => 'Large', 'price_adjustment' => 0.400]);
        $product->modifierGroups()->attach($group->id);

        $result = $this->service()->create($shop, [$this->line($product, 1, [$group->id => [$large->id]], 'No seeds')], [
            'idempotency_key' => (string) Str::uuid(),
            'customer_name' => 'Layla',
            'loyalty_phone' => '95123456',
            'order_note' => 'Leave at door',
        ]);

        $this->assertSame('created', $result['outcome']);
        $order = $result['order'];
        $this->assertSame('unpaid', $order->status);
        $this->assertSame('guest', $order->source);
        $this->assertSame('Layla', $order->customer_name);
        $this->assertSame('Leave at door', $order->order_note);
        $this->assertEqualsWithDelta(2.400, $order->total_amount, 0.0001);

        $item = OrderItem::where('order_id', $order->id)->firstOrFail();
        $this->assertSame('No seeds', $item->note);
        $this->assertEqualsWithDelta(2.400, $item->price_snapshot, 0.0001);
        $this->assertSame('Large', $item->modifiers->first()->modifier_option_name_snapshot_en);
    }

    public function test_create_is_idempotent_on_repeat_key(): void
    {
        [$shop, $product] = $this->makeMenu();
        $key = (string) Str::uuid();
        $context = ['idempotency_key' => $key, 'customer_name' => 'Layla', 'loyalty_phone' => '95123456'];
        $cart = [$this->line($product, 1)];

        $first = $this->service()->create($shop, $cart, $context);
        $second = $this->service()->create($shop, $cart, $context);

        $this->assertSame('created', $first['outcome']);
        $this->assertSame('duplicate', $second['outcome']);
        $this->assertSame($first['order']->id, $second['order']->id);
        $this->assertSame(1, Order::where('shop_id', $shop->id)->count());
    }

    public function test_create_rejects_reused_idempotency_key_with_different_cart(): void
    {
        [$shop, $product] = $this->makeMenu();
        $key = (string) Str::uuid();
        $context = ['idempotency_key' => $key, 'customer_name' => 'Layla', 'loyalty_phone' => '95123456'];

        $first = $this->service()->create($shop, [$this->line($product, 1)], $context);
        $second = $this->service()->create($shop, [$this->line($product, 2)], $context);

        $this->assertSame('created', $first['outcome']);
        $this->assertSame('invalid', $second['outcome']);
        $this->assertSame('order', $second['error_field']);
        $this->assertSame(__('guest.idempotency_conflict'), $second['error']);
        $this->assertSame(1, Order::where('shop_id', $shop->id)->count());
    }

    public function test_create_for_counter_rejects_reused_idempotency_key_with_different_cart(): void
    {
        [$shop, $product] = $this->makeMenu();
        $key = (string) Str::uuid();
        $context = ['idempotency_key' => $key];

        $first = $this->service()->createForCounter($shop, [$this->line($product, 1)], $context);
        $second = $this->service()->createForCounter($shop, [$this->line($product, 2)], $context);

        $this->assertSame('created', $first['outcome']);
        $this->assertSame('invalid', $second['outcome']);
        $this->assertSame('order', $second['error_field']);
        $this->assertSame(__('guest.idempotency_conflict'), $second['error']);
        $this->assertSame(1, Order::where('shop_id', $shop->id)->count());
    }

    public function test_create_requires_customer_name(): void
    {
        [$shop, $product] = $this->makeMenu();

        $result = $this->service()->create($shop, [$this->line($product, 1)], [
            'idempotency_key' => (string) Str::uuid(),
            'customer_name' => '   ',
            'loyalty_phone' => '95123456',
        ]);

        $this->assertSame('invalid', $result['outcome']);
        $this->assertSame('order', $result['error_field']);
        $this->assertSame(__('guest.name_required'), $result['error']);
        $this->assertSame(0, Order::count());
    }

    public function test_create_requires_valid_phone(): void
    {
        [$shop, $product] = $this->makeMenu();

        $result = $this->service()->create($shop, [$this->line($product, 1)], [
            'idempotency_key' => (string) Str::uuid(),
            'customer_name' => 'Layla',
            'loyalty_phone' => 'abc',
        ]);

        $this->assertSame('invalid', $result['outcome']);
        $this->assertSame('loyalty', $result['error_field']);
        $this->assertSame(0, Order::count());
    }

    public function test_create_rejects_guest_order_when_shop_is_closed(): void
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

        $result = $this->service()->create($shop, [$this->line($product, 1)], [
            'idempotency_key' => (string) Str::uuid(),
            'customer_name' => 'Layla',
            'loyalty_phone' => '95123456',
        ]);

        $this->assertSame('invalid', $result['outcome']);
        $this->assertSame('order', $result['error_field']);
        $this->assertSame(__('guest.shop_closed'), $result['error']);
        $this->assertSame(0, Order::count());
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

        $quote = $this->service()->quote($shop, [$this->line($product, 1)]);

        $this->assertSame('invalid', $quote['outcome']);
        $this->assertSame('order', $quote['error_field']);
        $this->assertSame(__('guest.shop_closed'), $quote['error']);
    }

    public function test_quote_rejects_guest_cart_after_shift_is_closed_for_the_business_day(): void
    {
        [$shop, $product] = $this->makeMenu();
        $this->closeShiftFor($shop);

        $quote = $this->service()->quote($shop, [$this->line($product, 1)]);

        $this->assertSame('invalid', $quote['outcome']);
        $this->assertSame('order', $quote['error_field']);
        $this->assertSame(__('guest.shift_closed'), $quote['error']);
    }

    public function test_create_rejects_guest_order_after_shift_is_closed_for_the_business_day(): void
    {
        [$shop, $product] = $this->makeMenu();
        $this->closeShiftFor($shop);

        $result = $this->service()->create($shop, [$this->line($product, 1)], [
            'idempotency_key' => (string) Str::uuid(),
            'customer_name' => 'Layla',
            'loyalty_phone' => '95123456',
        ]);

        $this->assertSame('invalid', $result['outcome']);
        $this->assertSame('order', $result['error_field']);
        $this->assertSame(__('guest.shift_closed'), $result['error']);
        $this->assertSame(0, Order::where('shop_id', $shop->id)->count());
    }

    public function test_counter_order_service_rejects_new_order_after_shift_close_defensively(): void
    {
        [$shop, $product] = $this->makeMenu();
        $this->closeShiftFor($shop);

        $result = $this->service()->createForCounter($shop, [$this->line($product, 1)], [
            'idempotency_key' => (string) Str::uuid(),
            'customer_name' => 'Counter walk-in',
        ]);

        $this->assertSame('invalid', $result['outcome']);
        $this->assertSame('order', $result['error_field']);
        $this->assertSame(__('guest.shift_closed'), $result['error']);
        $this->assertSame(0, Order::where('shop_id', $shop->id)->count());
    }

    public function test_create_accepts_guest_order_during_business_hours(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-24 12:00:00', 'Asia/Muscat'));

        [$shop, $product] = $this->makeMenu();
        $shop->update([
            'branding' => [
                'timezone' => 'Asia/Muscat',
                'business_hours' => [
                    'wednesday' => ['open' => '09:00', 'close' => '22:00', 'closed' => false],
                ],
            ],
        ]);

        $result = $this->service()->create($shop, [$this->line($product, 1)], [
            'idempotency_key' => (string) Str::uuid(),
            'customer_name' => 'Layla',
            'loyalty_phone' => '95123456',
        ]);

        $this->assertSame('created', $result['outcome']);
        $this->assertSame(1, Order::where('shop_id', $shop->id)->count());
    }

    public function test_create_accepts_guest_order_during_overnight_business_hours(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-25 01:00:00', 'Asia/Muscat'));

        [$shop, $product] = $this->makeMenu();
        $shop->update([
            'branding' => [
                'timezone' => 'Asia/Muscat',
                'business_hours' => [
                    'wednesday' => ['open' => '18:00', 'close' => '02:00', 'closed' => false],
                    'thursday' => ['open' => '09:00', 'close' => '22:00', 'closed' => false],
                ],
            ],
        ]);

        $result = $this->service()->create($shop, [$this->line($product, 1)], [
            'idempotency_key' => (string) Str::uuid(),
            'customer_name' => 'Layla',
            'loyalty_phone' => '95123456',
        ]);

        $this->assertSame('created', $result['outcome']);
        $this->assertSame(1, Order::where('shop_id', $shop->id)->count());
    }

    public function test_quote_uses_safe_timezone_fallback_for_stale_branding_data(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-24 12:00:00', config('app.timezone')));

        [$shop, $product] = $this->makeMenu();
        $shop->update([
            'branding' => [
                'timezone' => 'Mars/Olympus',
                'business_hours' => [
                    'wednesday' => ['open' => '09:00', 'close' => '22:00', 'closed' => false],
                ],
            ],
        ]);

        $quote = $this->service()->quote($shop, [$this->line($product, 1)]);

        $this->assertSame('ok', $quote['outcome']);
    }

    public function test_create_does_not_persist_unavailable_cart(): void
    {
        [$shop, $product] = $this->makeMenu();
        $cart = [$this->line($product, 1)];
        $product->update(['is_visible' => false]);

        $result = $this->service()->create($shop, $cart, [
            'idempotency_key' => (string) Str::uuid(),
            'customer_name' => 'Layla',
            'loyalty_phone' => '95123456',
        ]);

        $this->assertSame('unavailable', $result['outcome']);
        $this->assertSame(0, Order::count());
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
