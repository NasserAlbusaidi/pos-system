<?php

namespace Tests\Feature\Livewire;

use App\Livewire\PosDashboard;
use App\Models\AuditLog;
use App\Models\LoyaltyCustomer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ShiftClosure;
use App\Models\Shop;
use App\Models\User;
use App\Services\LoyaltyService;
use App\Services\PrintNodeService;
use App\Services\StripeRefundService;
use App\Support\ShopClock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

/**
 * Pilot payment policy (#57): an order settlement must cover the full balance.
 * Partial payments are rejected at the source so no order can sit 'unpaid'
 * forever. Multiple tenders (split by guest/method) are allowed as long as they
 * sum to the full balance.
 */
class PosPaymentFullBalanceTest extends TestCase
{
    use RefreshDatabase;

    private function makeUnpaidOrder(Shop $shop, float $total = 10.000): Order
    {
        return Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'total_amount' => $total,
        ]);
    }

    private function actAsServer(Shop $shop): User
    {
        return User::factory()->create(['shop_id' => $shop->id, 'role' => 'server']);
    }

    public function test_partial_payment_is_rejected_and_records_nothing(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = $this->actAsServer($shop);
        $order = $this->makeUnpaidOrder($shop, 10.000);

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->call('openPayment', $order->id)
            ->set('paymentRows', [['amount' => 4.000, 'method' => 'cash']])
            ->call('applyPayments')
            ->assertNotSet('paymentError', null)      // error surfaced
            ->assertSet('paymentOrderId', $order->id); // modal stays open

        $this->assertSame('unpaid', $order->fresh()->status);
        $this->assertSame(0, Payment::where('order_id', $order->id)->count());
    }

    public function test_full_payment_in_one_tender_marks_paid(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = $this->actAsServer($shop);
        $order = $this->makeUnpaidOrder($shop, 10.000);

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->call('openPayment', $order->id)
            ->set('paymentRows', [['amount' => 10.000, 'method' => 'cash']])
            ->call('applyPayments')
            ->assertSet('paymentError', null);

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame(1, Payment::where('order_id', $order->id)->count());
    }

    public function test_quick_payment_rejects_unknown_method_instead_of_relabeling_as_cash(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = $this->actAsServer($shop);
        $order = $this->makeUnpaidOrder($shop, 10.000);

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->call('markAsPaid', $order->id, 'crypto')
            ->assertSet('paymentError', 'Choose a valid payment method.');

        $this->assertSame('unpaid', $order->fresh()->status);
        $this->assertSame(0, Payment::where('order_id', $order->id)->count());
    }

    public function test_quick_payment_is_rejected_after_shift_is_closed_for_the_business_day(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = $this->actAsServer($shop);
        $order = $this->makeUnpaidOrder($shop, 10.000);
        $this->closeShiftFor($shop, $user);

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->call('markAsPaid', $order->id, 'cash')
            ->assertSet('paymentError', 'Shift is closed for today. Payments are locked until the next business day.');

        $this->assertSame('unpaid', $order->fresh()->status);
        $this->assertSame(0, Payment::where('order_id', $order->id)->count());
    }

    public function test_printer_failure_does_not_block_payment_recording(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = $this->actAsServer($shop);
        $order = $this->makeUnpaidOrder($shop, 10.000);

        $this->app->instance(PrintNodeService::class, new class extends PrintNodeService
        {
            public function printOrder(Order $order, string $type = 'kitchen'): bool
            {
                throw new \RuntimeException('Printer offline');
            }
        });

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->call('openPayment', $order->id)
            ->set('paymentRows', [['amount' => 10.000, 'method' => 'cash']])
            ->call('applyPayments')
            ->assertSet('paymentError', null);

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame(1, Payment::where('order_id', $order->id)->count());
    }

    public function test_quick_payment_cannot_revive_cancelled_order(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = $this->actAsServer($shop);
        $order = $this->makeUnpaidOrder($shop, 10.000);
        $order->update(['status' => 'cancelled']);

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->call('markAsPaid', $order->id, 'cash');

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(0, Payment::where('order_id', $order->id)->count());
    }

    public function test_quick_payment_cannot_revive_expired_order(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = $this->actAsServer($shop);
        $order = $this->makeUnpaidOrder($shop, 10.000);
        $order->update(['expires_at' => now()->subMinute()]);

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->call('markAsPaid', $order->id, 'cash');

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(0, Payment::where('order_id', $order->id)->count());
    }

    public function test_apply_payments_rechecks_order_is_payable(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = $this->actAsServer($shop);
        $order = $this->makeUnpaidOrder($shop, 10.000);

        $component = Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->call('openPayment', $order->id)
            ->assertSet('paymentError', null);

        $order->update(['status' => 'cancelled']);

        $component
            ->set('paymentRows', [['amount' => 10.000, 'method' => 'cash']])
            ->call('applyPayments')
            ->assertNotSet('paymentError', null)
            ->assertSet('paymentOrderId', $order->id);

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(0, Payment::where('order_id', $order->id)->count());
    }

    public function test_split_payment_is_rejected_after_shift_is_closed_for_the_business_day(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = $this->actAsServer($shop);
        $order = $this->makeUnpaidOrder($shop, 10.000);

        $component = Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->call('openPayment', $order->id)
            ->set('paymentRows', [['amount' => 10.000, 'method' => 'cash']]);

        $this->closeShiftFor($shop, $user);

        $component
            ->call('applyPayments')
            ->assertSet('paymentError', 'Shift is closed for today. Payments are locked until the next business day.')
            ->assertSet('paymentOrderId', $order->id);

        $this->assertSame('unpaid', $order->fresh()->status);
        $this->assertSame(0, Payment::where('order_id', $order->id)->count());
    }

    public function test_paid_order_void_is_rejected_after_shift_is_closed_for_the_business_day(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);
        $order = $this->makeUnpaidOrder($shop, 10.000);

        Payment::forceCreate([
            'shop_id' => $shop->id,
            'order_id' => $order->id,
            'amount' => 10.000,
            'method' => 'cash',
            'created_by' => $admin->id,
            'paid_at' => now(),
        ]);
        $order->update([
            'status' => 'paid',
            'payment_method' => 'cash',
            'paid_at' => now(),
        ]);
        $this->closeShiftFor($shop, $admin);

        Livewire::actingAs($admin)
            ->test(PosDashboard::class)
            ->call('cancelOrder', $order->id)
            ->assertDispatched('toast', variant: 'error');

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame('cash', $order->fresh()->payment_method);
        $this->assertEqualsWithDelta(10.000, $order->fresh()->paid_total, 0.0001);
        $this->assertSame(1, Payment::where('order_id', $order->id)->count());
        $this->assertSame(
            'shift_closed',
            AuditLog::where('action', 'order.cancel_rejected')->latest()->firstOrFail()->meta['reason']
        );
    }

    public function test_revenue_order_without_payment_rows_is_rejected_after_shift_is_closed_for_the_business_day(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);
        $order = $this->makeUnpaidOrder($shop, 10.000);

        $order->update([
            'status' => 'preparing',
            'payment_method' => 'cash',
            'paid_at' => now(),
        ]);
        $this->closeShiftFor($shop, $admin);

        Livewire::actingAs($admin)
            ->test(PosDashboard::class)
            ->call('cancelOrder', $order->id)
            ->assertDispatched('toast', variant: 'error');

        $this->assertSame('preparing', $order->fresh()->status);
        $this->assertSame('cash', $order->fresh()->payment_method);
        $this->assertSame(0, Payment::where('order_id', $order->id)->count());
        $this->assertSame(
            'shift_closed',
            AuditLog::where('action', 'order.cancel_rejected')->latest()->firstOrFail()->meta['reason']
        );
    }

    public function test_pos_voids_paid_local_order_by_recording_reversal_payments(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);
        $order = $this->makeUnpaidOrder($shop, 10.000);

        Payment::forceCreate([
            'shop_id' => $shop->id,
            'order_id' => $order->id,
            'amount' => 10.000,
            'method' => 'cash',
            'created_by' => $admin->id,
            'paid_at' => now(),
        ]);
        $order->update([
            'status' => 'paid',
            'payment_method' => 'cash',
            'paid_at' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(PosDashboard::class)
            ->call('cancelOrder', $order->id)
            ->assertDispatched('toast', variant: 'success');

        $voided = $order->fresh();
        $this->assertSame('cancelled', $voided->status);
        $this->assertSame('refunded', $voided->payment_method);
        $this->assertEqualsWithDelta(0.000, $voided->paid_total, 0.0001);
        $this->assertSame(2, Payment::where('order_id', $order->id)->count());
        $this->assertDatabaseHas('payments', [
            'shop_id' => $shop->id,
            'order_id' => $order->id,
            'amount' => -10.000,
            'method' => 'cash',
            'created_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'shop_id' => $shop->id,
            'user_id' => $admin->id,
            'action' => 'order.refund_voided',
            'auditable_type' => Order::class,
            'auditable_id' => $order->id,
        ]);
    }

    public function test_voiding_paid_local_order_reverses_awarded_loyalty_points_once(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);
        $order = $this->makeUnpaidOrder($shop, 10.000);
        $order->update([
            'subtotal_amount' => 10.000,
            'loyalty_phone' => '95123456',
        ]);

        Livewire::actingAs($admin)
            ->test(PosDashboard::class)
            ->call('markAsPaid', $order->id, 'cash')
            ->assertSet('paymentError', null);

        $customer = LoyaltyCustomer::where('shop_id', $shop->id)
            ->where('phone', '95123456')
            ->firstOrFail();

        $this->assertSame(10, (int) $customer->points);
        $this->assertSame(1, (int) $customer->visit_count);

        app(LoyaltyService::class)->award($order->fresh());

        $this->assertSame(10, (int) $customer->fresh()->points);
        $this->assertSame(1, AuditLog::where('action', 'loyalty.awarded')->where('auditable_id', $order->id)->count());

        Livewire::actingAs($admin)
            ->test(PosDashboard::class)
            ->call('cancelOrder', $order->id)
            ->assertDispatched('toast', variant: 'success');

        $customer->refresh();

        $this->assertSame(0, (int) $customer->points);
        $this->assertSame(0, (int) $customer->visit_count);
        $this->assertSame(1, AuditLog::where('action', 'loyalty.awarded')->where('auditable_id', $order->id)->count());
        $this->assertSame(1, AuditLog::where('action', 'loyalty.reversed')->where('auditable_id', $order->id)->count());
        $this->assertSame(
            10,
            AuditLog::where('action', 'loyalty.reversed')->where('auditable_id', $order->id)->firstOrFail()->meta['points']
        );

        app(LoyaltyService::class)->reverseAwardForRefundedOrder($order->fresh());

        $this->assertSame(0, (int) $customer->fresh()->points);
        $this->assertSame(1, AuditLog::where('action', 'loyalty.reversed')->where('auditable_id', $order->id)->count());
    }

    public function test_pos_cannot_void_stripe_paid_order_without_external_refund(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);
        $order = $this->makeUnpaidOrder($shop, 10.000);

        Payment::forceCreate([
            'shop_id' => $shop->id,
            'order_id' => $order->id,
            'amount' => 10.000,
            'method' => 'stripe',
            'created_by' => null,
            'paid_at' => now(),
        ]);
        $order->update([
            'status' => 'paid',
            'payment_method' => 'stripe',
            'paid_at' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(PosDashboard::class)
            ->call('cancelOrder', $order->id)
            ->assertDispatched('toast', variant: 'error');

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertEqualsWithDelta(10.000, $order->fresh()->paid_total, 0.0001);
        $this->assertSame(1, Payment::where('order_id', $order->id)->count());
        $this->assertSame(
            'external_refund_required',
            AuditLog::where('action', 'order.cancel_rejected')->latest()->firstOrFail()->meta['reason']
        );
    }

    public function test_pos_refunds_stripe_paid_order_when_provider_reference_exists(): void
    {
        $shop = Shop::create([
            'name' => 'Bite',
            'slug' => 'bite',
            'currency_code' => 'OMR',
            'currency_decimals' => 3,
        ]);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);
        $order = $this->makeUnpaidOrder($shop, 10.000);
        $order->update([
            'subtotal_amount' => 10.000,
            'loyalty_phone' => '95123456',
        ]);

        $payment = Payment::forceCreate([
            'shop_id' => $shop->id,
            'order_id' => $order->id,
            'amount' => 10.000,
            'method' => 'stripe',
            'provider_reference' => 'pi_refundable_order',
            'created_by' => null,
            'paid_at' => now(),
        ]);
        $order->update([
            'status' => 'paid',
            'payment_method' => 'stripe',
            'paid_at' => now(),
        ]);
        app(LoyaltyService::class)->award($order->fresh());

        $refundGateway = new class extends StripeRefundService
        {
            public array $calls = [];

            public function refundPaymentIntent(Order $order, Payment $payment, int $amountMinor): string
            {
                $this->calls[] = [
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                    'provider_reference' => $payment->provider_reference,
                    'amount_minor' => $amountMinor,
                ];

                return 're_refundable_order';
            }
        };
        $this->app->instance(StripeRefundService::class, $refundGateway);

        Livewire::actingAs($admin)
            ->test(PosDashboard::class)
            ->call('cancelOrder', $order->id)
            ->assertDispatched('toast', variant: 'success');

        $voided = $order->fresh();
        $this->assertSame('cancelled', $voided->status);
        $this->assertSame('refunded', $voided->payment_method);
        $this->assertEqualsWithDelta(0.000, $voided->paid_total, 0.0001);
        $this->assertSame(
            0,
            (int) LoyaltyCustomer::where('shop_id', $shop->id)->where('phone', '95123456')->firstOrFail()->points
        );
        $this->assertSame([
            [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'provider_reference' => 'pi_refundable_order',
                'amount_minor' => 10000,
            ],
        ], $refundGateway->calls);
        $this->assertDatabaseHas('payments', [
            'shop_id' => $shop->id,
            'order_id' => $order->id,
            'amount' => -10.000,
            'method' => 'stripe',
            'provider_reference' => 're_refundable_order',
            'reverses_payment_id' => $payment->id,
            'created_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'shop_id' => $shop->id,
            'user_id' => $admin->id,
            'action' => 'order.refund_voided',
            'auditable_type' => Order::class,
            'auditable_id' => $order->id,
        ]);
    }

    public function test_failed_stripe_refund_leaves_paid_order_and_payment_untouched(): void
    {
        $shop = Shop::create([
            'name' => 'Bite',
            'slug' => 'bite',
            'currency_code' => 'OMR',
            'currency_decimals' => 3,
        ]);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);
        $order = $this->makeUnpaidOrder($shop, 10.000);

        Payment::forceCreate([
            'shop_id' => $shop->id,
            'order_id' => $order->id,
            'amount' => 10.000,
            'method' => 'stripe',
            'provider_reference' => 'pi_refund_will_fail',
            'created_by' => null,
            'paid_at' => now(),
        ]);
        $order->update([
            'status' => 'paid',
            'payment_method' => 'stripe',
            'paid_at' => now(),
        ]);

        $this->app->instance(StripeRefundService::class, new class extends StripeRefundService
        {
            public function refundPaymentIntent(Order $order, Payment $payment, int $amountMinor): string
            {
                throw new RuntimeException('Stripe refund declined');
            }
        });

        Livewire::actingAs($admin)
            ->test(PosDashboard::class)
            ->call('cancelOrder', $order->id)
            ->assertDispatched('toast', variant: 'error');

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame('stripe', $order->fresh()->payment_method);
        $this->assertEqualsWithDelta(10.000, $order->fresh()->paid_total, 0.0001);
        $this->assertSame(1, Payment::where('order_id', $order->id)->count());

        $log = AuditLog::where('action', 'order.cancel_rejected')->latest()->firstOrFail();
        $this->assertSame('stripe_refund_failed', $log->meta['reason']);
        $this->assertSame('Stripe refund declined', $log->meta['error']);
    }

    public function test_pos_can_cancel_unpaid_order_that_has_no_money_recorded(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $admin = User::factory()->create(['shop_id' => $shop->id, 'role' => 'admin']);
        $order = $this->makeUnpaidOrder($shop, 10.000);

        Livewire::actingAs($admin)
            ->test(PosDashboard::class)
            ->call('cancelOrder', $order->id)
            ->assertDispatched('toast', variant: 'success');

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(0, Payment::where('order_id', $order->id)->count());
    }

    public function test_numeric_string_payment_amount_marks_paid(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = $this->actAsServer($shop);
        $order = $this->makeUnpaidOrder($shop, 10.000);

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->call('openPayment', $order->id)
            ->set('paymentRows', [['amount' => '10.000', 'method' => 'cash']])
            ->call('applyPayments')
            ->assertSet('paymentError', null);

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame(1, Payment::where('order_id', $order->id)->count());
    }

    public function test_split_tenders_that_sum_to_balance_are_accepted(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = $this->actAsServer($shop);
        $order = $this->makeUnpaidOrder($shop, 10.000);

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->call('openPayment', $order->id)
            ->set('paymentRows', [
                ['amount' => 6.000, 'method' => 'cash'],
                ['amount' => 4.000, 'method' => 'card'],
            ])
            ->call('applyPayments')
            ->assertSet('paymentError', null);

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame(2, Payment::where('order_id', $order->id)->count());
    }

    private function closeShiftFor(Shop $shop, User $user): void
    {
        ShiftClosure::forceCreate([
            'shop_id' => $shop->id,
            'business_date' => ShopClock::localDate($shop),
            'closed_by' => $user->id,
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

    public function test_settling_remaining_balance_after_existing_payment_marks_order_split(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = $this->actAsServer($shop);
        $order = $this->makeUnpaidOrder($shop, 10.000);

        Payment::forceCreate([
            'shop_id' => $shop->id,
            'order_id' => $order->id,
            'amount' => 4.000,
            'method' => 'stripe',
            'created_by' => null,
            'paid_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->call('openPayment', $order->id)
            ->set('paymentRows', [['amount' => 6.000, 'method' => 'cash']])
            ->call('applyPayments')
            ->assertSet('paymentError', null);

        $paid = $order->fresh();
        $this->assertSame('paid', $paid->status);
        $this->assertSame('split', $paid->payment_method);
        $this->assertSame(2, Payment::where('order_id', $order->id)->count());
        $this->assertEqualsWithDelta(10.000, $paid->paid_total, 0.0001);
    }

    public function test_mismatched_shop_payment_does_not_satisfy_order_balance(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $otherShop = Shop::create(['name' => 'Other', 'slug' => 'other']);
        $user = $this->actAsServer($shop);
        $order = $this->makeUnpaidOrder($shop, 10.000);

        Payment::forceCreate([
            'shop_id' => $otherShop->id,
            'order_id' => $order->id,
            'amount' => 10.000,
            'method' => 'card',
            'paid_at' => now(),
        ]);

        $this->assertEqualsWithDelta(0.000, $order->fresh()->paid_total, 0.0001);
        $this->assertEqualsWithDelta(10.000, $order->fresh()->balance_due, 0.0001);

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->call('markAsPaid', $order->id, 'cash');

        $paid = $order->fresh();
        $this->assertSame('paid', $paid->status);
        $this->assertEqualsWithDelta(10.000, $paid->paid_total, 0.0001);
        $this->assertEqualsWithDelta(0.000, $paid->balance_due, 0.0001);
        $this->assertDatabaseHas('payments', [
            'shop_id' => $shop->id,
            'order_id' => $order->id,
            'amount' => 10.000,
            'method' => 'cash',
        ]);
    }

    public function test_split_by_amount_prefills_remainder_so_full_balance_can_be_settled(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = $this->actAsServer($shop);
        $order = $this->makeUnpaidOrder($shop, 10.000);

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->call('openPayment', $order->id)
            ->set('splitAmount', '4.250')
            ->call('splitByAmount')
            ->assertSet('paymentRows.0.amount', 4.250)
            ->assertSet('paymentRows.1.amount', 5.750)
            ->set('paymentRows.0.method', 'cash')
            ->set('paymentRows.1.method', 'card')
            ->call('applyPayments')
            ->assertSet('paymentError', null);

        $paid = $order->fresh();
        $this->assertSame('paid', $paid->status);
        $this->assertSame('split', $paid->payment_method);
        $this->assertEqualsWithDelta(10.000, Payment::where('order_id', $order->id)->sum('amount'), 0.0001);
        $this->assertSame(2, Payment::where('order_id', $order->id)->count());
    }

    public function test_split_tenders_with_three_decimal_residual_mark_order_paid(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = $this->actAsServer($shop);
        $order = $this->makeUnpaidOrder($shop, 5.722);

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->call('openPayment', $order->id)
            ->set('paymentRows', [
                ['amount' => 3.000, 'method' => 'card'],
                ['amount' => 2.722, 'method' => 'cash'],
            ])
            ->call('applyPayments')
            ->assertSet('paymentError', null);

        $paid = $order->fresh();
        $this->assertSame('paid', $paid->status);
        $this->assertSame('split', $paid->payment_method);
        $this->assertNotNull($paid->paid_at);
        $this->assertSame(2, Payment::where('order_id', $order->id)->count());
    }

    public function test_overpayment_is_rejected(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = $this->actAsServer($shop);
        $order = $this->makeUnpaidOrder($shop, 10.000);

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->call('openPayment', $order->id)
            ->set('paymentRows', [['amount' => 12.000, 'method' => 'cash']])
            ->call('applyPayments')
            ->assertNotSet('paymentError', null);

        $this->assertSame('unpaid', $order->fresh()->status);
        $this->assertSame(0, Payment::where('order_id', $order->id)->count());
    }

    public function test_split_payment_rejects_unknown_method_instead_of_relabeling_as_cash(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = $this->actAsServer($shop);
        $order = $this->makeUnpaidOrder($shop, 10.000);

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->call('openPayment', $order->id)
            ->set('paymentRows', [['amount' => 10.000, 'method' => 'crypto']])
            ->call('applyPayments')
            ->assertSet('paymentError', 'Choose a valid payment method.')
            ->assertSet('paymentOrderId', $order->id);

        $this->assertSame('unpaid', $order->fresh()->status);
        $this->assertSame(0, Payment::where('order_id', $order->id)->count());
    }

    public function test_split_payment_rejects_missing_method_instead_of_relabeling_as_cash(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = $this->actAsServer($shop);
        $order = $this->makeUnpaidOrder($shop, 10.000);

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->call('openPayment', $order->id)
            ->set('paymentRows', [['amount' => 10.000]])
            ->call('applyPayments')
            ->assertSet('paymentError', 'Choose a valid payment method.')
            ->assertSet('paymentOrderId', $order->id);

        $this->assertSame('unpaid', $order->fresh()->status);
        $this->assertSame(0, Payment::where('order_id', $order->id)->count());
    }

    public function test_malformed_payment_amount_is_rejected_instead_of_float_cast(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = $this->actAsServer($shop);
        $order = $this->makeUnpaidOrder($shop, 10.000);

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->call('openPayment', $order->id)
            ->set('paymentRows', [['amount' => '10abc', 'method' => 'cash']])
            ->call('applyPayments')
            ->assertNotSet('paymentError', null);

        $this->assertSame('unpaid', $order->fresh()->status);
        $this->assertSame(0, Payment::where('order_id', $order->id)->count());
    }

    public function test_malformed_split_by_amount_is_rejected_instead_of_float_cast(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = $this->actAsServer($shop);
        $order = $this->makeUnpaidOrder($shop, 10.000);

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->call('openPayment', $order->id)
            ->set('splitAmount', '4abc')
            ->call('splitByAmount')
            ->assertNotSet('paymentError', null)
            ->assertSet('paymentRows.0.amount', 10.000);

        $this->assertSame('unpaid', $order->fresh()->status);
        $this->assertSame(0, Payment::where('order_id', $order->id)->count());
    }

    public function test_three_decimal_overpayment_is_rejected(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = $this->actAsServer($shop);
        $order = $this->makeUnpaidOrder($shop, 5.722);

        Livewire::actingAs($user)
            ->test(PosDashboard::class)
            ->call('openPayment', $order->id)
            ->set('paymentRows', [
                ['amount' => 3.000, 'method' => 'card'],
                ['amount' => 2.723, 'method' => 'cash'],
            ])
            ->call('applyPayments')
            ->assertNotSet('paymentError', null);

        $this->assertSame('unpaid', $order->fresh()->status);
        $this->assertSame(0, Payment::where('order_id', $order->id)->count());
    }
}
