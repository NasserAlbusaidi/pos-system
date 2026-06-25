<?php

namespace App\Livewire\SuperAdmin\Shops;

use App\Models\Shop;
use App\Services\ShopProvisioningService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Manage extends Component
{
    public ?Shop $shop = null;

    public function boot()
    {
        if (! Auth::user()?->is_super_admin) {
            abort(403, 'Unauthorized. Super Admin access required.');
        }
    }

    public $name = '';

    public $slug = '';

    public $status = 'trial';

    // For creating new owner user
    public $ownerName = '';

    public $ownerEmail = '';

    public $ownerPassword = '';

    public function mount($shop = null)
    {
        // Handle Route Model Binding (Edit) or Null (Create)
        if ($shop instanceof Shop && $shop->exists) {
            $this->shop = $shop;
            $this->name = $shop->name;
            $this->slug = $shop->slug;
            $this->status = $shop->status;
        }
    }

    public function updatedName($value)
    {
        if (! $this->shop) {
            $this->slug = Str::slug($value);
        }
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|min:3',
            'slug' => 'required|unique:shops,slug,'.($this->shop->id ?? 'NULL'),
            'status' => 'required|in:active,suspended,trial',
            'ownerName' => $this->shop ? 'nullable' : 'required',
            'ownerEmail' => $this->shop ? 'nullable' : 'required|email|unique:users,email',
            'ownerPassword' => $this->shop ? 'nullable' : 'required|string|min:12|not_in:password',
        ]);

        if ($this->shop) {
            $this->shop->update([
                'name' => $this->name,
                'slug' => $this->slug,
            ]);
            $this->applyLifecycleStatus($this->shop, $this->status);
            $this->shop->save();
        } else {
            app(ShopProvisioningService::class)->provisionOwner(
                name: $this->ownerName,
                email: $this->ownerEmail,
                password: $this->ownerPassword,
                shopName: $this->name,
                slug: $this->slug,
                status: $this->status,
            );
        }

        return redirect()->route('super-admin.shops.index');
    }

    protected function applyLifecycleStatus(Shop $shop, string $status): void
    {
        $branding = is_array($shop->branding) ? $shop->branding : [];

        if ($status === 'trial') {
            $trialEndsAt = $shop->trial_ends_at?->isFuture()
                ? $shop->trial_ends_at
                : now()->addDays(config('billing.trial_days', 14));

            $branding['trial_started_at'] ??= now()->toIso8601String();
            $branding['trial_ends_at'] = $trialEndsAt->toIso8601String();

            $shop->status = 'trial';
            $shop->trial_ends_at = $trialEndsAt;
            $shop->branding = $branding;

            return;
        }

        unset($branding['trial_started_at'], $branding['trial_ends_at']);

        $shop->status = $status;
        $shop->trial_ends_at = null;
        $shop->branding = $branding;
    }

    #[Layout('layouts.super-admin')]
    public function render()
    {
        return view('livewire.super-admin.shops.manage');
    }
}
