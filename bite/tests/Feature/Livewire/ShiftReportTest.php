<?php

namespace Tests\Feature\Livewire;

use App\Livewire\ShiftReport;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ShiftReportTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_shift_report_uses_shop_timezone_for_selected_business_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-24 21:30:00', 'UTC'));

        $shop = Shop::factory()->create([
            'branding' => ['timezone' => 'Asia/Muscat'],
        ]);
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
            ->test(ShiftReport::class)
            ->assertSet('date', '2026-06-25')
            ->assertViewHas('totalRevenue', fn (float $totalRevenue): bool => abs($totalRevenue - 5.000) < 0.001)
            ->assertViewHas('paymentBreakdown', function ($paymentBreakdown): bool {
                $cash = $paymentBreakdown->firstWhere('method', 'cash');

                return $paymentBreakdown->count() === 1
                    && $cash !== null
                    && abs((float) $cash->total - 5.000) < 0.001;
            })
            ->assertViewHas('ordersByHour', function ($ordersByHour): bool {
                $localHour = collect($ordersByHour)->firstWhere('hour', '01:00');
                $previousLocalHour = collect($ordersByHour)->firstWhere('hour', '12:00');

                return ((int) ($localHour['count'] ?? 0)) === 1
                    && ((int) ($previousLocalHour['count'] ?? 0)) === 0;
            });
    }

    public function test_shift_report_excludes_other_shops_orders_payments_and_products(): void
    {
        $shop = Shop::factory()->create();
        $otherShop = Shop::factory()->create();
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);

        $ownOrder = $this->makePaidOrder($shop, 12.000, now());
        $this->recordPayment($shop, $ownOrder, 12.000, 'cash', $ownOrder->paid_at);
        $this->recordOrderItem($ownOrder, 'House Latte', 6.000, 2);

        $otherOrder = $this->makePaidOrder($otherShop, 99.000, now());
        $this->recordPayment($otherShop, $otherOrder, 99.000, 'card', $otherOrder->paid_at);
        $this->recordOrderItem($otherOrder, 'Other Shop Steak', 99.000, 1);

        Livewire::actingAs($manager)
            ->test(ShiftReport::class)
            ->assertViewHas('totalOrders', 1)
            ->assertViewHas('totalRevenue', fn (float $totalRevenue): bool => abs($totalRevenue - 12.000) < 0.001)
            ->assertViewHas('paymentBreakdown', function ($paymentBreakdown): bool {
                $cash = $paymentBreakdown->firstWhere('method', 'cash');

                return $paymentBreakdown->count() === 1
                    && $cash !== null
                    && abs((float) $cash->total - 12.000) < 0.001;
            })
            ->assertViewHas('topProducts', function ($topProducts): bool {
                return $topProducts->pluck('product_name_snapshot_en')->all() === ['House Latte'];
            });
    }

    public function test_shift_report_payment_breakdown_excludes_non_revenue_order_payments(): void
    {
        $shop = Shop::factory()->create();
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);

        $paidOrder = $this->makePaidOrder($shop, 12.000, now());
        $this->recordPayment($shop, $paidOrder, 12.000, 'cash', $paidOrder->paid_at);

        $cancelledOrder = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'cancelled',
            'subtotal_amount' => 99.000,
            'tax_amount' => 0,
            'total_amount' => 99.000,
            'payment_method' => 'cash',
            'paid_at' => now(),
        ]);
        $this->recordPayment($shop, $cancelledOrder, 99.000, 'cash', now());

        $unpaidOrder = Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'unpaid',
            'subtotal_amount' => 77.000,
            'tax_amount' => 0,
            'total_amount' => 77.000,
            'payment_method' => null,
            'paid_at' => null,
        ]);
        $this->recordPayment($shop, $unpaidOrder, 77.000, 'voucher', now());

        Livewire::actingAs($manager)
            ->test(ShiftReport::class)
            ->assertViewHas('totalRevenue', fn (float $totalRevenue): bool => abs($totalRevenue - 12.000) < 0.001)
            ->assertViewHas('paymentBreakdown', function ($paymentBreakdown): bool {
                $cash = $paymentBreakdown->firstWhere('method', 'cash');

                return $paymentBreakdown->count() === 1
                    && $cash !== null
                    && abs((float) $cash->total - 12.000) < 0.001;
            });
    }

    public function test_shift_report_includes_refund_reversals_on_the_refund_business_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-25 12:00:00', 'UTC'));

        $shop = Shop::factory()->create();
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);
        $yesterday = Carbon::parse('2026-06-24 10:00:00', 'UTC');

        $order = $this->makePaidOrder($shop, 10.000, $yesterday);
        $original = $this->recordPayment($shop, $order, 10.000, 'cash', $yesterday);
        $order->update(['status' => 'cancelled', 'payment_method' => 'refunded']);
        $this->recordPayment($shop, $order, -10.000, 'cash', now(), $original->id);

        Livewire::actingAs($manager)
            ->test(ShiftReport::class)
            ->assertViewHas('totalOrders', 0)
            ->assertViewHas('totalRevenue', fn (float $totalRevenue): bool => abs($totalRevenue) < 0.001)
            ->assertViewHas('paymentBreakdown', function ($paymentBreakdown): bool {
                $cash = $paymentBreakdown->firstWhere('method', 'cash');

                return $paymentBreakdown->count() === 1
                    && $cash !== null
                    && abs((float) $cash->total + 10.000) < 0.001;
            });
    }

    public function test_same_day_voided_payment_nets_to_zero_in_shift_report_breakdown(): void
    {
        $shop = Shop::factory()->create();
        $manager = User::factory()->create(['shop_id' => $shop->id, 'role' => 'manager']);

        $order = $this->makePaidOrder($shop, 10.000, now());
        $original = $this->recordPayment($shop, $order, 10.000, 'cash', now());
        $order->update(['status' => 'cancelled', 'payment_method' => 'refunded']);
        $this->recordPayment($shop, $order, -10.000, 'cash', now(), $original->id);

        Livewire::actingAs($manager)
            ->test(ShiftReport::class)
            ->assertViewHas('totalOrders', 0)
            ->assertViewHas('paymentBreakdown', function ($paymentBreakdown): bool {
                $cash = $paymentBreakdown->firstWhere('method', 'cash');

                return $paymentBreakdown->count() === 1
                    && $cash !== null
                    && abs((float) $cash->total) < 0.001
                    && (int) $cash->count === 2;
            });
    }

    private function makePaidOrder(Shop $shop, float $total, Carbon $paidAt): Order
    {
        return Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'completed',
            'subtotal_amount' => $total,
            'tax_amount' => 0,
            'total_amount' => $total,
            'payment_method' => 'cash',
            'paid_at' => $paidAt,
            'fulfilled_at' => $paidAt,
        ]);
    }

    private function recordPayment(
        Shop $shop,
        Order $order,
        float $amount,
        string $method,
        Carbon $paidAt,
        ?int $reversesPaymentId = null
    ): Payment {
        return Payment::forceCreate([
            'shop_id' => $shop->id,
            'order_id' => $order->id,
            'amount' => $amount,
            'method' => $method,
            'paid_at' => $paidAt,
            'reverses_payment_id' => $reversesPaymentId,
        ]);
    }

    private function recordOrderItem(Order $order, string $name, float $price, int $quantity): void
    {
        OrderItem::create([
            'order_id' => $order->id,
            'product_name_snapshot_en' => $name,
            'price_snapshot' => $price,
            'quantity' => $quantity,
        ]);
    }
}
