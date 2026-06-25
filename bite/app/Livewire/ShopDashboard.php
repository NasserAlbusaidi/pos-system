<?php

namespace App\Livewire;

use App\Livewire\Concerns\AuthorizesRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Shop;
use App\Support\ShopClock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ShopDashboard extends Component
{
    use AuthorizesRole;

    protected function allowedRoles(): array
    {
        return ['server', 'manager', 'admin'];
    }

    public Shop $shop;

    public $dailyRevenue = 0;

    public $yesterdayRevenue = 0;

    public ?int $revenueDelta = null;

    public $ordersTodayCount = 0;

    public $activeOrdersCount = 0;

    public $itemsSoldToday = 0;

    public $avgOrderValue = 0;

    public $paymentSummary = [];

    public $topProducts = [];

    public $ordersByStatus = [];

    public $weeklyRevenue = [];

    public $revenueHeatmap = [];

    public float $dailyGoal = 0;

    // Notification state
    public $showNotifications = false;

    public $previousUnreadCount = 0;

    public function mount()
    {
        $user = Auth::user();
        $this->shop = $user->shop;

        if ($user->shouldRedirectToOnboarding()) {
            $this->redirect(route('onboarding'), navigate: true);

            return;
        }

        $this->loadStats();
    }

    public function loadStats()
    {
        $shop = $this->shop ?? Auth::user()->shop;
        $shopId = $shop->id;
        [$todayStartUtc, $todayEndUtc] = ShopClock::currentLocalDayUtcRange($shop);
        [$yesterdayStartUtc, $yesterdayEndUtc] = ShopClock::localDayUtcRange($shop, ShopClock::localDate($shop, offsetDays: -1));
        [$weekStartUtc, $weekEndUtc] = ShopClock::recentLocalDaysUtcRange($shop, 7);
        [$heatmapStartUtc, $heatmapEndUtc] = ShopClock::recentLocalDaysUtcRange($shop, 28);
        $weekDates = ShopClock::recentLocalDates($shop, 7);

        $this->dailyRevenue = (float) Order::where('shop_id', $shopId)
            ->revenueRecognized()
            ->whereBetween('paid_at', [$todayStartUtc, $todayEndUtc])
            ->sum('total_amount');

        // Yesterday's paid revenue powers the hero "vs yesterday" delta.
        $this->yesterdayRevenue = (float) Order::where('shop_id', $shopId)
            ->revenueRecognized()
            ->whereBetween('paid_at', [$yesterdayStartUtc, $yesterdayEndUtc])
            ->sum('total_amount');

        // Percentage change vs yesterday — null (not 0/∞) when there is no
        // baseline, so the view never renders a fabricated delta.
        $this->revenueDelta = $this->yesterdayRevenue > 0
            ? (int) round((($this->dailyRevenue - $this->yesterdayRevenue) / $this->yesterdayRevenue) * 100)
            : null;

        $this->ordersTodayCount = Order::where('shop_id', $shopId)
            ->whereBetween('created_at', [$todayStartUtc, $todayEndUtc])
            ->count();

        $revenueOrdersCount = Order::where('shop_id', $shopId)
            ->revenueRecognized()
            ->whereBetween('paid_at', [$todayStartUtc, $todayEndUtc])
            ->count();

        $this->avgOrderValue = $revenueOrdersCount > 0
            ? round($this->dailyRevenue / $revenueOrdersCount, 3)
            : 0;

        $this->itemsSoldToday = (int) OrderItem::whereHas('order', function ($query) use ($shopId, $todayStartUtc, $todayEndUtc) {
            $query->where('shop_id', $shopId)
                ->revenueRecognized()
                ->whereBetween('paid_at', [$todayStartUtc, $todayEndUtc]);
        })->sum('quantity');

        $this->activeOrdersCount = Order::where('shop_id', $shopId)
            ->whereIn('status', ['unpaid', 'paid', 'preparing', 'ready'])
            ->where(function ($query) {
                $query->where('status', '!=', 'unpaid')
                    ->orWhereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->count();

        $this->paymentSummary = Payment::query()
            ->reportableForPaymentSummary($shopId, $todayStartUtc, $todayEndUtc)
            ->select('method as payment_method', DB::raw('count(*) as orders'), DB::raw('sum(amount) as total'))
            ->groupBy('method')
            ->get()
            ->mapWithKeys(function ($row) {
                return [$row->payment_method => [
                    'orders' => (int) $row->orders,
                    'total' => (float) $row->total,
                ]];
            })
            ->all();

        $this->ordersByStatus = Order::where('shop_id', $shopId)
            ->whereBetween('created_at', [$todayStartUtc, $todayEndUtc])
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $this->topProducts = OrderItem::whereHas('order', function ($query) use ($shopId, $weekStartUtc, $weekEndUtc) {
            $query->where('shop_id', $shopId)
                ->revenueRecognized()
                ->whereBetween('paid_at', [$weekStartUtc, $weekEndUtc]);
        })
            ->select('product_name_snapshot_en', DB::raw('sum(quantity) as qty'), DB::raw('sum(price_snapshot * quantity) as revenue'))
            ->groupBy('product_name_snapshot_en')
            ->orderByDesc('qty')
            ->limit(5)
            ->get();

        $weeklyRaw = Order::where('shop_id', $shopId)
            ->revenueRecognized()
            ->whereBetween('paid_at', [$weekStartUtc, $weekEndUtc])
            ->get(['paid_at', 'total_amount'])
            ->groupBy(fn (Order $order): string => ShopClock::localDate($shop, $order->paid_at))
            ->map(fn ($orders): float => (float) $orders->sum('total_amount'))
            ->all();

        $this->weeklyRevenue = collect($weekDates)
            ->map(function (string $day) use ($weeklyRaw) {
                return [
                    'day' => $day,
                    'total' => (float) ($weeklyRaw[$day] ?? 0),
                ];
            })
            ->all();

        // Revenue heatmap: last 4 local weeks, by local day-of-week and hour.
        $heatmapRaw = Order::where('shop_id', $shopId)
            ->revenueRecognized()
            ->whereBetween('paid_at', [$heatmapStartUtc, $heatmapEndUtc])
            ->get(['paid_at', 'total_amount'])
            ->groupBy(fn (Order $order): string => ShopClock::localWeekdayIndex($shop, $order->paid_at).'|'.ShopClock::localHour($shop, $order->paid_at))
            ->map(function ($orders, string $bucket): array {
                [$dow, $hour] = explode('|', $bucket);

                return [
                    'dow' => (int) $dow,
                    'hour' => (int) $hour,
                    'total' => (float) $orders->sum('total_amount'),
                ];
            })
            ->values();

        $this->revenueHeatmap = $heatmapRaw->all();

        // Daily goal from branding config (owner can set it)
        $branding = $this->shop->branding ?? [];
        $this->dailyGoal = (float) ($branding['daily_goal'] ?? 0);
    }

    public function setDailyGoal(float $goal): void
    {
        abort_unless($this->canManageDashboardSettings(), 403, 'Unauthorized role.');

        $goal = max(0, round($goal, 3));
        $branding = $this->shop->branding ?? [];
        $this->shop->update([
            'branding' => array_merge($branding, ['daily_goal' => $goal]),
        ]);
        $this->dailyGoal = $goal;
        $this->dispatch('toast', message: 'Daily goal updated.', variant: 'success');
    }

    public function toggleNotifications()
    {
        $this->showNotifications = ! $this->showNotifications;

        // Mark all as read when opening
        if ($this->showNotifications) {
            $this->shop->unreadNotifications->markAsRead();
        }
    }

    public function clearAllNotifications()
    {
        $role = Auth::user()->role;
        if (! in_array($role, ['admin', 'manager'])) {
            return;
        }

        $this->shop->notifications()->delete();
        $this->showNotifications = false;
    }

    public function checkForNewNotifications()
    {
        $currentUnread = $this->shop->unreadNotifications()->count();

        if ($currentUnread > $this->previousUnreadCount && $this->previousUnreadCount >= 0) {
            $this->dispatch('new-order-sound');
        }

        $this->previousUnreadCount = $currentUnread;
    }

    public function canManageDashboardSettings(): bool
    {
        return in_array(Auth::user()?->role, ['manager', 'admin'], true);
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        $this->loadStats();
        $this->checkForNewNotifications();

        $notifications = $this->shop->notifications()
            ->latest()
            ->take(20)
            ->get();

        $unreadCount = $this->shop->unreadNotifications()->count();

        return view('livewire.shop-dashboard', [
            'recentOrders' => Order::where('shop_id', Auth::user()->shop_id)
                ->where('status', '!=', 'cancelled')
                ->where(function ($query) {
                    $query->where('status', '!=', 'unpaid')
                        ->orWhereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->with('items')
                ->latest()
                ->take(5)
                ->get(),
            'canManageDashboardSettings' => $this->canManageDashboardSettings(),
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }
}
