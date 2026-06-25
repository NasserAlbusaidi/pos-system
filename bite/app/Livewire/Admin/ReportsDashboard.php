<?php

namespace App\Livewire\Admin;

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

class ReportsDashboard extends Component
{
    use AuthorizesRole;

    protected function allowedRoles(): array
    {
        return ['manager', 'admin'];
    }

    protected function requiredPlanFeature(): ?string
    {
        return 'reports';
    }

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
        $shop = $this->shop ?? Auth::user()->shop;
        $shopId = $shop->id;
        [$from, $to] = ShopClock::recentLocalDaysUtcRange($shop, (int) $this->rangeDays);
        $localDates = ShopClock::recentLocalDates($shop, (int) $this->rangeDays);
        $exportQuery = [
            'from' => $localDates[0] ?? ShopClock::localDate($shop),
            'to' => $localDates[count($localDates) - 1] ?? ShopClock::localDate($shop),
        ];

        $revenueRaw = Order::where('shop_id', $shopId)
            ->revenueRecognized()
            ->whereBetween('paid_at', [$from, $to])
            ->get(['paid_at', 'total_amount'])
            ->groupBy(fn (Order $order): string => ShopClock::localDate($shop, $order->paid_at))
            ->map(fn ($orders): float => (float) $orders->sum('total_amount'))
            ->all();

        $revenueSeries = collect($localDates)
            ->map(function (string $day) use ($revenueRaw) {
                return [
                    'day' => $day,
                    'total' => (float) ($revenueRaw[$day] ?? 0),
                ];
            })
            ->values();

        $ordersByHourRaw = Order::where('shop_id', $shopId)
            ->revenueRecognized()
            ->whereBetween('paid_at', [$from, $to])
            ->get(['paid_at'])
            ->groupBy(fn (Order $order): string => ShopClock::localHour($shop, $order->paid_at))
            ->map->count()
            ->toArray();

        $ordersByHour = HourlyBuckets::counts($ordersByHourRaw);

        $topProducts = OrderItem::whereHas('order', function ($query) use ($shopId, $from, $to) {
            $query->where('shop_id', $shopId)
                ->revenueRecognized()
                ->whereBetween('paid_at', [$from, $to]);
        })
            ->select('product_name_snapshot_en', 'product_name_snapshot_ar', DB::raw('sum(quantity) as qty'), DB::raw('sum(price_snapshot * quantity) as revenue'))
            ->groupBy('product_name_snapshot_en', 'product_name_snapshot_ar')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        $paymentSummary = Payment::query()
            ->reportableForPaymentSummary($shopId, $from, $to)
            ->select('method as payment_method', DB::raw('count(distinct order_id) as orders'), DB::raw('sum(amount) as total'))
            ->groupBy('method')
            ->get();

        $totalRevenue = (float) Order::where('shop_id', $shopId)
            ->revenueRecognized()
            ->whereBetween('paid_at', [$from, $to])
            ->sum('total_amount');

        $totalOrders = Order::where('shop_id', $shopId)
            ->revenueRecognized()
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
            'exportQuery' => $exportQuery,
        ]);
    }
}
