<?php

namespace Tests\Feature\Livewire;

use App\Livewire\PosDashboard;
use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PricingRule;
use App\Models\Product;
use App\Models\ShiftClosure;
use App\Models\Shop;
use App\Models\User;
use App\Support\ShopClock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Walk-in / counter order entry (#56): staff build a cart from the menu and
 * settle it at the counter. The order is re-priced server-side (never trusts a
 * client price), tenant-scoped, and full payment is taken on charge.
 */
class PosWalkInOrderTest extends TestCase
{
    use RefreshDatabase;

    private Shop $shop;

    private Product $loaf;   // 2.500

    private Product $coffee; // 1.200

    protected function setUp(): void
    {
        parent::setUp();

        $this->shop = Shop::create(['name' => 'Bite', 'slug' => 'bite', 'tax_rate' => 0]);
        $category = Category::create(['shop_id' => $this->shop->id, 'name_en' => 'Bakery', 'is_active' => true]);

        $this->loaf = $this->makeProduct($category, 'Sourdough loaf', 2.500);
        $this->coffee = $this->makeProduct($category, 'Latte', 1.200);
    }

    private function makeProduct(Category $category, string $name, float $price): Product
    {
        $product = new Product([
            'category_id' => $category->id,
            'name_en' => $name,
            'price' => $price,
            'is_available' => true,
            'is_visible' => true,
        ]);
        $product->shop_id = $this->shop->id; // guarded
        $product->save();

        return $product;
    }

    private function server(): User
    {
        return User::factory()->create(['shop_id' => $this->shop->id, 'role' => 'server']);
    }

    public function test_staff_can_create_and_charge_a_walk_in_order(): void
    {
        Livewire::actingAs($this->server())
            ->test(PosDashboard::class)
            ->call('openNewOrder')
            ->call('addToCart', $this->loaf->id)
            ->call('addToCart', $this->loaf->id)
            ->call('addToCart', $this->coffee->id)
            ->set('newOrderName', 'Aisha')
            ->call('chargeNewOrder', 'cash')
            ->assertSet('newOrderError', null)
            ->assertSet('showNewOrder', false);

        $order = Order::where('shop_id', $this->shop->id)->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertSame('paid', $order->status);
        $this->assertSame('counter', $order->source);
        $this->assertSame('Aisha', $order->customer_name);
        // Re-priced server-side: 2.500 x2 + 1.200 = 6.200
        $this->assertEqualsWithDelta(6.200, (float) $order->total_amount, 0.0001);
        $this->assertSame(2, $order->items()->count());

        $this->assertSame(1, Payment::where('order_id', $order->id)->count());
        $this->assertEqualsWithDelta(6.200, (float) Payment::where('order_id', $order->id)->sum('amount'), 0.0001);
        $this->assertSame('cash', $order->fresh()->payment_method);
    }

    public function test_blank_customer_name_defaults_to_walk_in(): void
    {
        Livewire::actingAs($this->server())
            ->test(PosDashboard::class)
            ->call('openNewOrder')
            ->call('addToCart', $this->loaf->id)
            ->call('chargeNewOrder', 'card')
            ->assertSet('showNewOrder', false);

        $order = Order::where('shop_id', $this->shop->id)->latest('id')->first();
        $this->assertSame('Walk-in', $order->customer_name);
        $this->assertSame('paid', $order->status);
    }

    public function test_walk_in_charge_rejects_unknown_payment_method_instead_of_relabeling_as_cash(): void
    {
        Livewire::actingAs($this->server())
            ->test(PosDashboard::class)
            ->call('openNewOrder')
            ->call('addToCart', $this->loaf->id)
            ->call('chargeNewOrder', 'crypto')
            ->assertSet('newOrderError', 'Choose a valid payment method.')
            ->assertSet('showNewOrder', true);

        $this->assertSame(0, Order::where('shop_id', $this->shop->id)->count());
        $this->assertSame(0, Payment::where('shop_id', $this->shop->id)->count());
    }

    public function test_walk_in_charge_after_shift_close_does_not_create_an_unpaid_counter_order(): void
    {
        $server = $this->server();
        ShiftClosure::forceCreate([
            'shop_id' => $this->shop->id,
            'business_date' => ShopClock::localDate($this->shop),
            'closed_by' => $server->id,
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

        Livewire::actingAs($server)
            ->test(PosDashboard::class)
            ->call('openNewOrder')
            ->call('addToCart', $this->loaf->id)
            ->call('chargeNewOrder', 'cash')
            ->assertSet('newOrderError', 'Shift is closed for today. Payments are locked until the next business day.')
            ->assertSet('showNewOrder', true);

        $this->assertSame(0, Order::where('shop_id', $this->shop->id)->count());
        $this->assertSame(0, Payment::where('shop_id', $this->shop->id)->count());
    }

    public function test_walk_in_cart_displays_active_time_priced_amount_before_charge(): void
    {
        PricingRule::create([
            'shop_id' => $this->shop->id,
            'product_id' => $this->loaf->id,
            'name' => 'Counter happy hour',
            'discount_type' => 'fixed',
            'discount_value' => 0.500,
            'start_time' => '00:00',
            'end_time' => '23:59',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->server())
            ->test(PosDashboard::class)
            ->call('openNewOrder')
            ->call('addToCart', $this->loaf->id)
            ->assertSet("posCart.{$this->loaf->id}.price", 2.0)
            ->call('chargeNewOrder', 'cash')
            ->assertSet('newOrderError', null)
            ->assertSet('showNewOrder', false);

        $order = Order::where('shop_id', $this->shop->id)->latest('id')->firstOrFail();

        $this->assertEqualsWithDelta(2.000, (float) $order->total_amount, 0.0001);
        $this->assertEqualsWithDelta(2.000, (float) Payment::where('order_id', $order->id)->sum('amount'), 0.0001);
    }

    public function test_product_with_required_modifier_opens_option_picker_before_carting(): void
    {
        $this->attachSizeModifier($this->coffee, required: true);

        Livewire::actingAs($this->server())
            ->test(PosDashboard::class)
            ->call('openNewOrder')
            ->call('addToCart', $this->coffee->id)
            ->assertSet('customizingPosProductId', $this->coffee->id)
            ->assertSet('posCart', []);
    }

    public function test_staff_can_create_and_charge_a_walk_in_order_with_required_modifier(): void
    {
        [$group, $large] = $this->attachSizeModifier($this->coffee, required: true);

        Livewire::actingAs($this->server())
            ->test(PosDashboard::class)
            ->call('openNewOrder')
            ->call('addToCart', $this->coffee->id)
            ->call('selectPosModifier', $group->id, $large->id, false)
            ->call('confirmPosModifierSelection')
            ->set('newOrderName', 'Muna')
            ->call('chargeNewOrder', 'card')
            ->assertSet('newOrderError', null)
            ->assertSet('showNewOrder', false);

        $order = Order::where('shop_id', $this->shop->id)->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertSame('paid', $order->status);
        $this->assertSame('Muna', $order->customer_name);
        $this->assertSame('card', $order->payment_method);
        $this->assertEqualsWithDelta(1.700, (float) $order->total_amount, 0.0001);

        $item = $order->items()->first();
        $this->assertEqualsWithDelta(1.700, (float) $item->price_snapshot, 0.0001);
        $this->assertDatabaseHas('order_item_modifiers', [
            'order_item_id' => $item->id,
            'modifier_option_name_snapshot_en' => 'Large',
            'price_adjustment_snapshot' => 0.500,
        ]);
    }

    public function test_empty_cart_cannot_be_charged(): void
    {
        Livewire::actingAs($this->server())
            ->test(PosDashboard::class)
            ->call('openNewOrder')
            ->call('chargeNewOrder', 'cash')
            ->assertNotSet('newOrderError', null)
            ->assertSet('showNewOrder', true);

        $this->assertSame(0, Order::where('shop_id', $this->shop->id)->count());
    }

    public function test_foreign_shop_product_cannot_be_added(): void
    {
        $otherShop = Shop::create(['name' => 'Other', 'slug' => 'other']);
        $otherCat = Category::create(['shop_id' => $otherShop->id, 'name_en' => 'X', 'is_active' => true]);
        $foreign = $this->makeProductFor($otherShop, $otherCat, 'Foreign cake', 9.000);

        Livewire::actingAs($this->server())
            ->test(PosDashboard::class)
            ->call('openNewOrder')
            ->call('addToCart', $foreign->id)
            ->assertSet('posCart', []);
    }

    private function makeProductFor(Shop $shop, Category $category, string $name, float $price): Product
    {
        $product = new Product([
            'category_id' => $category->id,
            'name_en' => $name,
            'price' => $price,
            'is_available' => true,
            'is_visible' => true,
        ]);
        $product->shop_id = $shop->id;
        $product->save();

        return $product;
    }

    private function attachSizeModifier(Product $product, bool $required): array
    {
        $group = ModifierGroup::create([
            'shop_id' => $this->shop->id,
            'name_en' => 'Size',
            'name_ar' => 'الحجم',
            'min_selection' => $required ? 1 : 0,
            'max_selection' => 1,
        ]);

        $large = ModifierOption::create([
            'modifier_group_id' => $group->id,
            'name_en' => 'Large',
            'name_ar' => 'كبير',
            'price_adjustment' => 0.500,
        ]);

        $product->modifierGroups()->attach($group->id);

        return [$group, $large];
    }
}
