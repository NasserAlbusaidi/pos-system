<?php

namespace App\Livewire;

use App\Livewire\Concerns\AuthorizesRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Shop;
use App\Support\HourlyBuckets;
use App\Support\ShopClock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ShiftReport extends Component
{
    use AuthorizesRole;

    protected function allowedRoles(): array
    {
        return ['manager', 'admin'];
    }

    public Shop $shop;

    public string $date = '';

    public function mount(): void
    {
        $this->shop = Auth::user()->shop;
        $this->date = ShopClock::localDate($this->shop);
    }

    public function updatedDate(): void
    {
        $this->validate([
            'date' => 'required|date|before_or_equal:'.ShopClock::localDate($this->shop),
        ]);
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        $shop = $this->shop ?? Auth::user()->shop;
        $shopId = $shop->id;
        $date = $this->date;
        [$dayStartUtc, $dayEndUtc] = ShopClock::localDayUtcRange($shop, $date);

        $ordersQuery = Order::where('shop_id', $shopId)
            ->revenueRecognized()
            ->whereBetween('paid_at', [$dayStartUtc, $dayEndUtc]);

        $totalOrders = (clone $ordersQuery)->count();
        $totalRevenue = (float) (clone $ordersQuery)->sum('total_amount');
        $totalTax = (float) (clone $ordersQuery)->sum('tax_amount');
        $avgOrder = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 3) : 0;

        // Payment breakdown from the payments table
        $paymentBreakdown = Payment::query()
            ->reportableForPaymentSummary($shopId, $dayStartUtc, $dayEndUtc)
            ->select(
                'method',
                DB::raw('count(*) as count'),
                DB::raw('sum(amount) as total')
            )
            ->groupBy('method')
            ->orderByDesc('total')
            ->get();

        // Top 5 products by quantity sold
        $topProducts = OrderItem::whereHas('order', function ($query) use ($shopId, $dayStartUtc, $dayEndUtc) {
            $query->where('shop_id', $shopId)
                ->revenueRecognized()
                ->whereBetween('paid_at', [$dayStartUtc, $dayEndUtc]);
        })
            ->select(
                'product_name_snapshot_en',
                DB::raw('sum(quantity) as qty'),
                DB::raw('sum(price_snapshot * quantity) as revenue')
            )
            ->groupBy('product_name_snapshot_en')
            ->orderByDesc('qty')
            ->limit(5)
            ->get();

        // Orders by local hour breakdown.
        $ordersByHourRaw = (clone $ordersQuery)
            ->get(['paid_at'])
            ->groupBy(fn (Order $order): string => ShopClock::localHour($shop, $order->paid_at))
            ->map->count()
            ->toArray();

        $ordersByHour = HourlyBuckets::counts($ordersByHourRaw, withClockSuffix: true);

        // Find peak hour
        $peakHour = $ordersByHour->sortByDesc('count')->first();

        return view('livewire.shift-report', [
            'totalOrders' => $totalOrders,
            'totalRevenue' => $totalRevenue,
            'totalTax' => $totalTax,
            'avgOrder' => $avgOrder,
            'paymentBreakdown' => $paymentBreakdown,
            'topProducts' => $topProducts,
            'ordersByHour' => $ordersByHour,
            'peakHour' => $peakHour,
        ]);
    }
}
