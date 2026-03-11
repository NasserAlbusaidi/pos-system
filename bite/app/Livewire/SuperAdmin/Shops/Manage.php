<?php

namespace App\Livewire\SuperAdmin\Shops;

use App\Models\Shop;
use App\Models\User;
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

    public $status = 'active';

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
            'ownerPassword' => $this->shop ? 'nullable' : 'required|min:8',
        ]);

        if ($this->shop) {
            $this->shop->update([
                'name' => $this->name,
                'slug' => $this->slug,
            ]);
            $this->shop->status = $this->status;
            $this->shop->save();
        } else {
            $shop = Shop::create([
                'name' => $this->name,
                'slug' => $this->slug,
                'branding' => null,
            ]);
            $shop->status = $this->status;
            $shop->save();

            // Create Owner
            User::forceCreate([
                'shop_id' => $shop->id,
                'name' => $this->ownerName,
                'email' => $this->ownerEmail,
                'password' => bcrypt($this->ownerPassword),
                'role' => 'admin',
            ]);
        }

        return redirect()->route('super-admin.shops.index');
    }

    #[Layout('layouts.super-admin')]
    public function render()
    {
        return view('livewire.super-admin.shops.manage');
    }
}
