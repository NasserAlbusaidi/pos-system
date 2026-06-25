<?php

namespace Tests\Feature\Livewire;

use App\Livewire\CashReconciliation;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ShiftClosure;
use App\Models\Shop;
use App\Models\User;
use App\Support\ShopClock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class CashReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_shift_summary_separates_cash_card_and_voucher_tenders_for_current_shop(): void
    {
        $shop = $this->makeShop('Bite', 'bite');
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);
        $order = $this->makePaidOrder($shop, 18.000);
        $this->recordPayment($shop, $order, 5.000, 'cash');
        $this->recordPayment($shop, $order, 10.000, 'card');
        $this->recordPayment($shop, $order, 3.000, 'voucher');

        $otherShop = $this->makeShop('Other', 'other');
        $otherOrder = $this->makePaidOrder($otherShop, 99.000);
        $this->recordPayment($otherShop, $otherOrder, 99.000, 'voucher');

        Livewire::actingAs($manager)
            ->test(CashReconciliation::class)
            ->assertSet('expectedCash', 5.0)
            ->assertSet('shiftSummary.total_orders', 1)
            ->assertSet('shiftSummary.total_revenue', 18.0)
            ->assertSet('shiftSummary.cash_total', 5.0)
            ->assertSet('shiftSummary.card_total', 10.0)
            ->assertSet('shiftSummary.voucher_total', 3.0)
            ->assertSee(__('admin.voucher_payments'));
    }

    public function test_reconciliation_audit_log_preserves_tender_breakdown(): void
    {
        $shop = $this->makeShop('Bite', 'bite');
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);
        $order = $this->makePaidOrder($shop, 13.000);
        $this->recordPayment($shop, $order, 5.000, 'cash');
        $this->recordPayment($shop, $order, 8.000, 'voucher');

        Livewire::actingAs($manager)
            ->test(CashReconciliation::class)
            ->set('actualCash', 4.500)
            ->set('notes', 'Drawer short by half an OMR.')
            ->call('reconcile')
            ->assertSet('showResult', true)
            ->assertSet('difference', -0.5);

        $audit = AuditLog::where('shop_id', $shop->id)
            ->where('action', 'cash_reconciliation')
            ->firstOrFail();

        $this->assertEqualsWithDelta(5.000, $audit->meta['expected_cash'], 0.0001);
        $this->assertEqualsWithDelta(4.500, $audit->meta['actual_cash'], 0.0001);
        $this->assertEqualsWithDelta(8.000, $audit->meta['shift_summary']['voucher_total'], 0.0001);
        $this->assertSame('Drawer short by half an OMR.', $audit->meta['notes']);
    }

    public function test_reconciliation_refreshes_expected_cash_before_recording(): void
    {
        $shop = $this->makeShop('Bite', 'bite');
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);
        $firstOrder = $this->makePaidOrder($shop, 5.000);
        $this->recordPayment($shop, $firstOrder, 5.000, 'cash');

        $component = Livewire::actingAs($manager)
            ->test(CashReconciliation::class)
            ->assertSet('expectedCash', 5.0);

        $lateOrder = $this->makePaidOrder($shop, 2.000);
        $this->recordPayment($shop, $lateOrder, 2.000, 'cash');

        $component
            ->set('actualCash', 7.000)
            ->call('reconcile')
            ->assertSet('expectedCash', 7.0)
            ->assertSet('difference', 0.0);

        $audit = AuditLog::where('shop_id', $shop->id)
            ->where('action', 'cash_reconciliation')
            ->firstOrFail();

        $this->assertEqualsWithDelta(7.000, $audit->meta['expected_cash'], 0.0001);
        $this->assertEqualsWithDelta(7.000, $audit->meta['actual_cash'], 0.0001);
    }

    public function test_shift_summary_uses_shop_timezone_for_current_business_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-24 21:30:00', 'UTC'));

        $shop = $this->makeShop('Bite', 'bite');
        $shop->update(['branding' => ['timezone' => 'Asia/Muscat']]);
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);

        $previousLocalDay = $this->makePaidOrder(
            $shop,
            10.000,
            Carbon::parse('2026-06-24 08:00:00', 'UTC')
        );
        $this->recordPayment($shop, $previousLocalDay, 10.000, 'cash', $previousLocalDay->paid_at);

        $currentLocalDay = $this->makePaidOrder(
            $shop,
            5.000,
            Carbon::parse('2026-06-24 21:00:00', 'UTC')
        );
        $this->recordPayment($shop, $currentLocalDay, 5.000, 'cash', $currentLocalDay->paid_at);

        Livewire::actingAs($manager)
            ->test(CashReconciliation::class)
            ->assertSet('expectedCash', 5.0)
            ->assertSet('shiftSummary.total_orders', 1)
            ->assertSet('shiftSummary.total_revenue', 5.0);
    }

    public function test_shift_summary_excludes_payments_for_non_revenue_orders(): void
    {
        $shop = $this->makeShop('Bite', 'bite');
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);

        $paidOrder = $this->makePaidOrder($shop, 12.000);
        $this->recordPayment($shop, $paidOrder, 12.000, 'cash');

        $cancelledOrder = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'cancelled',
            'subtotal_amount' => 99.000,
            'tax_amount' => 0,
            'total_amount' => 99.000,
            'payment_method' => 'cash',
            'paid_at' => now(),
        ]);
        $this->recordPayment($shop, $cancelledOrder, 99.000, 'cash');

        $unpaidOrder = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'subtotal_amount' => 77.000,
            'tax_amount' => 0,
            'total_amount' => 77.000,
            'payment_method' => null,
            'paid_at' => null,
        ]);
        $this->recordPayment($shop, $unpaidOrder, 77.000, 'voucher');

        Livewire::actingAs($manager)
            ->test(CashReconciliation::class)
            ->assertSet('expectedCash', 12.0)
            ->assertSet('shiftSummary.total_orders', 1)
            ->assertSet('shiftSummary.total_revenue', 12.0)
            ->assertSet('shiftSummary.cash_total', 12.0)
            ->assertSet('shiftSummary.voucher_total', 0.0);
    }

    public function test_shift_summary_counts_refund_reversal_on_the_day_money_leaves_the_drawer(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-25 12:00:00', 'UTC'));

        $shop = $this->makeShop('Bite', 'bite');
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);
        $yesterday = Carbon::parse('2026-06-24 10:00:00', 'UTC');

        $order = $this->makePaidOrder($shop, 10.000, $yesterday);
        $original = $this->recordPayment($shop, $order, 10.000, 'cash', $yesterday);
        $order->update(['status' => 'cancelled', 'payment_method' => 'refunded']);
        $this->recordPayment($shop, $order, -10.000, 'cash', now(), $original->id);

        Livewire::actingAs($manager)
            ->test(CashReconciliation::class)
            ->assertSet('expectedCash', -10.0)
            ->assertSet('shiftSummary.total_orders', 0)
            ->assertSet('shiftSummary.total_revenue', 0.0)
            ->assertSet('shiftSummary.cash_total', -10.0);
    }

    public function test_same_day_voided_cash_payment_nets_to_zero_for_drawer_count(): void
    {
        $shop = $this->makeShop('Bite', 'bite');
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);
        $order = $this->makePaidOrder($shop, 10.000);
        $original = $this->recordPayment($shop, $order, 10.000, 'cash');
        $order->update(['status' => 'cancelled', 'payment_method' => 'refunded']);
        $this->recordPayment($shop, $order, -10.000, 'cash', now(), $original->id);

        Livewire::actingAs($manager)
            ->test(CashReconciliation::class)
            ->assertSet('expectedCash', 0.0)
            ->assertSet('shiftSummary.total_orders', 0)
            ->assertSet('shiftSummary.total_revenue', 0.0)
            ->assertSet('shiftSummary.cash_total', 0.0);
    }

    public function test_shift_cannot_be_closed_before_reconciliation_is_recorded(): void
    {
        $shop = $this->makeShop('Bite', 'bite');
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);

        Livewire::actingAs($manager)
            ->test(CashReconciliation::class)
            ->call('closeShift')
            ->assertNoRedirect()
            ->assertDispatched('toast', variant: 'error');

        $this->assertDatabaseMissing('audit_logs', [
            'shop_id' => $shop->id,
            'action' => 'cash_reconciliation',
        ]);
        $this->assertDatabaseMissing('shift_closures', [
            'shop_id' => $shop->id,
            'business_date' => ShopClock::localDate($shop),
        ]);
    }

    public function test_shift_cannot_close_with_stale_reconciliation_after_late_payment(): void
    {
        $shop = $this->makeShop('Bite', 'bite');
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);
        $firstOrder = $this->makePaidOrder($shop, 5.000);
        $this->recordPayment($shop, $firstOrder, 5.000, 'cash');

        $component = Livewire::actingAs($manager)
            ->test(CashReconciliation::class)
            ->set('actualCash', 5.000)
            ->call('reconcile')
            ->assertSet('showResult', true)
            ->assertSet('difference', 0.0);

        $lateOrder = $this->makePaidOrder($shop, 2.000);
        $this->recordPayment($shop, $lateOrder, 2.000, 'cash');

        $component
            ->call('closeShift')
            ->assertNoRedirect()
            ->assertSet('showResult', false)
            ->assertSet('expectedCash', 7.0)
            ->assertDispatched('toast', variant: 'error');

        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_shift_close_is_audited_after_reconciled_totals_are_current(): void
    {
        $shop = $this->makeShop('Bite', 'bite');
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);
        $order = $this->makePaidOrder($shop, 8.000);
        $this->recordPayment($shop, $order, 5.000, 'cash');
        $this->recordPayment($shop, $order, 3.000, 'card');

        Livewire::actingAs($manager)
            ->test(CashReconciliation::class)
            ->set('actualCash', 4.750)
            ->set('notes', 'Drawer short after paid cash order.')
            ->call('reconcile')
            ->call('closeShift')
            ->assertRedirect(route('dashboard'));

        $closeAudit = AuditLog::where('shop_id', $shop->id)
            ->where('user_id', $manager->id)
            ->where('action', 'shift.closed')
            ->firstOrFail();

        $this->assertSame(Shop::class, $closeAudit->auditable_type);
        $this->assertSame($shop->id, $closeAudit->auditable_id);
        $this->assertEqualsWithDelta(5.000, $closeAudit->meta['expected_cash'], 0.0001);
        $this->assertEqualsWithDelta(4.750, $closeAudit->meta['actual_cash'], 0.0001);
        $this->assertEqualsWithDelta(-0.250, $closeAudit->meta['difference'], 0.0001);
        $this->assertEqualsWithDelta(3.000, $closeAudit->meta['shift_summary']['card_total'], 0.0001);
        $this->assertSame('Drawer short after paid cash order.', $closeAudit->meta['notes']);

        $closure = ShiftClosure::where('shop_id', $shop->id)
            ->where('business_date', ShopClock::localDate($shop))
            ->firstOrFail();

        $this->assertSame($manager->id, $closure->closed_by);
        $this->assertEqualsWithDelta(5.000, (float) $closure->expected_cash, 0.0001);
        $this->assertEqualsWithDelta(4.750, (float) $closure->actual_cash, 0.0001);
        $this->assertEqualsWithDelta(-0.250, (float) $closure->difference, 0.0001);
        $this->assertEqualsWithDelta(3.000, $closure->shift_summary['card_total'], 0.0001);
        $this->assertSame('Drawer short after paid cash order.', $closure->notes);
        $this->assertNotNull($closure->closed_at);
    }

    public function test_shift_close_can_only_be_recorded_once_for_a_business_day(): void
    {
        $shop = $this->makeShop('Bite', 'bite');
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);
        $order = $this->makePaidOrder($shop, 5.000);
        $this->recordPayment($shop, $order, 5.000, 'cash');

        ShiftClosure::forceCreate([
            'shop_id' => $shop->id,
            'business_date' => ShopClock::localDate($shop),
            'closed_by' => $manager->id,
            'expected_cash' => 5.000,
            'actual_cash' => 5.000,
            'difference' => 0.000,
            'shift_summary' => [
                'total_orders' => 1,
                'total_revenue' => 5.000,
                'cash_total' => 5.000,
                'card_total' => 0.000,
                'voucher_total' => 0.000,
            ],
            'notes' => 'Already closed.',
            'closed_at' => now(),
        ]);

        Livewire::actingAs($manager)
            ->test(CashReconciliation::class)
            ->set('actualCash', 5.000)
            ->call('reconcile')
            ->call('closeShift')
            ->assertNoRedirect()
            ->assertDispatched('toast', variant: 'error');

        $this->assertSame(1, ShiftClosure::where('shop_id', $shop->id)->count());
    }

    private function makeShop(string $name, string $slug): Shop
    {
        $shop = Shop::create(['name' => $name, 'slug' => $slug]);
        $shop->trial_ends_at = now()->addDays(14);
        $shop->save();

        return $shop;
    }

    private function makePaidOrder(Shop $shop, float $total, ?Carbon $paidAt = null): Order
    {
        $paidAt ??= now();

        return Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'completed',
            'subtotal_amount' => $total,
            'tax_amount' => 0,
            'total_amount' => $total,
            'payment_method' => 'split',
            'paid_at' => $paidAt,
            'fulfilled_at' => $paidAt,
        ]);
    }

    private function recordPayment(
        Shop $shop,
        Order $order,
        float $amount,
        string $method,
        ?Carbon $paidAt = null,
        ?int $reversesPaymentId = null
    ): Payment {
        return Payment::forceCreate([
            'shop_id' => $shop->id,
            'order_id' => $order->id,
            'amount' => $amount,
            'method' => $method,
            'paid_at' => $paidAt ?? now(),
            'reverses_payment_id' => $reversesPaymentId,
        ]);
    }
}
