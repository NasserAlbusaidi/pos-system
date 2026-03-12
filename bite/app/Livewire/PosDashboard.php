<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemModifier;
use App\Models\Payment;
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

    public $managerPin = '';

    // Protected: these must not be client-settable to prevent manager PIN bypass.
    protected $pendingAction = null;

    protected $pendingPayload = [];

    public $showManagerModal = false;

    public $managerError = null;

    // Protected: prevents client from setting this to true via Livewire wire protocol.
    protected $managerOverrideApproved = false;

    public function mount(): void
    {
        $this->shop = Auth::user()->shop;
    }

    protected function loadStats(): void
    {
        $shopId = Auth::user()->shop_id;

        $this->salesToday = (float) Order::where('shop_id', $shopId)
            ->whereIn('status', ['paid', 'preparing', 'ready', 'completed'])
            ->whereDate('paid_at', today())
            ->sum('total_amount');

        $this->ordersToday = Order::where('shop_id', $shopId)
            ->whereDate('created_at', today())
            ->count();

        $this->unpaidCount = Order::where('shop_id', $shopId)
            ->where('status', 'unpaid')
            ->count();

        $this->readyCount = Order::where('shop_id', $shopId)
            ->where('status', 'ready')
            ->count();
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

    public function markAsPaid($orderId, $method = 'cash')
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

    public function markAsDelivered($orderId)
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

    public function openSplit($orderId)
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

        $order = Order::where('shop_id', Auth::user()->shop_id)
            ->with('items.modifiers')
            ->findOrFail($this->splitOrderId);

        if ($order->status !== 'unpaid') {
            $this->splitError = 'Only unpaid orders can be split.';

            return;
        }

        $moves = [];
        foreach ($order->items as $item) {
            $qty = (int) ($this->splitQuantities[$item->id] ?? 0);
            if ($qty < 0 || $qty > $item->quantity) {
                $this->splitError = 'Split quantities must be between 0 and the item quantity.';

                return;
            }
            if ($qty > 0) {
                $moves[] = ['item' => $item, 'qty' => $qty];
            }
        }

        if (empty($moves)) {
            $this->splitError = 'Select at least one item to split.';

            return;
        }

        DB::transaction(function () use ($order, $moves) {
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
        });

        session()->flash('message', 'Order split successfully.');
        $this->closeSplit();
    }

    public function openPayment($orderId)
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
        $guests = max(1, (int) $this->splitGuestCount);
        $balance = $order->balance_due;
        $base = floor(($balance / $guests) * 100) / 100;
        $rows = [];

        for ($i = 0; $i < $guests; $i++) {
            $rows[] = ['amount' => $base, 'method' => 'card'];
        }

        $remainder = round($balance - ($base * $guests), 2);
        if ($remainder > 0 && isset($rows[0])) {
            $rows[0]['amount'] = round($rows[0]['amount'] + $remainder, 2);
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

        $orders = Order::where('shop_id', Auth::user()->shop_id)
            ->whereIn('status', ['unpaid', 'ready'])
            ->with(['items', 'payments'])
            ->latest()
            ->get();

        return view('livewire.pos-dashboard', [
            'orders' => $orders,
            'splitOrder' => $this->splitOrderId
                ? Order::where('shop_id', Auth::user()->shop_id)->with('items.modifiers')->find($this->splitOrderId)
                : null,
            'paymentOrder' => $this->paymentOrderId
                ? Order::where('shop_id', Auth::user()->shop_id)->with('payments')->find($this->paymentOrderId)
                : null,
        ]);
    }
}
