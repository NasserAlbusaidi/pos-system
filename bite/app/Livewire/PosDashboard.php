<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemModifier;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Services\LoyaltyService;
use App\Services\PrintNodeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

class PosDashboard extends Component
{
    public Shop $shop;

    public $splitOrderId = null;

    public $splitQuantities = [];

    public $splitError = null;

    public $paymentOrderId = null;

    public $paymentRows = [];

    public $splitGuestCount = 2;

    public $splitAmount = null;

    public $paymentError = null;

    public $salesToday = 0;

    public $ordersToday = 0;

    public $unpaidCount = 0;

    public $readyCount = 0;

    public int $lastOrderCount = -1;

    public $managerPin = '';

    // Protected: these must not be client-settable to prevent manager PIN bypass.
    protected $pendingAction = null;

    protected $pendingPayload = [];

    public $showManagerModal = false;

    public $managerError = null;

    // Protected: prevents client from setting this to true via Livewire wire protocol.
    protected $managerOverrideApproved = false;

    public array $upsellSuggestions = [];

    public function mount(): void
    {
        $this->shop = Auth::user()->shop;
        $this->loadUpsellData();
    }

    protected function loadUpsellData(): void
    {
        // Pre-compute co-purchase patterns: for each product, find top 3 products
        // frequently bought together (in the same order) over the last 30 days.
        $shopId = Auth::user()->shop_id;

        $coPurchases = DB::select("
            SELECT
                oi1.product_id AS source_id,
                oi2.product_id AS suggested_id,
                p.name_en AS suggested_name,
                COUNT(*) AS frequency
            FROM order_items oi1
            JOIN order_items oi2 ON oi1.order_id = oi2.order_id AND oi1.product_id != oi2.product_id
            JOIN orders o ON o.id = oi1.order_id
            JOIN products p ON p.id = oi2.product_id
            WHERE o.shop_id = ?
              AND o.status IN ('paid', 'preparing', 'ready', 'completed')
              AND o.paid_at >= ?
              AND p.is_available = 1
            GROUP BY oi1.product_id, oi2.product_id, p.name_en
            HAVING COUNT(*) >= 2
            ORDER BY oi1.product_id, frequency DESC
        ", [$shopId, now()->subDays(30)->toDateString()]);

        $suggestions = [];
        foreach ($coPurchases as $row) {
            $sourceId = (int) $row->source_id;
            if (! isset($suggestions[$sourceId])) {
                $suggestions[$sourceId] = [];
            }
            if (count($suggestions[$sourceId]) < 3) {
                $suggestions[$sourceId][] = [
                    'id' => (int) $row->suggested_id,
                    'name' => $row->suggested_name,
                    'frequency' => (int) $row->frequency,
                ];
            }
        }
        $this->upsellSuggestions = $suggestions;
    }

    public function toggle86(int $productId): void
    {
        $product = Product::where('shop_id', Auth::user()->shop_id)
            ->findOrFail($productId);

        $product->update(['is_available' => ! $product->is_available]);

        AuditLog::record(
            $product->is_available ? 'product.restored' : 'product.86d',
            $product,
            ['product_name' => $product->name_en]
        );

        $this->dispatch('toast',
            message: $product->is_available
                ? "{$product->name_en} is back on the menu."
                : "{$product->name_en} marked as 86'd (sold out).",
            variant: $product->is_available ? 'success' : 'error'
        );
    }

    protected function loadStats(): void
    {
        $shopId = Auth::user()->shop_id;
        $today = today()->toDateString();

        $stats = DB::query()
            ->from('orders')
            ->where('shop_id', $shopId)
            ->selectRaw(implode(', ', [
                'COALESCE(SUM(CASE WHEN status IN (?, ?, ?, ?) AND DATE(paid_at) = ? THEN total_amount ELSE 0 END), 0) AS sales_today',
                'COUNT(CASE WHEN DATE(created_at) = ? THEN 1 END) AS orders_today',
                'COUNT(CASE WHEN status = ? THEN 1 END) AS unpaid_count',
                'COUNT(CASE WHEN status = ? THEN 1 END) AS ready_count',
            ]), ['paid', 'preparing', 'ready', 'completed', $today, $today, 'unpaid', 'ready'])
            ->first();

        $this->salesToday = (float) $stats->sales_today;
        $this->ordersToday = (int) $stats->orders_today;
        $this->unpaidCount = (int) $stats->unpaid_count;
        $this->readyCount = (int) $stats->ready_count;
    }

    public function clearOldOrders()
    {
        if ($this->requiresManagerOverride()) {
            $this->requestManagerOverride('clearOldOrders');

            return;
        }

        $shopId = Auth::user()->shop_id;

        Order::where('shop_id', $shopId)
            ->where('status', 'unpaid')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->whereDoesntHave('payments')
            ->update(['status' => 'cancelled']);

        $this->completeReadyOrdersForCleanup(
            $shopId,
            fn ($query) => $query->where('updated_at', '<', now()->subMinutes(30))
        );

        AuditLog::record('orders.cleared', null, ['shop_id' => $shopId]);
        session()->flash('message', 'Old orders cleared.');
    }

    public function systemReset()
    {
        if ($this->requiresManagerOverride()) {
            $this->requestManagerOverride('systemReset');

            return;
        }

        $shopId = Auth::user()->shop_id;

        Order::where('shop_id', $shopId)
            ->where('status', 'unpaid')
            ->whereDoesntHave('payments')
            ->update(['status' => 'cancelled']);

        $this->completeReadyOrdersForCleanup($shopId);

        AuditLog::record('orders.system_reset', null, ['shop_id' => $shopId]);
        session()->flash('message', 'System reset complete.');
    }

    public function cancelOrder(int $orderId): void
    {
        if ($this->requiresManagerOverride()) {
            $this->requestManagerOverride('cancelOrder', ['orderId' => $orderId]);

            return;
        }

        $order = Order::where('shop_id', Auth::user()->shop_id)
            ->whereIn('status', ['unpaid', 'paid', 'preparing', 'ready'])
            ->findOrFail($orderId);

        $previousStatus = $order->status;

        DB::transaction(function () use ($order) {
            $order->update(['status' => 'cancelled']);
        });

        AuditLog::record('order.cancelled', $order, [
            'cancelled_by' => Auth::user()->name,
            'previous_status' => $previousStatus,
        ]);

        $this->dispatch('toast',
            message: __('admin.order_cancelled_message', ['id' => $order->id]),
            variant: 'success'
        );
    }

    protected function completeReadyOrdersForCleanup(int $shopId, ?callable $scope = null): void
    {
        DB::transaction(function () use ($shopId, $scope): void {
            $query = Order::where('shop_id', $shopId)
                ->where('status', 'ready')
                ->lockForUpdate();

            if ($scope) {
                $scope($query);
            }

            $orders = $query->with('items.product')->get();

            foreach ($orders as $order) {
                $order->update([
                    'status' => 'completed',
                    'fulfilled_at' => $order->fulfilled_at ?: now(),
                ]);
            }
        });
    }

    protected function requiresManagerOverride(): bool
    {
        if ($this->managerOverrideApproved) {
            $this->managerOverrideApproved = false;

            return false;
        }

        $role = Auth::user()->role ?? 'server';

        return ! in_array($role, ['admin', 'manager'], true);
    }

    public function requestManagerOverride(string $action, array $payload = [])
    {
        $this->pendingAction = $action;
        $this->pendingPayload = $payload;
        $this->managerPin = '';
        $this->managerError = null;
        $this->showManagerModal = true;
    }

    public function confirmManagerOverride()
    {
        $throttleKey = $this->managerOverrideThrottleKey();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->managerError = "Too many attempts. Try again in {$seconds} seconds.";

            return;
        }

        $pin = trim($this->managerPin);
        if ($pin === '' || ! preg_match('/^\d{4,6}$/', $pin)) {
            RateLimiter::hit($throttleKey, 60);
            $this->managerError = 'Manager approval failed.';

            return;
        }

        $manager = User::where('shop_id', Auth::user()->shop_id)
            ->whereIn('role', ['admin', 'manager'])
            ->get()
            ->first(function ($user) use ($pin) {
                return $user->pin_code && Hash::check($pin, $user->pin_code);
            });

        if (! $manager) {
            RateLimiter::hit($throttleKey, 60);
            $this->managerError = 'Manager approval failed.';

            return;
        }

        RateLimiter::clear($throttleKey);
        $this->showManagerModal = false;
        $this->managerPin = '';
        $this->managerOverrideApproved = true;

        $action = $this->pendingAction;
        $payload = $this->pendingPayload;
        $this->pendingAction = null;
        $this->pendingPayload = [];

        if ($action === 'clearOldOrders') {
            $this->clearOldOrders();

            return;
        }

        if ($action === 'systemReset') {
            $this->systemReset();

            return;
        }

        if ($action === 'cancelOrder') {
            $this->cancelOrder($payload['orderId']);

            return;
        }
    }

    public function cancelManagerOverride()
    {
        $this->reset(['showManagerModal', 'managerPin', 'managerError']);
        $this->pendingAction = null;
        $this->pendingPayload = [];
        $this->managerOverrideApproved = false;
    }

    protected function managerOverrideThrottleKey(): string
    {
        return 'manager-override:'.Auth::user()->shop_id.'|'.request()->ip();
    }

    public function markAsPaid(int $orderId, string $method = 'cash')
    {
        $allowedMethods = ['cash', 'card', 'voucher'];
        if (! in_array($method, $allowedMethods, true)) {
            $method = 'cash';
        }

        $order = Order::where('shop_id', Auth::user()->shop_id)
            ->where('id', $orderId)
            ->firstOrFail();

        $this->recordPaymentsForOrder($order, [
            ['amount' => $order->balance_due, 'method' => $method],
        ]);
    }

    protected function recordPaymentsForOrder(Order $order, array $rows): void
    {
        $allowedMethods = ['cash', 'card', 'voucher'];

        $rows = collect($rows)
            ->map(fn ($row) => [
                'amount' => round((float) ($row['amount'] ?? 0), 2),
                'method' => in_array(trim((string) ($row['method'] ?? '')), $allowedMethods, true)
                    ? trim((string) $row['method'])
                    : 'cash',
            ])
            ->filter(fn ($row) => $row['amount'] > 0)
            ->values()
            ->all();

        if (empty($rows)) {
            return;
        }

        foreach ($rows as $row) {
            Payment::forceCreate([
                'shop_id' => $order->shop_id,
                'order_id' => $order->id,
                'amount' => $row['amount'],
                'method' => $row['method'],
                'created_by' => Auth::id(),
                'paid_at' => now(),
            ]);

            AuditLog::record('payment.recorded', $order, [
                'amount' => $row['amount'],
                'method' => $row['method'],
            ]);
        }

        $order->refresh();

        if ($order->balance_due <= 0 && $order->status !== 'paid') {
            $order->update([
                'status' => 'paid',
                'payment_method' => count($rows) > 1 ? 'split' : ($rows[0]['method'] ?? 'cash'),
                'paid_at' => now(),
            ]);

            AuditLog::record('order.paid', $order, [
                'payment_method' => $order->payment_method,
                'total' => $order->total_amount,
            ]);
            app(LoyaltyService::class)->award($order);
            app(PrintNodeService::class)->printOrder($order, 'kitchen');
        }
    }

    public function markAsDelivered(int $orderId)
    {
        $result = DB::transaction(function () use ($orderId) {
            $order = Order::where('shop_id', Auth::user()->shop_id)
                ->where('id', $orderId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status !== 'ready') {
                return [
                    'processed' => false,
                    'order' => $order,
                ];
            }

            if ($order->fulfilled_at) {
                return [
                    'processed' => false,
                    'order' => $order,
                ];
            }

            $order->update([
                'status' => 'completed',
                'fulfilled_at' => now(),
            ]);

            return [
                'processed' => true,
                'order' => $order->fresh(),
            ];
        });

        if (! $result['processed']) {
            session()->flash('message', 'Order is already completed or not ready yet.');

            return;
        }

        /** @var \App\Models\Order $order */
        $order = $result['order'];

        AuditLog::record('order.completed', $order);
        app(PrintNodeService::class)->printOrder($order, 'receipt');
    }

    public function openSplit(int $orderId)
    {
        $order = Order::where('shop_id', Auth::user()->shop_id)
            ->with('items.modifiers')
            ->findOrFail($orderId);

        if ($order->status !== 'unpaid') {
            $this->splitError = 'Only unpaid orders can be split.';

            return;
        }

        $this->splitOrderId = $order->id;
        $this->splitError = null;
        $this->splitQuantities = $order->items
            ->mapWithKeys(fn ($item) => [$item->id => 0])
            ->all();
    }

    public function closeSplit()
    {
        $this->reset(['splitOrderId', 'splitQuantities', 'splitError']);
    }

    public function applySplit()
    {
        if (! $this->splitOrderId) {
            return;
        }

        $result = DB::transaction(function () {
            $order = Order::where('shop_id', Auth::user()->shop_id)
                ->with('items.modifiers')
                ->lockForUpdate()
                ->findOrFail($this->splitOrderId);

            if ($order->status !== 'unpaid') {
                return ['error' => 'Only unpaid orders can be split.'];
            }

            $moves = [];
            foreach ($order->items as $item) {
                $qty = (int) ($this->splitQuantities[$item->id] ?? 0);
                if ($qty < 0 || $qty > $item->quantity) {
                    return ['error' => 'Split quantities must be between 0 and the item quantity.'];
                }
                if ($qty > 0) {
                    $moves[] = ['item' => $item, 'qty' => $qty];
                }
            }

            if (empty($moves)) {
                return ['error' => 'Select at least one item to split.'];
            }

            $splitGroupId = $order->split_group_id ?: (string) Str::uuid();
            if (! $order->split_group_id) {
                $order->update(['split_group_id' => $splitGroupId]);
            }

            $newOrder = Order::forceCreate([
                'shop_id' => $order->shop_id,
                'parent_order_id' => $order->id,
                'split_group_id' => $splitGroupId,
                'customer_name' => $order->customer_name,
                'status' => 'unpaid',
                'subtotal_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => 0,
                'expires_at' => $order->expires_at ?? now()->addMinutes(6),
            ]);

            foreach ($moves as $move) {
                /** @var \App\Models\OrderItem $item */
                $item = $move['item'];
                $qty = $move['qty'];

                if ($qty === $item->quantity) {
                    $item->update(['order_id' => $newOrder->id]);

                    continue;
                }

                $item->update(['quantity' => $item->quantity - $qty]);

                $newItem = OrderItem::create([
                    'order_id' => $newOrder->id,
                    'product_id' => $item->product_id,
                    'product_name_snapshot_en' => $item->product_name_snapshot_en,
                    'product_name_snapshot_ar' => $item->product_name_snapshot_ar,
                    'price_snapshot' => $item->price_snapshot,
                    'quantity' => $qty,
                ]);

                foreach ($item->modifiers as $modifier) {
                    OrderItemModifier::create([
                        'order_item_id' => $newItem->id,
                        'modifier_option_name_snapshot_en' => $modifier->modifier_option_name_snapshot_en,
                        'modifier_option_name_snapshot_ar' => $modifier->modifier_option_name_snapshot_ar,
                        'price_adjustment_snapshot' => $modifier->price_adjustment_snapshot,
                    ]);
                }
            }

            $orderSubtotal = (float) $order->items()->sum(DB::raw('price_snapshot * quantity'));
            $newSubtotal = (float) $newOrder->items()->sum(DB::raw('price_snapshot * quantity'));

            $originalSubtotal = (float) ($order->subtotal_amount ?? $order->total_amount);
            $originalTax = (float) ($order->tax_amount ?? 0);
            $baseSubtotal = $originalSubtotal > 0 ? $originalSubtotal : ($orderSubtotal + $newSubtotal);

            $newTax = $baseSubtotal > 0 ? round($originalTax * ($newSubtotal / $baseSubtotal), 2) : 0;
            $orderTax = round($originalTax - $newTax, 2);

            $order->update([
                'subtotal_amount' => $orderSubtotal,
                'tax_amount' => $orderTax,
                'total_amount' => round($orderSubtotal + $orderTax, 2),
            ]);
            $newOrder->update([
                'subtotal_amount' => $newSubtotal,
                'tax_amount' => $newTax,
                'total_amount' => round($newSubtotal + $newTax, 2),
            ]);

            if ($order->items()->count() === 0) {
                $order->update(['status' => 'cancelled']);
            }

            AuditLog::record('order.split', $order, [
                'split_group_id' => $order->split_group_id,
                'items_moved' => collect($moves)->map(fn ($move) => [
                    'item_id' => $move['item']->id,
                    'qty' => $move['qty'],
                ])->all(),
            ]);

            return ['error' => null];
        });

        if ($result['error']) {
            $this->splitError = $result['error'];

            return;
        }

        session()->flash('message', 'Order split successfully.');
        $this->closeSplit();
    }

    public function openPayment(int $orderId)
    {
        $order = Order::where('shop_id', Auth::user()->shop_id)
            ->with('payments')
            ->findOrFail($orderId);

        if ($order->status !== 'unpaid') {
            $this->paymentError = 'Only unpaid orders can accept split payments.';

            return;
        }

        $this->paymentOrderId = $order->id;
        $this->paymentError = null;
        $this->splitAmount = null;
        $this->splitGuestCount = 2;
        $this->paymentRows = [
            ['amount' => $order->balance_due, 'method' => 'card'],
        ];
    }

    public function closePayment()
    {
        $this->reset(['paymentOrderId', 'paymentRows', 'paymentError', 'splitGuestCount', 'splitAmount']);
    }

    public function addPaymentRow()
    {
        $this->paymentRows[] = ['amount' => 0, 'method' => 'cash'];
    }

    public function removePaymentRow($index)
    {
        unset($this->paymentRows[$index]);
        $this->paymentRows = array_values($this->paymentRows);
    }

    public function splitByGuests()
    {
        if (! $this->paymentOrderId) {
            return;
        }

        $order = Order::where('shop_id', Auth::user()->shop_id)->findOrFail($this->paymentOrderId);
        $guests = max(1, min(20, (int) $this->splitGuestCount));
        $balance = $order->balance_due;
        $decimals = $this->shop->currency_decimals ?? 3;
        $factor = pow(10, $decimals);
        $base = floor(($balance / $guests) * $factor) / $factor;
        $rows = [];

        for ($i = 0; $i < $guests; $i++) {
            $rows[] = ['amount' => $base, 'method' => 'card'];
        }

        $remainder = round($balance - ($base * $guests), $decimals);
        if ($remainder > 0 && isset($rows[0])) {
            $rows[0]['amount'] = round($rows[0]['amount'] + $remainder, $decimals);
        }

        $this->paymentRows = $rows;
    }

    public function splitByAmount()
    {
        if (! $this->paymentOrderId) {
            return;
        }

        $order = Order::where('shop_id', Auth::user()->shop_id)->findOrFail($this->paymentOrderId);
        $amount = round((float) $this->splitAmount, 2);
        if ($amount <= 0 || $amount > $order->balance_due) {
            $this->paymentError = 'Enter a valid amount up to the remaining balance.';

            return;
        }

        $this->paymentRows = [
            ['amount' => $amount, 'method' => 'card'],
        ];
    }

    public function applyPayments()
    {
        if (! $this->paymentOrderId) {
            return;
        }

        $order = Order::where('shop_id', Auth::user()->shop_id)
            ->with('payments')
            ->findOrFail($this->paymentOrderId);

        $rows = collect($this->paymentRows)->map(function ($row) {
            return [
                'amount' => round((float) ($row['amount'] ?? 0), 2),
                'method' => trim((string) ($row['method'] ?? 'cash')),
            ];
        })->filter(fn ($row) => $row['amount'] > 0)->values()->all();

        if (empty($rows)) {
            $this->paymentError = 'Add at least one payment.';

            return;
        }

        $sum = round(collect($rows)->sum('amount'), 2);
        if ($sum > $order->balance_due + 0.01) {
            $this->paymentError = 'Payments cannot exceed the remaining balance.';

            return;
        }

        $this->recordPaymentsForOrder($order, $rows);
        session()->flash('message', $order->balance_due <= 0 ? 'Order paid.' : 'Partial payment recorded.');
        $this->closePayment();
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        $this->loadStats();

        $shopId = Auth::user()->shop_id;

        $orders = Order::where('shop_id', $shopId)
            ->whereIn('status', ['unpaid', 'ready'])
            ->with(['items.modifiers', 'payments'])
            ->latest()
            ->get();

        $currentCount = $orders->count();
        if ($this->lastOrderCount >= 0 && $currentCount > $this->lastOrderCount) {
            $this->dispatch('pos-new-order');
        }
        $this->lastOrderCount = $currentCount;

        $menuCategories = Category::where('shop_id', $shopId)
            ->where('is_active', true)
            ->with(['products' => fn ($q) => $q->where('is_visible', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return view('livewire.pos-dashboard', [
            'orders' => $orders,
            'menuCategories' => $menuCategories,
            'splitOrder' => $this->splitOrderId
                ? Order::where('shop_id', $shopId)->with('items.modifiers')->find($this->splitOrderId)
                : null,
            'paymentOrder' => $this->paymentOrderId
                ? Order::where('shop_id', $shopId)->with('payments')->find($this->paymentOrderId)
                : null,
        ]);
    }
}
