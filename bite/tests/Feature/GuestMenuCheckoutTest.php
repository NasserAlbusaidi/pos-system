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
 * Covers the pilot guarantees: order is created 'unpaid' while storing the
 * guest's preferred payment path, checkout requires name + phone, the order-level note
 * persists end-to-end and reaches the kitchen, and the totals expose the
 * checkout service / VAT display while omitting waiter-call.
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

    public function test_order_created_unpaid_with_default_counter_payment_method(): void
    {
        [$shop, $product] = $this->createMenu();

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->set('customerName', 'Layla')
            ->set('loyaltyPhone', '95123456')
            ->call('submitOrder');

        $order = Order::firstOrFail();
        $this->assertSame('unpaid', $order->status);
        $this->assertSame('counter', $order->payment_method);
    }

    public function test_guest_selected_online_payment_method_persists_on_unpaid_order(): void
    {
        [$shop, $product] = $this->createMenu();

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->set('customerName', 'Layla')
            ->set('loyaltyPhone', '95123456')
            ->set('paymentMethod', 'online')
            ->call('submitOrder');

        $order = Order::firstOrFail();
        $this->assertSame('unpaid', $order->status);
        $this->assertSame('online', $order->payment_method);
    }

    public function test_checkout_rejects_tampered_payment_method(): void
    {
        [$shop, $product] = $this->createMenu();

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->set('customerName', 'Layla')
            ->set('loyaltyPhone', '95123456')
            ->set('paymentMethod', 'crypto')
            ->call('submitOrder')
            ->assertSet('showReviewModal', true)
            ->assertSee(__('guest.payment_method_invalid'));

        $this->assertSame(0, Order::count());
    }

    public function test_checkout_details_step_shows_voucher_field_and_cart_summary(): void
    {
        [$shop, $product] = $this->createMenu();

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->call('toggleReview')
            ->assertSee(__('guest.voucher'))
            ->assertSee(__('guest.promo_code'))
            ->assertSee(__('guest.voucher_placeholder'))
            ->assertSee(__('guest.apply_voucher'))
            ->assertSee(__('guest.subtotal'))
            ->assertSee(__('guest.service'))
            ->assertSee(__('guest.vat_5'))
            ->assertSee(__('guest.total'));
    }

    public function test_review_order_opens_a_full_checkout_screen_instead_of_the_cart_sheet(): void
    {
        [$shop, $product] = $this->createMenu();

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->call('toggleReview')
            ->assertSet('screen', 'checkout')
            ->assertSet('showReviewModal', true)
            ->assertSeeHtml('data-route-name="checkout"')
            ->assertSeeHtml('bite-checkout-screen')
            ->assertSeeHtml('bite-checkout-main')
            ->assertDontSeeHtml('bite-sheet--cart')
            ->assertDontSeeHtml('bite-cart-status')
            ->assertSee(__('guest.your_cart'))
            ->assertSet('checkoutDetailsVisible', true)
            ->assertSee(__('guest.place_order'))
            ->assertSee(__('guest.payment_method_label'))
            ->assertSee(__('guest.voucher'))
            ->assertDontSeeHtml('wire:click="showCheckoutDetails"');
    }

    public function test_checkout_screen_uses_cart_review_layout(): void
    {
        [$shop, $product] = $this->createMenu();

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->call('toggleReview')
            ->assertSeeHtml('bite-cart-top')
            ->assertDontSeeHtml('bite-cart-status')
            ->assertSeeHtml('bite-checkout-card')
            ->assertSeeHtml('bite-checkout-qty')
            ->assertSeeHtml('bite-checkout-note')
            ->assertSeeHtml('bite-checkout-total-card')
            ->assertSeeHtml('bite-checkout-powered')
            ->assertSee(__('guest.your_cart'))
            ->assertSee('AR')
            ->assertSee(__('guest.order_note'))
            ->assertSee(__('guest.service'))
            ->assertSee(__('guest.vat_5'))
            ->assertSee(__('guest.place_order'))
            ->assertDontSeeHtml('wire:click="showCheckoutDetails"');
    }

    public function test_powered_by_footer_renders_after_checkout_fields(): void
    {
        [$shop, $product] = $this->createMenu();

        $html = Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->call('toggleReview')
            ->html();

        $paymentPosition = strpos($html, __('guest.payment_method_label'));
        $namePosition = strpos($html, __('guest.your_name'));
        $phonePosition = strpos($html, __('guest.phone_label'));
        $poweredPosition = strpos($html, 'bite-checkout-powered');

        $this->assertNotFalse($paymentPosition);
        $this->assertNotFalse($namePosition);
        $this->assertNotFalse($phonePosition);
        $this->assertNotFalse($poweredPosition);
        $this->assertGreaterThan($paymentPosition, $poweredPosition);
        $this->assertGreaterThan($namePosition, $poweredPosition);
        $this->assertGreaterThan($phonePosition, $poweredPosition);
    }

    public function test_submitted_order_total_includes_checkout_service_fee(): void
    {
        [$shop, $product] = $this->createMenu();

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->set('customerName', 'Layla')
            ->set('loyaltyPhone', '95123456')
            ->call('submitOrder');

        $order = Order::firstOrFail();
        $this->assertEqualsWithDelta(0.500, (float) $order->subtotal_amount, 0.001);
        $this->assertEqualsWithDelta(0.700, (float) $order->total_amount, 0.001);
    }

    public function test_checkout_voucher_code_can_be_entered_and_applied(): void
    {
        [$shop, $product] = $this->createMenu();

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->set('showReviewModal', true)
            ->set('checkoutDetailsVisible', true)
            ->set('voucherCode', ' pickup10 ')
            ->assertSet('voucherCode', ' PICKUP10 ')
            ->assertSet('voucherApplied', false)
            ->call('applyVoucher')
            ->assertSet('voucherCode', 'PICKUP10')
            ->assertSet('voucherApplied', true)
            ->assertSee(__('guest.voucher_applied', ['code' => 'PICKUP10']));
    }

    /**
     * Phase 7b (#29) scope guard: the guest checkout must STRIP — not just hide —
     * every out-of-scope feature. The pilot excludes Thawani gateway wiring,
     * coupon discount calculation and waiter-call. Voucher entry, service,
     * VAT display, and the two payment paths are in scope.
     */
    public function test_checkout_strips_all_out_of_scope_features(): void
    {
        [$shop, $product] = $this->createMenu();

        $component = Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('addToCart', $product->id)
            ->set('showReviewModal', true)
            ->set('checkoutDetailsVisible', true);

        // Voucher entry is in scope, but coupon calculation plumbing is not.
        $component->assertSee(__('guest.voucher'))
            ->assertSee(__('guest.promo_code'))
            ->assertSee(__('guest.voucher_placeholder'))
            ->assertSeeHtml('wire:model.live.debounce.250ms="voucherCode"')
            ->assertSeeHtml('wire:click="applyVoucher"')
            ->assertDontSee('coupon', false)
            ->assertDontSee('Coupon')
            ->assertDontSeeHtml('wire:model="promoCode')
            ->assertDontSeeHtml('wire:model="couponCode')
            ->assertDontSeeHtml('applyCoupon');

        $component->assertSee(__('guest.service'))
            ->assertSee(__('guest.vat_5'))
            ->assertDontSee('service_fee', false)
            ->assertDontSee('serviceFee', false);

        // No waiter-call control.
        $component->assertDontSee('waiter', false)
            ->assertDontSee('Waiter')
            ->assertDontSee('Call waiter')
            ->assertDontSeeHtml('callWaiter')
            ->assertDontSeeHtml('wire:click="callWaiter');

        // No Thawani gateway path.
        $component->assertDontSee('Thawani')
            ->assertDontSee('thawani', false)
            ->assertDontSee('Pay now')
            ->assertDontSeeHtml('wire:model="payment_method')
            ->assertDontSeeHtml('payOnline')
            ->assertDontSeeHtml('<select')
            ->assertSeeHtml('bite-payment-picker')
            ->assertSeeHtml('role="listbox"')
            ->assertSeeHtml('wire:click="$set(\'paymentMethod\', \'counter\')"')
            ->assertSeeHtml('wire:click="$set(\'paymentMethod\', \'online\')"');

        // The only payment paths shown are counter payment and online payment.
        $component->assertSee(__('guest.payment_method_label'))
            ->assertSee(__('guest.payment_method_counter'))
            ->assertSee(__('guest.payment_method_online'))
            ->assertSee(__('guest.subtotal'))
            ->assertSee(__('guest.vat_5'))
            ->assertSee(__('guest.total'));
    }

    /**
     * Phase 7b (#29) guard, Arabic side: the same out-of-scope features stay
     * stripped under RTL/Arabic, and the only payment paths are counter and online.
     */
    public function test_checkout_strips_out_of_scope_features_in_arabic(): void
    {
        [$shop, $product] = $this->createMenu();

        Livewire::test(GuestMenu::class, ['shop' => $shop])
            ->call('switchLanguage', 'ar')
            ->call('addToCart', $product->id)
            ->set('showReviewModal', true)
            ->set('checkoutDetailsVisible', true)
            ->assertDontSee('Thawani')
            ->assertSee(__('guest.voucher', [], 'ar'))
            ->assertSee(__('guest.promo_code', [], 'ar'))
            ->assertSee(__('guest.voucher_placeholder', [], 'ar'))
            ->assertSee(__('guest.service', [], 'ar'))
            ->assertSee(__('guest.vat_5', [], 'ar'))
            ->assertSee('EN')
            ->assertDontSee('Waiter')
            ->assertSeeHtml('wire:model.live.debounce.250ms="voucherCode"')
            ->assertSeeHtml('wire:click="applyVoucher"')
            ->assertSeeHtml('bite-payment-picker')
            ->assertSeeHtml('wire:click="$set(\'paymentMethod\', \'counter\')"')
            ->assertSeeHtml('wire:click="$set(\'paymentMethod\', \'online\')"')
            ->assertSee(__('guest.payment_method_label', [], 'ar'))
            ->assertSee(__('guest.payment_method_counter', [], 'ar'))
            ->assertSee(__('guest.payment_method_online', [], 'ar'));
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
