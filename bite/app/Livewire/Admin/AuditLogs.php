<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class AuditLogs extends Component
{
    public $search = '';

    public $userFilter = '';

    public $logFilter = 'all';

    private const FILTER_PREFIXES = [
        'orders' => ['order.', 'orders.', 'payment.'],
        'products' => ['product.', 'category.', 'modifier.'],
        'operations' => ['cash_reconciliation', 'loyalty.'],
        'auth' => ['login.', 'pin.', 'impersonation.'],
    ];

    #[Layout('layouts.admin')]
    public function render()
    {
        $shopId = Auth::user()->shop_id;

        $query = AuditLog::where('shop_id', $shopId)
            ->when($this->search, fn ($q) => $q->where('action', 'like', '%'.$this->search.'%'))
            ->when($this->userFilter, fn ($q) => $q->where('user_id', $this->userFilter));

        if ($this->logFilter !== 'all' && isset(self::FILTER_PREFIXES[$this->logFilter])) {
            $prefixes = self::FILTER_PREFIXES[$this->logFilter];
            $query->where(function ($q) use ($prefixes) {
                foreach ($prefixes as $prefix) {
                    $q->orWhere('action', 'like', $prefix.'%');
                }
            });
        }

        $logs = $query->with('user')
            ->latest()
            ->limit(200)
            ->get();

        $users = User::where('shop_id', $shopId)->orderBy('name')->get();

        $filterCounts = $this->getFilterCounts($shopId);

        return view('livewire.admin.audit-logs', [
            'logs' => $logs,
            'users' => $users,
            'filterCounts' => $filterCounts,
        ]);
    }

    private function getFilterCounts(int $shopId): array
    {
        $counts = ['all' => AuditLog::where('shop_id', $shopId)->count()];

        foreach (self::FILTER_PREFIXES as $key => $prefixes) {
            $query = AuditLog::where('shop_id', $shopId)
                ->where(function ($q) use ($prefixes) {
                    foreach ($prefixes as $prefix) {
                        $q->orWhere('action', 'like', $prefix.'%');
                    }
                });
            $counts[$key] = $query->count();
        }

        return $counts;
    }
}
