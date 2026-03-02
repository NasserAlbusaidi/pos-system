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

    #[Layout('layouts.admin')]
    public function render()
    {
        $shopId = Auth::user()->shop_id;

        $logs = AuditLog::where('shop_id', $shopId)
            ->when($this->search, fn ($query) => $query->where('action', 'like', '%'.$this->search.'%'))
            ->when($this->userFilter, fn ($query) => $query->where('user_id', $this->userFilter))
            ->with('user')
            ->latest()
            ->limit(200)
            ->get();

        $users = User::where('shop_id', $shopId)->orderBy('name')->get();

        return view('livewire.admin.audit-logs', [
            'logs' => $logs,
            'users' => $users,
        ]);
    }
}
