<?php

namespace App\Livewire;

use App\Livewire\Concerns\AuthorizesRole;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ShiftClosure;
use App\Models\Shop;
use App\Support\ShopClock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

class CashReconciliation extends Component
{
    use AuthorizesRole;

    protected function allowedRoles(): array
    {
        return ['manager', 'admin'];
    }

    public Shop $shop;

    public float $expectedCash = 0;

    public ?float $actualCash = null;

    public float $difference = 0;

    public string $notes = '';

    public bool $showResult = false;

    public array $shiftSummary = [];

    public ?string $reconciledSummarySignature = null;

    public function mount(): void
    {
        $this->shop = Auth::user()->shop;
        $this->loadShiftSummary();
    }

    protected function loadShiftSummary(): void
    {
        $shop = $this->shop ?? Auth::user()->shop;
        $shopId = $shop->id;
        [$dayStartUtc, $dayEndUtc] = ShopClock::currentLocalDayUtcRange($shop);

        $ordersQuery = Order::where('shop_id', $shopId)
            ->revenueRecognized()
            ->whereBetween('paid_at', [$dayStartUtc, $dayEndUtc]);

        $totalOrders = (clone $ordersQuery)->count();
        $totalRevenue = (float) (clone $ordersQuery)->sum('total_amount');

        $paymentTotals = Payment::query()
            ->reportableForPaymentSummary($shopId, $dayStartUtc, $dayEndUtc)
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
        $voucherTotal = (float) ($paymentTotals['voucher'] ?? 0);

        $this->expectedCash = $cashTotal;

        $this->shiftSummary = [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'cash_total' => $cashTotal,
            'card_total' => $cardTotal,
            'voucher_total' => $voucherTotal,
        ];
    }

    public function reconcile(): void
    {
        $this->validate([
            'actualCash' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->loadShiftSummary();
        $this->difference = round((float) $this->actualCash - $this->expectedCash, 3);
        $this->reconciledSummarySignature = $this->shiftSummarySignature();

        AuditLog::record('cash_reconciliation', $this->shop, [
            'date' => ShopClock::localDate($this->shop),
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
        if (! $this->showResult || $this->reconciledSummarySignature === null) {
            $this->dispatch('toast',
                message: 'Record cash reconciliation before closing the shift.',
                variant: 'error'
            );

            return;
        }

        $this->loadShiftSummary();

        if ($this->shiftSummarySignature() !== $this->reconciledSummarySignature) {
            $this->showResult = false;
            $this->dispatch('toast',
                message: 'Shift totals changed. Reconcile cash again before closing the shift.',
                variant: 'error'
            );

            return;
        }

        $businessDate = ShiftClosure::businessDateFor($this->shop);

        if (ShiftClosure::where('shop_id', $this->shop->id)->where('business_date', $businessDate)->exists()) {
            $this->dispatch('toast',
                message: 'Shift is already closed for today.',
                variant: 'error'
            );

            return;
        }

        DB::transaction(function () use ($businessDate) {
            ShiftClosure::create([
                'shop_id' => $this->shop->id,
                'business_date' => $businessDate,
                'closed_by' => Auth::id(),
                'expected_cash' => $this->expectedCash,
                'actual_cash' => (float) $this->actualCash,
                'difference' => $this->difference,
                'notes' => trim($this->notes) === '' ? null : $this->notes,
                'shift_summary' => $this->shiftSummary,
                'closed_at' => now(),
            ]);

            AuditLog::record('shift.closed', $this->shop, [
                'date' => $businessDate,
                'expected_cash' => $this->expectedCash,
                'actual_cash' => (float) $this->actualCash,
                'difference' => $this->difference,
                'notes' => $this->notes,
                'shift_summary' => $this->shiftSummary,
            ]);
        });

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
        $this->reconciledSummarySignature = null;
        $this->loadShiftSummary();
    }

    protected function shiftSummarySignature(): string
    {
        $summary = $this->shiftSummary;
        ksort($summary);

        return md5(json_encode($summary, JSON_PRESERVE_ZERO_FRACTION));
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        return view('livewire.cash-reconciliation');
    }
}
