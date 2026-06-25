<?php

namespace App\Livewire\SuperAdmin;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Dashboard extends Component
{
    public function boot()
    {
        if (! Auth::user()?->is_super_admin) {
            abort(403, 'Unauthorized. Super Admin access required.');
        }
    }

    public function toggleStatus($shopId)
    {
        $shop = Shop::findOrFail($shopId);
        $shop->status = $shop->status === 'active' ? 'suspended' : 'active';
        $shop->save();
    }

    public function deleteShop($shopId)
    {
        $shop = Shop::findOrFail($shopId);

        if ($shop->orders()->exists() || Payment::where('shop_id', $shop->id)->exists()) {
            $shop->status = 'suspended';
            $shop->save();

            AuditLog::record('shop.delete_blocked', $shop, [
                'reason' => 'financial_history',
                'orders_count' => $shop->orders()->count(),
            ]);

            $this->dispatch(
                'toast',
                message: 'Shop has financial history and was suspended instead of deleted.',
                variant: 'error',
            );

            return;
        }

        $shop->delete();

        $this->dispatch('toast', message: 'Shop deleted.', variant: 'success');
    }

    #[Layout('layouts.super-admin')]
    public function render()
    {
        return view('livewire.super-admin.dashboard', [
            'shops' => Shop::withCount('products', 'orders')->get(),
            'totalShops' => Shop::count(),
            'activeShops' => Shop::where('status', 'active')->count(),
        ]);
    }
}
