<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ShiftReport extends Component
{
    public Shop $shop;

    public string $date = '';

    public function mount(): void
    {
        $this->shop = Auth::user()->shop;
        $this->date = today()->toDateString();
    }

    public function updatedDate(): void
    {
        $this->validate([
            'date' => 'required|date|before_or_equal:today',
        ]);
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        $shopId = Auth::user()->shop_id;
        $date = $this->date;

        $driver = DB::getDriverName();
        $hourExpression = $driver === 'sqlite' ? "strftime('%H', paid_at)" : 'hour(paid_at)';

        // Completed / paid orders for the date
        $ordersQuery = Order::where('shop_id', $shopId)
            ->whereIn('status', ['paid', 'preparing', 'ready', 'completed'])
            ->whereDate('paid_at', $date);

        $totalOrders = (clone $ordersQuery)->count();
        $totalRevenue = (float) (clone $ordersQuery)->sum('total_amount');
        $totalTax = (float) (clone $ordersQuery)->sum('tax_amount');
        $avgOrder = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;

        // Payment breakdown from the payments table
        $paymentBreakdown = Payment::where('shop_id', $shopId)
            ->whereDate('paid_at', $date)
            ->select(
                'method',
                DB::raw('count(*) as count'),
                DB::raw('sum(amount) as total')
            )
            ->groupBy('method')
            ->orderByDesc('total')
            ->get();

        // Top 5 products by quantity sold
        $topProducts = OrderItem::whereHas('order', function ($query) use ($shopId, $date) {
            $query->where('shop_id', $shopId)
                ->whereIn('status', ['paid', 'preparing', 'ready', 'completed'])
                ->whereDate('paid_at', $date);
        })
            ->select(
                'product_name_snapshot',
                DB::raw('sum(quantity) as qty'),
                DB::raw('sum(price_snapshot * quantity) as revenue')
            )
            ->groupBy('product_name_snapshot')
            ->orderByDesc('qty')
            ->limit(5)
            ->get();

        // Orders by hour breakdown
        $ordersByHourRaw = (clone $ordersQuery)
            ->select(DB::raw("{$hourExpression} as hour"), DB::raw('count(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        $ordersByHour = collect(range(0, 23))
            ->map(function ($hour) use ($ordersByHourRaw) {
                $key = str_pad((string) $hour, 2, '0', STR_PAD_LEFT);

                return [
                    'hour' => $key . ':00',
                    'count' => (int) ($ordersByHourRaw[$key] ?? 0),
                ];
            })
            ->values();

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
