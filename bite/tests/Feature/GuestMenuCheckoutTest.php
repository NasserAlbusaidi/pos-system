<?php

namespace Tests\Feature;

use App\Livewire\GuestMenu;
use App\Livewire\KitchenDisplay;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Services\PrintNodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Phase 4 (#24): cart/review re-skin + checkout (pay-at-counter) + order note.
 *
 * Covers the pilot guarantees: order is created 'unpaid' with NO payment_method
 * set by the guest, checkout requires name + phone, the order-level note
 * persists end-to-end and reaches the kitchen, and the totals expose no service
 * fee / VAT / voucher lines (hidden per scope #29).
 */
class GuestMenuCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_note_persists_end_to_end(): void
    {
        [$shop, $product] = $this->createMenu();

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->set('customerName', 'Layla')
            ->set('loyaltyPhone', '95123456')
            ->set('orderNote', '  Table by the window, please  ')
            ->call('submitOrder');

        $order = Order::firstOrFail();
        $this->assertSame('Table by the window, please', $order->order_note);
    }

    public function test_order_note_is_trimmed_and_capped_at_500(): void
    {
        [$shop, $product] = $this->createMenu();

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->set('customerName', 'Layla')
            ->set('loyaltyPhone', '95123456')
            ->set('orderNote', str_repeat('x', 800))
            ->call('submitOrder');

        $this->assertSame(500, mb_strlen(Order::firstOrFail()->order_note));
    }

    public function test_blank_order_note_persists_as_null(): void
    {
        [$shop, $product] = $this->createMenu();

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->set('customerName', 'Layla')
            ->set('loyaltyPhone', '95123456')
            ->set('orderNote', '   ')
            ->call('submitOrder');

        $this->assertNull(Order::firstOrFail()->order_note);
    }

    public function test_checkout_requires_name(): void
    {
        [$shop, $product] = $this->createMenu();

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->set('customerName', '   ')
            ->set('loyaltyPhone', '95123456')
            ->call('submitOrder')
            ->assertSet('showReviewModal', true);

        $this->assertSame(0, Order::count());
    }

    public function test_checkout_requires_phone(): void
    {
        [$shop, $product] = $this->createMenu();

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->set('customerName', 'Layla')
            ->set('loyaltyPhone', '')
            ->call('submitOrder')
            ->assertSet('showReviewModal', true);

        $this->assertSame(0, Order::count());
    }

    public function test_checkout_rejects_invalid_phone(): void
    {
        [$shop, $product] = $this->createMenu();

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->set('customerName', 'Layla')
            ->set('loyaltyPhone', '123') // too short for normalizePhone()
            ->call('submitOrder');

        $this->assertSame(0, Order::count());
    }

    public function test_customer_name_persists_and_is_trimmed(): void
    {
        [$shop, $product] = $this->createMenu();

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->set('customerName', '  Layla  ')
            ->set('loyaltyPhone', '95123456')
            ->call('submitOrder');

        $this->assertSame('Layla', Order::firstOrFail()->customer_name);
    }

    public function test_order_created_unpaid_with_no_payment_method(): void
    {
        [$shop, $product] = $this->createMenu();

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->set('customerName', 'Layla')
            ->set('loyaltyPhone', '95123456')
            ->call('submitOrder');

        $order = Order::firstOrFail();
        $this->assertSame('unpaid', $order->status);
        $this->assertNull($order->payment_method);
    }

    public function test_review_screen_shows_no_service_fee_vat_or_voucher_lines(): void
    {
        [$shop, $product] = $this->createMenu();

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->set('showReviewModal', true)
            ->assertDontSee('Service fee')
            ->assertDontSee('Service Fee')
            ->assertDontSee('Voucher')
            ->assertDontSee('VAT')
            ->assertSee(__('guest.subtotal'))
            ->assertSee(__('guest.total'));
    }

    /**
     * Phase 7b (#29) scope guard: the guest checkout must STRIP — not just hide —
     * every out-of-scope feature. The pilot excludes online payment / Thawani,
     * vouchers/promo/coupon codes, a service-fee line, a separate VAT line item,
     * and waiter-call. This asserts none of those controls render and that the
     * only payment method offered is pay-at-counter. Legitimate per-product tax
     * (the summary "Tax" row) is in scope and intentionally NOT asserted absent.
     */
    public function test_checkout_strips_all_out_of_scope_features(): void
    {
        [$shop, $product] = $this->createMenu();

        $component = Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->set('showReviewModal', true);

        // No voucher / promo / coupon / discount-code field.
        $component->assertDontSee('voucher', false)
            ->assertDontSee('Voucher')
            ->assertDontSee('promo', false)
            ->assertDontSee('Promo')
            ->assertDontSee('coupon', false)
            ->assertDontSee('Coupon')
            ->assertDontSeeHtml('wire:model="voucher')
            ->assertDontSeeHtml('wire:model="promoCode')
            ->assertDontSeeHtml('wire:model="couponCode')
            ->assertDontSeeHtml('applyVoucher');

        // No extra service-fee summary line.
        $component->assertDontSee('Service fee')
            ->assertDontSee('Service Fee')
            ->assertDontSee('service_fee', false)
            ->assertDontSee('serviceFee', false);

        // No separate VAT summary line item (legitimate per-product Tax row stays).
        $component->assertDontSee('VAT');

        // No waiter-call control.
        $component->assertDontSee('waiter', false)
            ->assertDontSee('Waiter')
            ->assertDontSee('Call waiter')
            ->assertDontSeeHtml('callWaiter')
            ->assertDontSeeHtml('wire:click="callWaiter');

        // No online-payment / Thawani path or guest-set payment method.
        $component->assertDontSee('Thawani')
            ->assertDontSee('thawani', false)
            ->assertDontSee('Pay online')
            ->assertDontSee('Pay now')
            ->assertDontSeeHtml('wire:model="paymentMethod')
            ->assertDontSeeHtml('wire:model="payment_method')
            ->assertDontSeeHtml('payOnline')
            ->assertDontSeeHtml('<select');

        // The only payment method shown is pay-at-counter.
        $component->assertSee(__('guest.pay_at_counter'))
            ->assertSee(__('guest.subtotal'))
            ->assertSee(__('guest.tax'))
            ->assertSee(__('guest.total'));
    }

    /**
     * Phase 7b (#29) guard, Arabic side: the same out-of-scope features stay
     * stripped under RTL/Arabic, and the only payment method is pay-at-counter.
     */
    public function test_checkout_strips_out_of_scope_features_in_arabic(): void
    {
        [$shop, $product] = $this->createMenu();

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('switchLanguage', 'ar')
            ->call('addToCart', $product->id)
            ->set('showReviewModal', true)
            ->assertDontSee('Thawani')
            ->assertDontSee('Voucher')
            ->assertDontSee('Service fee')
            ->assertDontSee('VAT')
            ->assertDontSee('Waiter')
            ->assertDontSeeHtml('<select')
            ->assertSee(__('guest.pay_at_counter', [], 'ar'));
    }

    public function test_order_note_renders_on_kds(): void
    {
        $shop = Shop::factory()->create();
        $user = User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'kitchen',
        ]);

        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'paid',
            'total_amount' => 5.00,
            'paid_at' => now(),
            'order_note' => 'Allergy: shellfish — whole table',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_name_snapshot_en' => 'Cardamom Bun',
            'product_name_snapshot_ar' => null,
            'price_snapshot' => 1.200,
            'quantity' => 1,
        ]);

        Livewire::actingAs($user)
            ->test(KitchenDisplay::class)
            ->assertSee('Cardamom Bun')
            ->assertSee('Allergy: shellfish — whole table');
    }

    public function test_kds_omits_order_note_block_when_absent(): void
    {
        $shop = Shop::factory()->create();
        $user = User::factory()->create([
            'shop_id' => $shop->id,
            'role' => 'kitchen',
        ]);

        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'paid',
            'total_amount' => 2.500,
            'paid_at' => now(),
            'order_note' => null,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_name_snapshot_en' => 'Plain Loaf',
            'product_name_snapshot_ar' => null,
            'price_snapshot' => 2.500,
            'quantity' => 1,
        ]);

        Livewire::actingAs($user)
            ->test(KitchenDisplay::class)
            ->assertSee('Plain Loaf')
            ->assertDontSeeHtml('kds-order-note');
    }

    public function test_order_note_renders_on_printed_kitchen_ticket(): void
    {
        $shop = Shop::factory()->create();
        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'paid',
            'total_amount' => 1.200,
            'paid_at' => now(),
            'order_note' => 'Ring the bell when ready',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_name_snapshot_en' => 'Cardamom Bun',
            'product_name_snapshot_ar' => null,
            'price_snapshot' => 1.200,
            'quantity' => 1,
        ]);

        $ticket = $this->buildTicket($order);

        $this->assertStringContainsString('ORDER NOTE: Ring the bell when ready', $ticket);
    }

    public function test_printed_ticket_omits_order_note_when_absent(): void
    {
        $shop = Shop::factory()->create();
        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'paid',
            'total_amount' => 2.500,
            'paid_at' => now(),
            'order_note' => null,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_name_snapshot_en' => 'Plain Loaf',
            'product_name_snapshot_ar' => null,
            'price_snapshot' => 2.500,
            'quantity' => 1,
        ]);

        $this->assertStringNotContainsString('ORDER NOTE:', $this->buildTicket($order));
    }

    public function test_group_order_note_persists_end_to_end(): void
    {
        [$shop, $product] = $this->createMenu();

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('createGroup')
            ->call('addToCart', $product->id)
            ->set('customerName', 'Layla')
            ->set('loyaltyPhone', '95123456')
            ->set('orderNote', 'Two forks please')
            ->call('submitOrder');

        $this->assertSame('Two forks please', Order::firstOrFail()->order_note);
    }

    private function buildTicket(Order $order): string
    {
        $method = new ReflectionMethod(PrintNodeService::class, 'buildTicket');
        $method->setAccessible(true);

        return $method->invoke(new PrintNodeService, $order, 'kitchen');
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
