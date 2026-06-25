<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RouteTenantIsolationSweepTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_order_invoice_route_does_not_expose_another_shops_order(): void
    {
        [$shop, $user] = $this->makeSubscribedShopWithUser('server');
        $otherOrder = $this->makeOrderFor($this->makeSubscribedShop('Other Shop', 'other-shop'));

        $this->actingAs($user)
            ->get(route('admin.orders.invoice', $otherOrder))
            ->assertNotFound();
    }

    public function test_receipt_route_does_not_expose_another_shops_order(): void
    {
        [$shop, $user] = $this->makeSubscribedShopWithUser('server');
        $otherOrder = $this->makeOrderFor($this->makeSubscribedShop('Other Shop', 'other-shop'));

        $this->actingAs($user)
            ->get(route('receipt.print', $otherOrder))
            ->assertNotFound();
    }

    public function test_reports_export_only_contains_authenticated_users_shop_orders(): void
    {
        [$shop, $manager] = $this->makeSubscribedShopWithUser('manager');
        $ownOrder = $this->makeOrderFor($shop, 12.345);
        $otherOrder = $this->makeOrderFor($this->makeSubscribedShop('Other Shop', 'other-shop'), 99.999);

        $response = $this->actingAs($manager)
            ->get(route('admin.reports.export', [
                'from' => today()->toDateString(),
                'to' => today()->toDateString(),
            ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();
        $rows = array_map('str_getcsv', array_filter(explode("\n", trim($csv))));
        $orderIds = collect(array_slice($rows, 1))->pluck(0)->all();

        $this->assertSame([
            'order_id',
            'paid_at',
            'subtotal',
            'tax',
            'total',
            'payment_method',
            'payment_breakdown',
            'status',
            'payment_activity_at',
        ], $rows[0]);
        $this->assertContains((string) $ownOrder->id, $orderIds);
        $this->assertNotContains((string) $otherOrder->id, $orderIds);
        $this->assertStringContainsString('12.345', $csv);
        $this->assertStringNotContainsString('99.999', $csv);
    }

    public function test_reports_export_includes_split_tender_breakdown(): void
    {
        [$shop, $manager] = $this->makeSubscribedShopWithUser('manager');
        $order = $this->makeOrderFor($shop, 10.000, 'split');
        $this->recordPayment($shop, $order, 6.000, 'cash');
        $this->recordPayment($shop, $order, 4.000, 'card');

        $response = $this->actingAs($manager)
            ->get(route('admin.reports.export', [
                'from' => today()->toDateString(),
                'to' => today()->toDateString(),
            ]));

        $rows = array_map('str_getcsv', array_filter(explode("\n", trim($response->streamedContent()))));
        $row = collect(array_slice($rows, 1))->firstWhere(0, (string) $order->id);

        $this->assertSame('split', $row[5]);
        $this->assertSame('cash:6.000; card:4.000', $row[6]);
    }

    public function test_reports_export_payment_breakdown_excludes_mismatched_shop_payments(): void
    {
        [$shop, $manager] = $this->makeSubscribedShopWithUser('manager');
        $otherShop = $this->makeSubscribedShop('Other Shop', 'other-shop');
        $order = $this->makeOrderFor($shop, 10.000, 'split');
        $this->recordPayment($shop, $order, 10.000, 'cash');
        $this->recordPayment($otherShop, $order, 55.000, 'card');

        $response = $this->actingAs($manager)
            ->get(route('admin.reports.export', [
                'from' => today()->toDateString(),
                'to' => today()->toDateString(),
            ]));

        $rows = array_map('str_getcsv', array_filter(explode("\n", trim($response->streamedContent()))));
        $row = collect(array_slice($rows, 1))->firstWhere(0, (string) $order->id);

        $this->assertSame('cash:10.000', $row[6]);
    }

    public function test_reports_export_includes_refund_reversal_activity_inside_selected_range(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-25 12:00:00', 'UTC'));

        [$shop, $manager] = $this->makeSubscribedShopWithUser('manager');
        $originalPaidAt = Carbon::parse('2026-06-24 10:00:00', 'UTC');
        $order = $this->makeOrderFor($shop, 10.000, 'refunded', 'cancelled', $originalPaidAt);
        $original = $this->recordPayment($shop, $order, 10.000, 'cash', $originalPaidAt);
        $this->recordPayment($shop, $order, -10.000, 'cash', now(), $original->id);

        $response = $this->actingAs($manager)
            ->get(route('admin.reports.export', [
                'from' => today()->toDateString(),
                'to' => today()->toDateString(),
            ]));

        $response->assertOk();
        $rows = array_map('str_getcsv', array_filter(explode("\n", trim($response->streamedContent()))));
        $row = collect(array_slice($rows, 1))->firstWhere(0, (string) $order->id);

        $this->assertNotNull($row);
        $this->assertSame('refunded', $row[5]);
        $this->assertSame('cash:-10.000', $row[6]);
        $this->assertSame('cancelled', $row[7]);
        $this->assertSame('2026-06-25 12:00', $row[8]);
    }

    public function test_reports_export_includes_paid_in_progress_orders(): void
    {
        [$shop, $manager] = $this->makeSubscribedShopWithUser('manager');
        $orders = collect(['paid', 'preparing', 'ready', 'completed'])
            ->map(fn (string $status, int $index) => $this->makeOrderFor(
                shop: $shop,
                total: 10.000 + $index,
                paymentMethod: 'cash',
                status: $status
            ));

        $excludedUnpaid = $this->makeOrderFor($shop, 77.000, 'cash', 'unpaid');
        $excludedUnpaid->forceFill(['paid_at' => null])->save();
        $excludedCancelled = $this->makeOrderFor($shop, 88.000, 'cash', 'cancelled');
        $otherShopPaid = $this->makeOrderFor($this->makeSubscribedShop('Other Shop', 'other-shop'), 99.000, 'cash', 'paid');

        $response = $this->actingAs($manager)
            ->get(route('admin.reports.export', [
                'from' => today()->toDateString(),
                'to' => today()->toDateString(),
            ]));

        $response->assertOk();
        $rows = array_map('str_getcsv', array_filter(explode("\n", trim($response->streamedContent()))));
        $orderIds = collect(array_slice($rows, 1))->pluck(0)->all();

        foreach ($orders as $order) {
            $this->assertContains((string) $order->id, $orderIds);
        }

        $this->assertNotContains((string) $excludedUnpaid->id, $orderIds);
        $this->assertNotContains((string) $excludedCancelled->id, $orderIds);
        $this->assertNotContains((string) $otherShopPaid->id, $orderIds);
    }

    public function test_reports_export_default_range_matches_last_30_local_days(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-25 12:00:00', 'UTC'));
        [$shop, $manager] = $this->makeSubscribedShopWithUser('manager');
        $insideWindow = $this->makeOrderFor(
            shop: $shop,
            total: 12.000,
            paidAt: Carbon::parse('2026-05-27 12:00:00', 'UTC'),
        );
        $outsideWindow = $this->makeOrderFor(
            shop: $shop,
            total: 99.000,
            paidAt: Carbon::parse('2026-05-26 12:00:00', 'UTC'),
        );

        $response = $this->actingAs($manager)->get(route('admin.reports.export'));

        $response->assertOk();
        $rows = array_map('str_getcsv', array_filter(explode("\n", trim($response->streamedContent()))));
        $orderIds = collect(array_slice($rows, 1))->pluck(0)->all();

        $this->assertContains((string) $insideWindow->id, $orderIds);
        $this->assertNotContains((string) $outsideWindow->id, $orderIds);
    }

    /**
     * @return array{0: Shop, 1: User}
     */
    private function makeSubscribedShopWithUser(string $role): array
    {
        $shop = $this->makeSubscribedShop('Bite', 'bite');
        $user = User::factory()->create([
            'shop_id' => $shop->id,
            'role' => $role,
        ]);

        return [$shop, $user];
    }

    private function makeSubscribedShop(string $name, string $slug): Shop
    {
        $shop = Shop::create(['name' => $name, 'slug' => $slug]);
        $shop->trial_ends_at = now()->addDays(14);
        $shop->save();

        return $shop;
    }

    private function makeOrderFor(
        Shop $shop,
        float $total = 10.000,
        string $paymentMethod = 'cash',
        string $status = 'completed',
        ?Carbon $paidAt = null
    ): Order {
        $paidAt ??= now();

        return Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => $status,
            'payment_method' => $paymentMethod,
            'subtotal_amount' => $total,
            'tax_amount' => 0,
            'total_amount' => $total,
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
