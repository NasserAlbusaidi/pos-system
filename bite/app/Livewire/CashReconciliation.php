<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

class CashReconciliation extends Component
{
    public Shop $shop;

    public float $expectedCash = 0;

    public ?float $actualCash = null;

    public float $difference = 0;

    public string $notes = '';

    public bool $showResult = false;

    public array $shiftSummary = [];

    public function mount(): void
    {
        $this->shop = Auth::user()->shop;
        $this->loadShiftSummary();
    }

    protected function loadShiftSummary(): void
    {
        $shopId = Auth::user()->shop_id;
        $today = today()->toDateString();

        $ordersQuery = Order::where('shop_id', $shopId)
            ->whereIn('status', ['paid', 'preparing', 'ready', 'completed'])
            ->whereDate('paid_at', $today);

        $totalOrders = (clone $ordersQuery)->count();
        $totalRevenue = (float) (clone $ordersQuery)->sum('total_amount');

        $paymentTotals = Payment::where('shop_id', $shopId)
            ->whereDate('paid_at', $today)
            ->select(
                'method',
                DB::raw('sum(amount) as total'),
                DB::raw('count(*) as count')
            )
            ->groupBy('method')
            ->pluck('total', 'method')
            ->toArray();

        $cashTotal = (float) ($paymentTotals['cash'] ?? 0);
        $cardTotal = (float) ($paymentTotals['card'] ?? 0);

        $this->expectedCash = $cashTotal;

        $this->shiftSummary = [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'cash_total' => $cashTotal,
            'card_total' => $cardTotal,
        ];
    }

    public function reconcile(): void
    {
        $this->validate([
            'actualCash' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->difference = round((float) $this->actualCash - $this->expectedCash, 3);

        AuditLog::record('cash_reconciliation', $this->shop, [
            'date' => today()->toDateString(),
            'expected_cash' => $this->expectedCash,
            'actual_cash' => (float) $this->actualCash,
            'difference' => $this->difference,
            'notes' => $this->notes,
            'shift_summary' => $this->shiftSummary,
        ]);

        $this->showResult = true;

        $this->dispatch('toast',
            message: 'Cash reconciliation recorded.',
            variant: 'success'
        );
    }

    public function closeShift(): void
    {
        $this->dispatch('toast',
            message: 'Shift closed successfully.',
            variant: 'success'
        );

        $this->redirect(route('dashboard'), navigate: true);
    }

    public function resetReconciliation(): void
    {
        $this->actualCash = null;
        $this->difference = 0;
        $this->notes = '';
        $this->showResult = false;
        $this->loadShiftSummary();
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        return view('livewire.cash-reconciliation');
    }
}
