<?php

namespace App\Livewire;

use App\Livewire\Concerns\AuthorizesRole;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemModifier;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ShiftClosure;
use App\Models\Shop;
use App\Models\User;
use App\Services\GuestOrderService;
use App\Services\LoyaltyService;
use App\Services\OrderPaymentReversalService;
use App\Services\PrintNodeService;
use App\Support\ShopClock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

class PosDashboard extends Component
{
    use AuthorizesRole;

    protected function allowedRoles(): array
    {
        return ['server', 'manager', 'admin'];
    }

    private const MANAGER_OVERRIDE_ACTIONS = [
        'clearOldOrders',
        'systemReset',
        'cancelOrder',
    ];

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

    protected ?array $managerOverrideApprover = null;

    public array $upsellSuggestions = [];

    // Walk-in / counter order entry (#56).
    public bool $showNewOrder = false;

    public array $posCart = [];

    public string $newOrderName = '';

    public ?string $newOrderError = null;

    public ?int $customizingPosProductId = null;

    public array $posSelectedModifiers = [];

    public ?string $posModifierError = null;

    // Set when the builder opens; makes a double-clicked charge idempotent.
    public string $newOrderKey = '';

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

    /**
     * True while any modal is open. The view gates wire:poll on this so a
     * background poll can't race a user action and morph an open modal away
     * (the "New Sale popup is consumed, must refresh" bug). Polling resumes
     * the moment the modal closes.
     */
    #[Computed]
    public function hasOpenModal(): bool
    {
        return $this->showNewOrder
            || $this->splitOrderId !== null
            || $this->paymentOrderId !== null
            || $this->showManagerModal;
    }

    public function openNewOrder(): void
    {
        $this->reset([
            'posCart',
            'newOrderName',
            'newOrderError',
            'customizingPosProductId',
            'posSelectedModifiers',
            'posModifierError',
        ]);
        $this->newOrderKey = (string) Str::uuid();
        $this->showNewOrder = true;
    }

    public function closeNewOrder(): void
    {
        $this->reset([
            'showNewOrder',
            'posCart',
            'newOrderName',
            'newOrderError',
            'customizingPosProductId',
            'posSelectedModifiers',
            'posModifierError',
            'newOrderKey',
        ]);
    }

    public function addToCart(int $productId): void
    {
        // Shop-scoped, orderable lookup — never trust the client for price or
        // for whether the product belongs to this tenant.
        $product = $this->shop->products()
            ->with('modifierGroups.options')
            ->orderable()
            ->find($productId);
        if (! $product) {
            return;
        }

        if ($product->modifierGroups->isNotEmpty()) {
            $this->customizingPosProductId = $product->id;
            $this->posSelectedModifiers = [];
            $this->posModifierError = null;

            return;
        }

        $this->addProductToPosCart($product);
    }

    public function selectPosModifier(int $groupId, int $optionId, bool $isMultiple = false): void
    {
        $optionIdStr = (string) $optionId;

        if ($isMultiple) {
            $current = $this->posSelectedModifiers[$groupId] ?? [];
            if (! is_array($current)) {
                $current = [$current];
            }

            if (in_array($optionIdStr, $current, true)) {
                $current = array_values(array_filter($current, fn ($id) => $id !== $optionIdStr));
            } else {
                $current[] = $optionIdStr;
            }

            $this->posSelectedModifiers[$groupId] = $current;
        } else {
            $this->posSelectedModifiers[$groupId] = $optionIdStr;
        }

        $this->posModifierError = null;
    }

    public function confirmPosModifierSelection(): void
    {
        if (! $this->customizingPosProductId) {
            return;
        }

        $product = $this->shop->products()
            ->with('modifierGroups.options')
            ->orderable()
            ->find($this->customizingPosProductId);

        if (! $product) {
            $this->closePosModifierPicker();

            return;
        }

        $orderService = app(GuestOrderService::class);
        $selectedByGroup = $orderService->normalizeModifierGroups($this->posSelectedModifiers);
        $modifierError = $orderService->validateSelectedModifierGroups($product, $selectedByGroup);

        if ($modifierError) {
            $this->posModifierError = $modifierError;

            return;
        }

        $this->addProductToPosCart(
            $product,
            $orderService->normalizeModifierIds($this->posSelectedModifiers)
        );
        $this->closePosModifierPicker();
    }

    public function closePosModifierPicker(): void
    {
        $this->customizingPosProductId = null;
        $this->posSelectedModifiers = [];
        $this->posModifierError = null;
    }

    protected function addProductToPosCart(Product $product, array $modifierIds = []): void
    {
        $orderService = app(GuestOrderService::class);
        $modifierIds = $orderService->normalizeModifierIds($modifierIds);
        $validOptions = $orderService->getValidModifierOptions($product, $modifierIds);
        $pricingRules = $orderService->loadActivePricingRules($this->shop);
        $basePrice = $product->getTimePriced($pricingRules);
        $modifierIds = $validOptions
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $cartKey = $this->posCartKey($product->id, $modifierIds);
        $maxQty = (int) config('ordering.max_quantity_per_line', 99);
        $current = (int) ($this->posCart[$cartKey]['quantity'] ?? 0);
        if ($current >= $maxQty) {
            return;
        }

        $this->posCart[$cartKey] = [
            'key' => $cartKey,
            'id' => $product->id,
            'name' => $product->name_en,
            'price' => max(0.0, round((float) $basePrice + (float) $validOptions->sum('price_adjustment'), 3)),
            'quantity' => $current + 1,
            'selectedModifiers' => $modifierIds,
            'modifierNames' => $validOptions
                ->map(fn ($option) => $option->name_en)
                ->values()
                ->all(),
        ];
        $this->newOrderError = null;
    }

    protected function posCartKey(int $productId, array $modifierIds = []): string
    {
        $modifierKey = collect($modifierIds)
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->implode('-');

        return $modifierKey === '' ? (string) $productId : $productId.':'.$modifierKey;
    }

    public function incrementCartItem(string $cartKey): void
    {
        if (! isset($this->posCart[$cartKey])) {
            return;
        }

        $maxQty = (int) config('ordering.max_quantity_per_line', 99);
        if ((int) $this->posCart[$cartKey]['quantity'] >= $maxQty) {
            return;
        }

        $this->posCart[$cartKey]['quantity']++;
        $this->newOrderError = null;
    }

    public function decrementCartItem(string $cartKey): void
    {
        if (! isset($this->posCart[$cartKey])) {
            return;
        }

        $next = (int) $this->posCart[$cartKey]['quantity'] - 1;
        if ($next < 1) {
            unset($this->posCart[$cartKey]);

            return;
        }

        $this->posCart[$cartKey]['quantity'] = $next;
    }

    public function removeCartItem(string $cartKey): void
    {
        unset($this->posCart[$cartKey]);
    }

    public function chargeNewOrder(string $method = 'cash')
    {
        $method = trim($method);
        if (! in_array($method, ['cash', 'card'], true)) {
            $this->newOrderError = 'Choose a valid payment method.';

            return;
        }

        if ($message = $this->shiftClosedPaymentMessage()) {
            $this->newOrderError = $message;

            return;
        }

        $cart = collect($this->posCart)
            ->map(fn ($row) => [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'quantity' => (int) $row['quantity'],
                'selectedModifiers' => $row['selectedModifiers'] ?? [],
            ])
            ->values()
            ->all();

        if (empty($cart)) {
            $this->newOrderError = 'Add at least one item.';

            return;
        }

        $result = app(GuestOrderService::class)->createForCounter($this->shop, $cart, [
            'customer_name' => $this->newOrderName,
            'idempotency_key' => $this->newOrderKey,
        ]);

        switch ($result['outcome']) {
            case 'invalid':
                $this->newOrderError = $result['error'];

                return;

            case 'unavailable':
                foreach ($result['unavailable_ids'] as $id) {
                    foreach ($this->posCart as $key => $row) {
                        if ((int) ($row['id'] ?? 0) === (int) $id) {
                            unset($this->posCart[$key]);
                        }
                    }
                }
                $this->newOrderError = 'No longer available: '.implode(', ', $result['unavailable']);

                return;

            case 'created':
            case 'duplicate':
            case 'raced':
                // Counter sale is pay-now: settle the full balance immediately so
                // the order never sits unpaid (and isn't auto-cancelled). markAsPaid
                // is idempotent on an already-paid order, so a replayed charge is safe.
                $this->markAsPaid($result['order']->id, $method);
                session()->flash('message', 'Order created and paid.');
                $this->closeNewOrder();

                return;

            default: // 'empty' or anything unexpected
                $this->newOrderError = 'Add at least one item.';
        }
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
        $shop = $this->shop ?? Auth::user()->shop;
        $shopId = $shop->id;
        [$todayStartUtc, $todayEndUtc] = ShopClock::currentLocalDayUtcRange($shop);

        $stats = DB::query()
            ->from('orders')
            ->where('shop_id', $shopId)
            ->selectRaw(implode(', ', [
                'COALESCE(SUM(CASE WHEN status IN (?, ?, ?, ?) AND paid_at BETWEEN ? AND ? THEN total_amount ELSE 0 END), 0) AS sales_today',
                'COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) AS orders_today',
                'COUNT(CASE WHEN status = ? THEN 1 END) AS unpaid_count',
                'COUNT(CASE WHEN status = ? THEN 1 END) AS ready_count',
            ]), ['paid', 'preparing', 'ready', 'completed', $todayStartUtc, $todayEndUtc, $todayStartUtc, $todayEndUtc, 'unpaid', 'ready'])
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

        $cancelled = $this->cancelUnpaidOrdersForCleanup(
            $shopId,
            fn ($query) => $query
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', now())
        );

        $completed = $this->completeReadyOrdersForCleanup(
            $shopId,
            fn ($query) => $query->where('updated_at', '<', now()->subMinutes(30))
        );

        AuditLog::record('orders.cleared', null, array_merge([
            'shop_id' => $shopId,
        ], $cancelled, $completed, $this->managerOverrideAuditMeta()));
        session()->flash('message', 'Old orders cleared.');
    }

    public function systemReset()
    {
        if ($this->requiresManagerOverride()) {
            $this->requestManagerOverride('systemReset');

            return;
        }

        $shopId = Auth::user()->shop_id;

        $cancelled = $this->cancelUnpaidOrdersForCleanup($shopId);

        $completed = $this->completeReadyOrdersForCleanup($shopId);

        AuditLog::record('orders.system_reset', null, array_merge([
            'shop_id' => $shopId,
        ], $cancelled, $completed, $this->managerOverrideAuditMeta()));
        session()->flash('message', 'System reset complete.');
    }

    public function cancelOrder(int $orderId): void
    {
        if ($this->requiresManagerOverride()) {
            $this->requestManagerOverride('cancelOrder', ['orderId' => $orderId]);

            return;
        }

        $result = DB::transaction(function () use ($orderId) {
            $order = Order::where('shop_id', Auth::user()->shop_id)
                ->whereIn('status', ['unpaid', 'paid', 'preparing', 'ready'])
                ->lockForUpdate()
                ->findOrFail($orderId);

            $previousStatus = $order->status;
            $shop = isset($this->shop) && $this->shop->id === $order->shop_id
                ? $this->shop
                : $order->shop()->first();

            if (! $order->canCancelWithoutPaymentReversal() && $shop && ShiftClosure::isClosedFor($shop)) {
                return [
                    'cancelled' => false,
                    'order' => $order,
                    'previous_status' => $previousStatus,
                    'reason' => 'shift_closed',
                ];
            }

            if ($order->trustedPaymentsQuery()->exists()) {
                return app(OrderPaymentReversalService::class)
                    ->reverseLocalPaymentsAndCancel($order, Auth::user());
            }

            if (! $order->canCancelWithoutPaymentReversal()) {
                return [
                    'cancelled' => false,
                    'order' => $order,
                    'previous_status' => $previousStatus,
                ];
            }

            $order->update(['status' => 'cancelled']);

            return [
                'cancelled' => true,
                'refunded' => false,
                'order' => $order->fresh(),
                'previous_status' => $previousStatus,
            ];
        });

        /** @var \App\Models\Order $order */
        $order = $result['order'];
        $previousStatus = $result['previous_status'];

        if (! $result['cancelled']) {
            AuditLog::record('order.cancel_rejected', $order, [
                'cancelled_by' => Auth::user()->name,
                'previous_status' => $previousStatus,
                'reason' => $result['reason'] ?? 'payment_reversal_required',
                'paid_total' => $order->paid_total,
                'unsupported_methods' => $result['unsupported_methods'] ?? [],
                'error' => $result['error'] ?? null,
            ] + $this->managerOverrideAuditMeta());

            $this->dispatch('toast',
                message: $this->cancelRejectedMessage((string) ($result['reason'] ?? 'payment_reversal_required'), $order),
                variant: 'error'
            );

            return;
        }

        $action = ($result['refunded'] ?? false) ? 'order.refund_voided' : 'order.cancelled';
        $meta = [
            'cancelled_by' => Auth::user()->name,
            'previous_status' => $previousStatus,
        ] + $this->managerOverrideAuditMeta();

        if ($result['refunded'] ?? false) {
            $meta['refund_total'] = $result['refund_total'] ?? 0;
            $meta['refund_rows'] = $result['refund_rows'] ?? [];
        }

        AuditLog::record($action, $order, $meta);

        $this->dispatch('toast',
            message: ($result['refunded'] ?? false)
                ? __('admin.order_refund_voided_message', ['id' => $order->id])
                : __('admin.order_cancelled_message', ['id' => $order->id]),
            variant: 'success'
        );
    }

    protected function cancelRejectedMessage(string $reason, Order $order): string
    {
        if ($reason === 'external_refund_required') {
            return __('admin.order_cancel_external_refund_required', ['id' => $order->id]);
        }

        if ($reason === 'stripe_refund_failed') {
            return __('admin.order_cancel_stripe_refund_failed', ['id' => $order->id]);
        }

        if ($reason === 'shift_closed') {
            return ShiftClosure::PAYMENT_LOCK_MESSAGE;
        }

        return __('admin.order_cancel_requires_reversal', ['id' => $order->id]);
    }

    protected function cancelUnpaidOrdersForCleanup(int $shopId, ?callable $scope = null): array
    {
        return DB::transaction(function () use ($shopId, $scope): array {
            $query = Order::where('shop_id', $shopId)
                ->where('status', 'unpaid')
                ->whereDoesntHave('payments')
                ->orderBy('id')
                ->lockForUpdate();

            if ($scope) {
                $scope($query);
            }

            $orderIds = $query->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            if ($orderIds !== []) {
                Order::where('shop_id', $shopId)
                    ->whereIn('id', $orderIds)
                    ->update(['status' => 'cancelled']);
            }

            return [
                'cancelled_unpaid_count' => count($orderIds),
                'cancelled_unpaid_order_ids' => $orderIds,
            ];
        });
    }

    protected function completeReadyOrdersForCleanup(int $shopId, ?callable $scope = null): array
    {
        return DB::transaction(function () use ($shopId, $scope): array {
            $query = Order::where('shop_id', $shopId)
                ->where('status', 'ready')
                ->orderBy('id')
                ->lockForUpdate();

            if ($scope) {
                $scope($query);
            }

            $orders = $query->get();
            $orderIds = [];

            foreach ($orders as $order) {
                $order->update([
                    'status' => 'completed',
                    'fulfilled_at' => $order->fulfilled_at ?: now(),
                ]);
                $orderIds[] = (int) $order->id;
            }

            return [
                'completed_ready_count' => count($orderIds),
                'completed_ready_order_ids' => $orderIds,
            ];
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
        abort_unless(in_array($action, self::MANAGER_OVERRIDE_ACTIONS, true), 404);

        $this->pendingAction = $action;
        $this->pendingPayload = $payload;
        session()->put($this->managerOverrideSessionKey(), [
            'action' => $action,
            'payload' => $payload,
        ]);
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
        $this->managerOverrideApprover = [
            'manager_approved_by_id' => $manager->id,
            'manager_approved_by_name' => $manager->name,
            'manager_approved_by_role' => $manager->role,
        ];

        ['action' => $action, 'payload' => $payload] = $this->pendingManagerOverride();
        $this->pendingAction = null;
        $this->pendingPayload = [];
        $this->forgetPendingManagerOverride();

        try {
            if ($action === 'clearOldOrders') {
                $this->clearOldOrders();

                return;
            }

            if ($action === 'systemReset') {
                $this->systemReset();

                return;
            }

            if ($action === 'cancelOrder') {
                $this->cancelOrder((int) ($payload['orderId'] ?? 0));

                return;
            }
        } finally {
            $this->managerOverrideApproved = false;
            $this->managerOverrideApprover = null;
        }
    }

    public function cancelManagerOverride()
    {
        $this->reset(['showManagerModal', 'managerPin', 'managerError']);
        $this->pendingAction = null;
        $this->pendingPayload = [];
        $this->managerOverrideApproved = false;
        $this->managerOverrideApprover = null;
        $this->forgetPendingManagerOverride();
    }

    protected function managerOverrideThrottleKey(): string
    {
        return 'manager-override:'.Auth::user()->shop_id.'|'.request()->ip();
    }

    protected function managerOverrideSessionKey(): string
    {
        return 'pos-manager-override:'.Auth::id().':'.Auth::user()->shop_id;
    }

    protected function pendingManagerOverride(): array
    {
        $pending = session()->get($this->managerOverrideSessionKey(), [
            'action' => $this->pendingAction,
            'payload' => $this->pendingPayload,
        ]);

        $action = is_string($pending['action'] ?? null) ? $pending['action'] : null;
        $payload = is_array($pending['payload'] ?? null) ? $pending['payload'] : [];

        return compact('action', 'payload');
    }

    protected function forgetPendingManagerOverride(): void
    {
        session()->forget($this->managerOverrideSessionKey());
    }

    protected function managerOverrideAuditMeta(): array
    {
        return $this->managerOverrideApprover ?? [];
    }

    public function markAsPaid(int $orderId, string $method = 'cash')
    {
        $this->paymentError = null;

        $allowedMethods = ['cash', 'card', 'voucher'];
        $method = trim($method);
        if (! in_array($method, $allowedMethods, true)) {
            $this->paymentError = 'Choose a valid payment method.';
            $this->dispatch('toast', message: $this->paymentError, variant: 'error');

            return;
        }

        DB::transaction(function () use ($orderId, $method) {
            $order = Order::where('shop_id', Auth::user()->shop_id)
                ->where('id', $orderId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($message = $this->paymentRejectionMessage($order)) {
                $this->paymentError = $message;

                return;
            }

            if ($order->balance_due <= 0) {
                return;
            }

            $this->recordPaymentsForOrder($order, [
                ['amount' => $order->balance_due, 'method' => $method],
            ]);
        });

        if ($this->paymentError) {
            $this->dispatch('toast', message: $this->paymentError, variant: 'error');
        }
    }

    protected function recordPaymentsForOrder(Order $order, array $rows): void
    {
        $allowedMethods = ['cash', 'card', 'voucher'];

        if ($message = $this->paymentRejectionMessage($order)) {
            $this->paymentError = $message;

            return;
        }

        $hasInvalidAmount = false;
        $hasInvalidMethod = false;
        $rows = collect($rows)
            ->map(function ($row) use ($allowedMethods, &$hasInvalidAmount, &$hasInvalidMethod) {
                $amount = $this->normalizePaymentAmount($row['amount'] ?? null);
                if ($amount === null) {
                    $hasInvalidAmount = true;
                    $amount = 0;
                }

                $method = trim((string) ($row['method'] ?? ''));
                if (! in_array($method, $allowedMethods, true)) {
                    $hasInvalidMethod = true;
                }

                return [
                    'amount' => $amount,
                    'method' => $method,
                ];
            })
            ->filter(fn ($row) => $row['amount'] > 0)
            ->values()
            ->all();

        if ($hasInvalidAmount) {
            $this->paymentError = 'Enter valid payment amounts.';

            return;
        }

        if ($hasInvalidMethod) {
            $this->paymentError = 'Choose a valid payment method.';

            return;
        }

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
                'payment_method' => $order->paymentSummaryMethod() ?? $rows[0]['method'],
                'paid_at' => now(),
            ]);

            AuditLog::record('order.paid', $order, [
                'payment_method' => $order->payment_method,
                'total' => $order->total_amount,
            ]);
            app(LoyaltyService::class)->award($order);
            $this->printOrderSafely($order, 'kitchen');
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
        $this->printOrderSafely($order, 'receipt');
    }

    protected function printOrderSafely(Order $order, string $type): void
    {
        try {
            app(PrintNodeService::class)->printOrder($order, $type);
        } catch (Throwable $e) {
            Log::warning('Order print failed after POS state change.', [
                'order_id' => $order->id,
                'shop_id' => $order->shop_id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
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
                'source' => $order->source ?? 'guest',
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
                    'note' => $item->note,
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

            $newTax = $baseSubtotal > 0 ? round($originalTax * ($newSubtotal / $baseSubtotal), 3) : 0;
            $orderTax = round($originalTax - $newTax, 3);

            $order->update([
                'subtotal_amount' => $orderSubtotal,
                'tax_amount' => $orderTax,
                'total_amount' => round($orderSubtotal + $orderTax, 3),
            ]);
            $newOrder->update([
                'subtotal_amount' => $newSubtotal,
                'tax_amount' => $newTax,
                'total_amount' => round($newSubtotal + $newTax, 3),
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
        $amount = $this->normalizePaymentAmount($this->splitAmount);
        if ($amount === null || $amount <= 0 || $amount > $order->balance_due) {
            $this->paymentError = 'Enter a valid amount up to the remaining balance.';

            return;
        }

        $decimals = (int) ($this->shop->currency_decimals ?? 3);
        $remainder = round($order->balance_due - $amount, $decimals);

        $this->paymentRows = [
            ['amount' => $amount, 'method' => 'card'],
        ];

        if ($remainder > 0) {
            $this->paymentRows[] = ['amount' => $remainder, 'method' => 'cash'];
        }
    }

    public function applyPayments()
    {
        if (! $this->paymentOrderId) {
            return;
        }

        $allowedMethods = ['cash', 'card', 'voucher'];
        $hasInvalidAmount = false;
        $hasInvalidMethod = false;
        $rows = collect($this->paymentRows)->map(function ($row) use ($allowedMethods, &$hasInvalidAmount, &$hasInvalidMethod) {
            $amount = $this->normalizePaymentAmount($row['amount'] ?? null);
            if ($amount === null) {
                $hasInvalidAmount = true;
                $amount = 0;
            }

            $method = trim((string) ($row['method'] ?? ''));
            if (! in_array($method, $allowedMethods, true)) {
                $hasInvalidMethod = true;
            }

            return [
                'amount' => $amount,
                'method' => $method,
            ];
        })->filter(fn ($row) => $row['amount'] > 0)->values()->all();

        if ($hasInvalidAmount) {
            $this->paymentError = 'Enter valid payment amounts.';

            return;
        }

        if ($hasInvalidMethod) {
            $this->paymentError = 'Choose a valid payment method.';

            return;
        }

        if (empty($rows)) {
            $this->paymentError = 'Add at least one payment.';

            return;
        }

        $result = DB::transaction(function () use ($rows) {
            $order = Order::where('shop_id', Auth::user()->shop_id)
                ->where('id', $this->paymentOrderId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($message = $this->paymentRejectionMessage($order)) {
                return ['error' => $message];
            }

            $sum = round(collect($rows)->sum('amount'), 3);
            $balance = round($order->balance_due, 3);
            $tolerance = 0.0005;

            // Pilot policy (#57): a settlement must cover the full balance, so no
            // order is ever left part-paid and 'unpaid' forever. Split tenders are
            // fine as long as they sum to the balance.
            if ($sum + $tolerance < $balance) {
                return ['error' => 'Payment must cover the full balance ('.formatPrice($balance, $this->shop).' due).'];
            }

            if ($sum > $balance + $tolerance) {
                return ['error' => 'Payments cannot exceed the remaining balance.'];
            }

            $this->recordPaymentsForOrder($order, $rows);

            return ['error' => null];
        });

        if ($result['error']) {
            $this->paymentError = $result['error'];

            return;
        }

        session()->flash('message', 'Order paid.');
        $this->closePayment();
    }

    protected function paymentRejectionMessage(Order $order): ?string
    {
        if ($order->status !== 'unpaid') {
            return 'Only unpaid orders can accept payments.';
        }

        if ($order->expires_at && $order->expires_at->isPast()) {
            $order->update(['status' => 'cancelled']);

            return 'This order has expired and cannot be paid.';
        }

        $shop = isset($this->shop) && $this->shop->id === $order->shop_id
            ? $this->shop
            : $order->shop()->first();

        if ($shop && $message = $this->shiftClosedPaymentMessage($shop)) {
            return $message;
        }

        return null;
    }

    protected function shiftClosedPaymentMessage(?Shop $shop = null): ?string
    {
        $shop ??= isset($this->shop) ? $this->shop : Auth::user()?->shop;

        if (! $shop) {
            return null;
        }

        return ShiftClosure::isClosedFor($shop)
            ? ShiftClosure::PAYMENT_LOCK_MESSAGE
            : null;
    }

    protected function normalizePaymentAmount(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            $amount = (float) $value;

            return is_finite($amount) ? round($amount, 3) : null;
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if (! preg_match('/^\d+(?:\.\d{1,3})?$/', $value)) {
            return null;
        }

        return round((float) $value, 3);
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        $this->loadStats();

        $shopId = Auth::user()->shop_id;

        $orders = Order::where('shop_id', $shopId)
            ->whereIn('status', ['unpaid', 'paid', 'preparing', 'ready'])
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

        $orderService = app(GuestOrderService::class);
        $activePricingRules = $orderService->loadActivePricingRules($this->shop);
        $posCustomizingProduct = $this->customizingPosProductId
            ? $this->shop->products()
                ->with('modifierGroups.options')
                ->orderable()
                ->find($this->customizingPosProductId)
            : null;
        $posCustomizingProductPrice = 0.0;
        if ($posCustomizingProduct) {
            $modifierIds = $orderService->normalizeModifierIds($this->posSelectedModifiers);
            $posCustomizingProductPrice = max(
                0.0,
                round((float) $posCustomizingProduct->getTimePriced($activePricingRules) + $orderService->sumModifierPrices($posCustomizingProduct, $modifierIds), 3)
            );
        }

        return view('livewire.pos-dashboard', [
            'orders' => $orders,
            'menuCategories' => $menuCategories,
            'activePricingRules' => $activePricingRules,
            'posCustomizingProduct' => $posCustomizingProduct,
            'posCustomizingProductPrice' => $posCustomizingProductPrice,
            'splitOrder' => $this->splitOrderId
                ? Order::where('shop_id', $shopId)->with('items.modifiers')->find($this->splitOrderId)
                : null,
            'paymentOrder' => $this->paymentOrderId
                ? Order::where('shop_id', $shopId)->with('payments')->find($this->paymentOrderId)
                : null,
        ]);
    }
}
