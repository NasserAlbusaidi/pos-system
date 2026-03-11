<?php

namespace App\Livewire\SuperAdmin;

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
        $shop->delete();
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
