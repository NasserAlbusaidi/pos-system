<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemModifier;
use App\Models\Payment;
use App\Models\Shop;
use App\Services\PrintNodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class PrintableOutputReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_does_not_repeat_modifier_amount_when_line_total_already_includes_it(): void
    {
        $shop = Shop::factory()->create([
            'currency_code' => 'OMR',
            'currency_symbol' => 'OMR',
            'currency_decimals' => 3,
        ]);
        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'paid',
            'customer_name' => 'Layla',
            'subtotal_amount' => 1.900,
            'tax_amount' => 0,
            'total_amount' => 1.900,
            'paid_at' => now(),
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_name_snapshot_en' => 'Latte',
            'product_name_snapshot_ar' => null,
            'price_snapshot' => 1.900,
            'quantity' => 1,
        ]);
        OrderItemModifier::create([
            'order_item_id' => $item->id,
            'modifier_option_name_snapshot_en' => 'Large',
            'modifier_option_name_snapshot_ar' => null,
            'price_adjustment_snapshot' => 0.400,
        ]);
        Payment::forceCreate([
            'shop_id' => $shop->id,
            'order_id' => $order->id,
            'amount' => 1.900,
            'method' => 'cash',
            'paid_at' => now(),
        ]);

        $this->blade(
            '<x-printable-receipt :order="$order" :shop="$shop" />',
            ['order' => $order->fresh(['items.modifiers', 'payments']), 'shop' => $shop],
        )
            ->assertSee('Latte')
            ->assertSee('Large')
            ->assertSee('OMR 1.900')
            ->assertDontSee('OMR 0.400');
    }

    public function test_unpaid_receipt_shows_balance_due_warning(): void
    {
        $shop = Shop::factory()->create([
            'currency_code' => 'OMR',
            'currency_symbol' => 'OMR',
            'currency_decimals' => 3,
        ]);
        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'customer_name' => 'Counter Guest',
            'subtotal_amount' => 10.000,
            'tax_amount' => 0,
            'total_amount' => 10.000,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_name_snapshot_en' => 'Lunch Set',
            'product_name_snapshot_ar' => null,
            'price_snapshot' => 10.000,
            'quantity' => 1,
        ]);

        $this->blade(
            '<x-printable-receipt :order="$order" :shop="$shop" />',
            ['order' => $order->fresh(['items.modifiers', 'payments']), 'shop' => $shop],
        )
            ->assertSee('UNPAID')
            ->assertSee('Balance due')
            ->assertSee('OMR 10.000');
    }

    public function test_invoice_displays_line_total_for_multi_quantity_items(): void
    {
        $shop = Shop::factory()->create([
            'currency_code' => 'OMR',
            'currency_symbol' => 'OMR',
            'currency_decimals' => 3,
        ]);
        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'paid',
            'subtotal_amount' => 3.000,
            'tax_amount' => 0,
            'total_amount' => 3.000,
            'paid_at' => now(),
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_name_snapshot_en' => 'Karak Tea',
            'product_name_snapshot_ar' => null,
            'price_snapshot' => 1.500,
            'quantity' => 2,
        ]);

        $this->view('invoices.order', ['order' => $order->fresh(['items.modifiers', 'shop'])])
            ->assertSee('Karak Tea')
            ->assertSee('2')
            ->assertSee('OMR 3.000')
            ->assertDontSee('OMR 1.500');
    }

    public function test_invoice_displays_payment_ledger_and_balance_due(): void
    {
        $shop = Shop::factory()->create([
            'currency_code' => 'OMR',
            'currency_symbol' => 'OMR',
            'currency_decimals' => 3,
        ]);
        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'subtotal_amount' => 10.000,
            'tax_amount' => 0,
            'total_amount' => 10.000,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_name_snapshot_en' => 'Family Platter',
            'product_name_snapshot_ar' => null,
            'price_snapshot' => 10.000,
            'quantity' => 1,
        ]);
        Payment::forceCreate([
            'shop_id' => $shop->id,
            'order_id' => $order->id,
            'amount' => 4.250,
            'method' => 'cash',
            'paid_at' => now(),
        ]);

        $this->view('invoices.order', ['order' => $order->fresh(['items.modifiers', 'payments', 'shop'])])
            ->assertSee('Payment')
            ->assertSee('Cash')
            ->assertSee('Amount paid')
            ->assertSee('OMR 4.250')
            ->assertSee('Balance due')
            ->assertSee('OMR 5.750')
            ->assertSee('Payment due');
    }

    public function test_printable_outputs_ignore_mismatched_shop_payments(): void
    {
        $shop = Shop::factory()->create([
            'currency_code' => 'OMR',
            'currency_symbol' => 'OMR',
            'currency_decimals' => 3,
        ]);
        $otherShop = Shop::factory()->create();
        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'customer_name' => 'Counter Guest',
            'subtotal_amount' => 10.000,
            'tax_amount' => 0,
            'total_amount' => 10.000,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_name_snapshot_en' => 'Family Platter',
            'product_name_snapshot_ar' => null,
            'price_snapshot' => 10.000,
            'quantity' => 1,
        ]);
        Payment::forceCreate([
            'shop_id' => $otherShop->id,
            'order_id' => $order->id,
            'amount' => 55.000,
            'method' => 'card',
            'paid_at' => now(),
        ]);

        $order = $order->fresh(['items.modifiers', 'payments', 'shop']);

        $this->blade(
            '<x-printable-receipt :order="$order" :shop="$shop" />',
            ['order' => $order, 'shop' => $shop],
        )
            ->assertSee('Balance due')
            ->assertSee('OMR 10.000')
            ->assertDontSee('OMR 55.000')
            ->assertDontSee('Card');

        $this->view('invoices.order', ['order' => $order])
            ->assertSee('Payment due')
            ->assertSee('Balance due')
            ->assertSee('OMR 10.000')
            ->assertDontSee('OMR 55.000')
            ->assertDontSee('Card');
    }

    public function test_printable_outputs_use_persisted_order_source_label(): void
    {
        $shop = Shop::factory()->create([
            'currency_code' => 'OMR',
            'currency_symbol' => 'OMR',
            'currency_decimals' => 3,
        ]);
        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'source' => 'counter',
            'status' => 'paid',
            'customer_name' => 'Aisha',
            'subtotal_amount' => 2.000,
            'tax_amount' => 0,
            'total_amount' => 2.000,
            'paid_at' => now(),
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_name_snapshot_en' => 'Sourdough',
            'product_name_snapshot_ar' => null,
            'price_snapshot' => 2.000,
            'quantity' => 1,
        ]);

        $order = $order->fresh(['items.modifiers', 'payments', 'shop']);

        $this->blade(
            '<x-printable-receipt :order="$order" :shop="$shop" />',
            ['order' => $order, 'shop' => $shop],
        )
            ->assertSee('Counter')
            ->assertDontSee('Guest Pickup');

        $this->view('invoices.order', ['order' => $order])
            ->assertSee('Counter')
            ->assertDontSee('Guest Pickup');

        $this->assertStringContainsString('Order Type: Counter', $this->buildTicket($order));
        $this->assertStringNotContainsString('Order Type: Guest Pickup', $this->buildTicket($order));
    }

    private function buildTicket(Order $order): string
    {
        $method = new ReflectionMethod(PrintNodeService::class, 'buildTicket');
        $method->setAccessible(true);

        return $method->invoke(new PrintNodeService, $order, 'kitchen');
    }
}
