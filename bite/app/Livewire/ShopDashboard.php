<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ShopDashboard extends Component
{
    public Shop $shop;

    public $dailyRevenue = 0;

    public $ordersTodayCount = 0;

    public $activeOrdersCount = 0;

    public $itemsSoldToday = 0;

    public $avgOrderValue = 0;

    public $paymentSummary = [];

    public $topProducts = [];

    public $ordersByStatus = [];

    public $weeklyRevenue = [];

    public function mount()
    {
        $this->shop = Auth::user()->shop;
        $this->loadStats();
    }

    public function loadStats()
    {
        $shopId = Auth::user()->shop_id;

        $this->dailyRevenue = (float) Order::where('shop_id', $shopId)
            ->where('status', 'completed')
            ->whereDate('paid_at', today())
            ->sum('total_amount');

        $this->ordersTodayCount = Order::where('shop_id', $shopId)
            ->whereDate('created_at', today())
            ->count();

        $completedOrdersCount = Order::where('shop_id', $shopId)
            ->where('status', 'completed')
            ->whereDate('paid_at', today())
            ->count();

        $this->avgOrderValue = $completedOrdersCount > 0
            ? round($this->dailyRevenue / $completedOrdersCount, 2)
            : 0;

        $this->itemsSoldToday = (int) OrderItem::whereHas('order', function ($query) use ($shopId) {
            $query->where('shop_id', $shopId)
                ->whereIn('status', ['paid', 'preparing', 'ready', 'completed'])
                ->whereDate('paid_at', today());
        })->sum('quantity');

        $this->activeOrdersCount = Order::where('shop_id', $shopId)
            ->whereIn('status', ['unpaid', 'paid', 'preparing', 'ready'])
            ->where(function ($query) {
                $query->where('status', '!=', 'unpaid')
                    ->orWhereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->count();

        $this->paymentSummary = Order::where('shop_id', $shopId)
            ->whereIn('status', ['paid', 'preparing', 'ready', 'completed'])
            ->whereDate('paid_at', today())
            ->whereNotNull('payment_method')
            ->select('payment_method', DB::raw('count(*) as orders'), DB::raw('sum(total_amount) as total'))
            ->groupBy('payment_method')
            ->get()
            ->mapWithKeys(function ($row) {
                return [$row->payment_method => [
                    'orders' => (int) $row->orders,
                    'total' => (float) $row->total,
                ]];
            })
            ->all();

        $this->ordersByStatus = Order::where('shop_id', $shopId)
            ->whereDate('created_at', today())
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $this->topProducts = OrderItem::whereHas('order', function ($query) use ($shopId) {
            $query->where('shop_id', $shopId)
                ->whereIn('status', ['paid', 'preparing', 'ready', 'completed'])
                ->whereBetween('paid_at', [now()->subDays(6)->startOfDay(), now()->endOfDay()]);
        })
            ->select('product_name_snapshot', DB::raw('sum(quantity) as qty'), DB::raw('sum(price_snapshot * quantity) as revenue'))
            ->groupBy('product_name_snapshot')
            ->orderByDesc('qty')
            ->limit(5)
            ->get();

        $weeklyRaw = Order::where('shop_id', $shopId)
            ->where('status', 'completed')
            ->whereBetween('paid_at', [now()->subDays(6)->startOfDay(), now()->endOfDay()])
            ->select(DB::raw('date(paid_at) as day'), DB::raw('sum(total_amount) as total'))
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day')
            ->toArray();

        $this->weeklyRevenue = collect(range(6, 0))
            ->map(function ($offset) use ($weeklyRaw) {
                $day = now()->subDays($offset)->toDateString();

                return [
                    'day' => $day,
                    'total' => (float) ($weeklyRaw[$day] ?? 0),
                ];
            })
            ->all();
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        $this->loadStats();

        return view('livewire.shop-dashboard', [
            'recentOrders' => Order::where('shop_id', Auth::user()->shop_id)
                ->where('status', '!=', 'cancelled')
                ->where(function ($query) {
                    $query->where('status', '!=', 'unpaid')
                        ->orWhereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }
}
