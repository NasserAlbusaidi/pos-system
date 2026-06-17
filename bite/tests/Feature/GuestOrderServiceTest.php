<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Shop;
use App\Services\GuestOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GuestOrderServiceTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_quote_rejects_cart_over_quantity_cap(): void
    {
        [$shop, $product] = $this->makeMenu();

        $quote = $this->service()->quote($shop, [$this->line($product, 100)]);

        $this->assertSame('invalid', $quote['outcome']);
        $this->assertSame('order', $quote['error_field']);
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
}
