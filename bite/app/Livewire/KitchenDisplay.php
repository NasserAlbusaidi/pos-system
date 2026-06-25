<?php

namespace App\Livewire;

use App\Livewire\Concerns\AuthorizesRole;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\ShiftClosure;
use App\Services\OrderPaymentReversalService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

class KitchenDisplay extends Component
{
    use AuthorizesRole;

    public int $lastOrderCount = -1;

    protected function allowedRoles(): array
    {
        return ['kitchen', 'manager', 'admin'];
    }

    public function updateStatus($orderId, $status)
    {
        if (! in_array(Auth::user()->role, ['kitchen', 'manager', 'admin'], true)) {
            abort(403, 'Unauthorized role.');
        }

        $status = (string) $status;
        if (! in_array($status, ['preparing', 'ready'], true)) {
            abort(422, 'Invalid status transition.');
        }

        $order = Order::where('shop_id', Auth::user()->shop_id)
            ->where('id', $orderId)
            ->firstOrFail();

        $allowedTransitions = [
            'paid' => 'preparing',
            'preparing' => 'ready',
        ];

        $expected = $allowedTransitions[$order->status] ?? null;
        if ($expected !== $status) {
            abort(422, 'Invalid status transition.');
        }

        $order->update(['status' => $status]);
        AuditLog::record('order.status_updated', $order, ['status' => $status]);
    }

    public function cancelOrder(int $orderId): void
    {
        if (! in_array(Auth::user()->role, ['manager', 'admin'], true)) {
            abort(403, 'Only managers can cancel orders from KDS.');
        }

        $result = DB::transaction(function () use ($orderId) {
            $order = Order::where('shop_id', Auth::user()->shop_id)
                ->whereIn('status', ['paid', 'preparing'])
                ->lockForUpdate()
                ->findOrFail($orderId);

            $previousStatus = $order->status;
            $shop = $order->shop()->first();

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
                'source' => 'kds',
                'paid_total' => $order->paid_total,
                'unsupported_methods' => $result['unsupported_methods'] ?? [],
                'error' => $result['error'] ?? null,
            ]);

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
            'source' => 'kds',
        ];

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

    #[Layout('layouts.admin')]
    public function render()
    {
        $orders = Order::where('shop_id', Auth::user()->shop_id)
            ->whereIn('status', ['paid', 'preparing'])
            ->with(['items.modifiers', 'payments']) // Eager load item snapshots and payment state for KDS
            ->oldest() // First in, First out
            ->get();

        $currentCount = $orders->count();
        if ($this->lastOrderCount >= 0 && $currentCount > $this->lastOrderCount) {
            $this->dispatch('kds-new-order');
        }
        $this->lastOrderCount = $currentCount;

        return view('livewire.kitchen-display', [
            'orders' => $orders,
        ]);
    }
}
