<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

class KitchenDisplay extends Component
{
    public int $lastOrderCount = -1;

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

        $order = Order::where('shop_id', Auth::user()->shop_id)
            ->whereIn('status', ['paid', 'preparing'])
            ->findOrFail($orderId);

        $previousStatus = $order->status;

        DB::transaction(function () use ($order) {
            $order->update(['status' => 'cancelled']);
        });

        AuditLog::record('order.cancelled', $order, [
            'cancelled_by' => Auth::user()->name,
            'previous_status' => $previousStatus,
            'source' => 'kds',
        ]);
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        $orders = Order::where('shop_id', Auth::user()->shop_id)
            ->whereIn('status', ['paid', 'preparing'])
            ->with('items') // Eager load items for KDS
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
