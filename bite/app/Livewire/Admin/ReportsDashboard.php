<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ReportsDashboard extends Component
{
    public Shop $shop;

    public $rangeDays = 30;

    public function mount(): void
    {
        $this->shop = Auth::user()->shop;
    }

    public function updatedRangeDays(): void
    {
        $this->rangeDays = max(1, min(365, (int) $this->rangeDays));
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        $shopId = Auth::user()->shop_id;
        $from = now()->subDays($this->rangeDays - 1)->startOfDay();
        $to = now()->endOfDay();

        $driver = DB::getDriverName();
        $hourExpression = $driver === 'sqlite' ? "strftime('%H', paid_at)" : 'hour(paid_at)';

        $revenueRaw = Order::where('shop_id', $shopId)
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$from, $to])
            ->select(DB::raw('date(paid_at) as day'), DB::raw('sum(total_amount) as total'))
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day')
            ->toArray();

        $revenueSeries = collect(range($this->rangeDays - 1, 0))
            ->map(function ($offset) use ($revenueRaw) {
                $day = now()->subDays($offset)->toDateString();

                return [
                    'day' => $day,
                    'total' => (float) ($revenueRaw[$day] ?? 0),
                ];
            })
            ->values();

        $ordersByHourRaw = Order::where('shop_id', $shopId)
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$from, $to])
            ->select(DB::raw("{$hourExpression} as hour"), DB::raw('count(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        $ordersByHour = collect(range(0, 23))
            ->map(function ($hour) use ($ordersByHourRaw) {
                $key = str_pad((string) $hour, 2, '0', STR_PAD_LEFT);

                return [
                    'hour' => $key,
                    'count' => (int) ($ordersByHourRaw[$key] ?? 0),
                ];
            })
            ->values();

        $topProducts = OrderItem::whereHas('order', function ($query) use ($shopId, $from, $to) {
            $query->where('shop_id', $shopId)
                ->where('status', 'completed')
                ->whereBetween('paid_at', [$from, $to]);
        })
            ->select('product_name_snapshot_en', 'product_name_snapshot_ar', DB::raw('sum(quantity) as qty'), DB::raw('sum(price_snapshot * quantity) as revenue'))
            ->groupBy('product_name_snapshot_en', 'product_name_snapshot_ar')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        $paymentSummary = Order::where('shop_id', $shopId)
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$from, $to])
            ->whereNotNull('payment_method')
            ->select('payment_method', DB::raw('count(*) as orders'), DB::raw('sum(total_amount) as total'))
            ->groupBy('payment_method')
            ->get();

        $totalRevenue = (float) Order::where('shop_id', $shopId)
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$from, $to])
            ->sum('total_amount');

        $totalOrders = Order::where('shop_id', $shopId)
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$from, $to])
            ->count();

        $avgOrder = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 3) : 0;

        return view('livewire.admin.reports-dashboard', [
            'rangeDays' => $this->rangeDays,
            'revenueSeries' => $revenueSeries,
            'ordersByHour' => $ordersByHour,
            'topProducts' => $topProducts,
            'paymentSummary' => $paymentSummary,
            'totalRevenue' => $totalRevenue,
            'totalOrders' => $totalOrders,
            'avgOrder' => $avgOrder,
        ]);
    }
}
