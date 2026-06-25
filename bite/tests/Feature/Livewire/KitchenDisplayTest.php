<?php

namespace Tests\Feature\Livewire;

use App\Livewire\KitchenDisplay;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ShiftClosure;
use App\Models\Shop;
use App\Models\User;
use App\Services\StripeRefundService;
use App\Support\ShopClock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KitchenDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_kitchen_sees_paid_orders_only(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'kitchen']);

        $paidOrder = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'paid',
            'total_amount' => 15.00,
        ]);

        $unpaidOrder = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'total_amount' => 10.00,
        ]);

        Livewire::actingAs($user)
            ->test(KitchenDisplay::class)
            ->assertSee('Ticket_'.$paidOrder->id)
            ->assertDontSee('Ticket_'.$unpaidOrder->id);
    }

    public function test_kitchen_ticket_shows_customer_name(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'kitchen']);

        Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'paid',
            'customer_name' => 'Aisha Pickup',
            'total_amount' => 15.00,
        ]);

        Livewire::actingAs($user)
            ->test(KitchenDisplay::class)
            ->assertSee('Aisha Pickup');
    }

    public function test_kitchen_can_update_order_status(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $user = User::factory()->create(['shop_id' => $shop->id, 'role' => 'kitchen']);

        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'paid',
            'total_amount' => 15.00,
        ]);

        Livewire::actingAs($user)
            ->test(KitchenDisplay::class)
            ->call('updateStatus', $order->id, 'preparing');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'preparing',
        ]);
    }

    public function test_manager_voids_paid_local_order_from_kds_by_recording_reversal_payments(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);

        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'paid',
            'total_amount' => 15.000,
            'payment_method' => 'card',
            'paid_at' => now(),
        ]);

        Payment::forceCreate([
            'shop_id' => $shop->id,
            'order_id' => $order->id,
            'amount' => 15.000,
            'method' => 'card',
            'created_by' => $manager->id,
            'paid_at' => now(),
        ]);

        Livewire::actingAs($manager)
            ->test(KitchenDisplay::class)
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
            'amount' => -15.000,
            'method' => 'card',
            'created_by' => $manager->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'shop_id' => $shop->id,
            'user_id' => $manager->id,
            'action' => 'order.refund_voided',
            'auditable_type' => Order::class,
            'auditable_id' => $order->id,
        ]);
    }

    public function test_manager_cannot_void_stripe_order_from_kds_without_external_refund(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);

        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'paid',
            'total_amount' => 15.000,
            'payment_method' => 'stripe',
            'paid_at' => now(),
        ]);

        Payment::forceCreate([
            'shop_id' => $shop->id,
            'order_id' => $order->id,
            'amount' => 15.000,
            'method' => 'stripe',
            'created_by' => null,
            'paid_at' => now(),
        ]);

        Livewire::actingAs($manager)
            ->test(KitchenDisplay::class)
            ->call('cancelOrder', $order->id)
            ->assertDispatched('toast', variant: 'error');

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame(1, Payment::where('order_id', $order->id)->count());
        $this->assertSame(
            'external_refund_required',
            AuditLog::where('action', 'order.cancel_rejected')->latest()->firstOrFail()->meta['reason']
        );
    }

    public function test_manager_cannot_void_paid_order_from_kds_after_shift_is_closed(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);

        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'paid',
            'total_amount' => 15.000,
            'payment_method' => 'card',
            'paid_at' => now(),
        ]);

        Payment::forceCreate([
            'shop_id' => $shop->id,
            'order_id' => $order->id,
            'amount' => 15.000,
            'method' => 'card',
            'created_by' => $manager->id,
            'paid_at' => now(),
        ]);

        ShiftClosure::forceCreate([
            'shop_id' => $shop->id,
            'business_date' => ShopClock::localDate($shop),
            'closed_by' => $manager->id,
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

        Livewire::actingAs($manager)
            ->test(KitchenDisplay::class)
            ->call('cancelOrder', $order->id)
            ->assertDispatched('toast', variant: 'error');

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame('card', $order->fresh()->payment_method);
        $this->assertEqualsWithDelta(15.000, $order->fresh()->paid_total, 0.0001);
        $this->assertSame(1, Payment::where('order_id', $order->id)->count());
        $this->assertSame(
            'shift_closed',
            AuditLog::where('action', 'order.cancel_rejected')->latest()->firstOrFail()->meta['reason']
        );
    }

    public function test_manager_cannot_cancel_revenue_order_without_payment_rows_from_kds_after_shift_is_closed(): void
    {
        $shop = Shop::create(['name' => 'Bite', 'slug' => 'bite']);
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);

        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'preparing',
            'total_amount' => 15.000,
            'payment_method' => 'card',
            'paid_at' => now(),
        ]);

        ShiftClosure::forceCreate([
            'shop_id' => $shop->id,
            'business_date' => ShopClock::localDate($shop),
            'closed_by' => $manager->id,
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

        Livewire::actingAs($manager)
            ->test(KitchenDisplay::class)
            ->call('cancelOrder', $order->id)
            ->assertDispatched('toast', variant: 'error');

        $this->assertSame('preparing', $order->fresh()->status);
        $this->assertSame(0, Payment::where('order_id', $order->id)->count());
        $this->assertSame(
            'shift_closed',
            AuditLog::where('action', 'order.cancel_rejected')->latest()->firstOrFail()->meta['reason']
        );
    }

    public function test_manager_refunds_stripe_paid_order_from_kds_when_provider_reference_exists(): void
    {
        $shop = Shop::create([
            'name' => 'Bite',
            'slug' => 'bite',
            'currency_code' => 'OMR',
            'currency_decimals' => 3,
        ]);
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);

        $order = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'paid',
            'total_amount' => 15.000,
            'payment_method' => 'stripe',
            'paid_at' => now(),
        ]);

        $payment = Payment::forceCreate([
            'shop_id' => $shop->id,
            'order_id' => $order->id,
            'amount' => 15.000,
            'method' => 'stripe',
            'provider_reference' => 'pi_kds_refundable',
            'created_by' => null,
            'paid_at' => now(),
        ]);

        $refundGateway = new class extends StripeRefundService
        {
            public array $calls = [];

            public function refundPaymentIntent(Order $order, Payment $payment, int $amountMinor): string
            {
                $this->calls[] = [
                    'provider_reference' => $payment->provider_reference,
                    'amount_minor' => $amountMinor,
                ];

                return 're_kds_refund';
            }
        };
        $this->app->instance(StripeRefundService::class, $refundGateway);

        Livewire::actingAs($manager)
            ->test(KitchenDisplay::class)
            ->call('cancelOrder', $order->id)
            ->assertDispatched('toast', variant: 'success');

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertEqualsWithDelta(0.000, $order->fresh()->paid_total, 0.0001);
        $this->assertSame([
            [
                'provider_reference' => 'pi_kds_refundable',
                'amount_minor' => 15000,
            ],
        ], $refundGateway->calls);
        $this->assertDatabaseHas('payments', [
            'shop_id' => $shop->id,
            'order_id' => $order->id,
            'amount' => -15.000,
            'method' => 'stripe',
            'provider_reference' => 're_kds_refund',
            'reverses_payment_id' => $payment->id,
            'created_by' => $manager->id,
        ]);
    }
}
