<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\BillingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ShopSettings extends Component
{
    // Shop basics
    public $name;

    public $paper;

    public $ink;

    public $accent;

    public $tax_rate = 0;

    // Currency config
    public $currency_code;

    public $currency_symbol;

    public $currency_decimals = 3;

    // Receipt header
    public $receipt_header = '';

    // Default language
    public $language = 'en';

    // WhatsApp notifications
    public $whatsapp_number = '';

    public $whatsapp_notifications_enabled = false;

    // Staff management
    public $staffName = '';

    public $staffEmail = '';

    public $staffRole = 'cashier';

    public $staffPin = '';

    public $editingStaffId = null;

    public function mount()
    {
        $shop = Auth::user()->shop;
        $this->name = $shop->name;

        $branding = $shop->branding ?? [];
        $this->paper = $this->normalizeHex($branding['paper'] ?? '#FDFCF8', '#FDFCF8');
        $this->ink = $this->normalizeHex($branding['ink'] ?? '#1A1918', '#1A1918');
        $this->accent = $this->normalizeHex($branding['accent'] ?? '#CC5500', '#CC5500');
        $this->tax_rate = $shop->tax_rate ?? 0;

        // Currency
        $this->currency_code = $shop->currency_code ?? 'OMR';
        $this->currency_symbol = $shop->currency_symbol ?? 'ر.ع.';
        $this->currency_decimals = $shop->currency_decimals ?? 3;

        // Receipt header from branding JSON
        $this->receipt_header = $branding['receipt_header'] ?? '';

        // Default language
        $this->language = $branding['language'] ?? 'en';

        // WhatsApp
        $this->whatsapp_number = $branding['whatsapp_number'] ?? '';
        $this->whatsapp_notifications_enabled = ! empty($branding['whatsapp_notifications_enabled']);
    }

    protected function normalizeHex(string $value, string $fallback): string
    {
        $hex = ltrim($value, '#');
        if (strlen($hex) === 3 && preg_match('/^[A-Fa-f0-9]{3}$/', $hex)) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (! preg_match('/^[A-Fa-f0-9]{6}$/', $hex)) {
            $hex = ltrim($fallback, '#');
        }

        return '#'.strtolower($hex);
    }

    /**
     * Sanitize color properties on every client-side update to prevent CSS injection.
     * Livewire public properties can be set by the client via wire protocol,
     * so we ensure they always contain valid hex colors before render.
     */
    public function updatedPaper($value)
    {
        $this->paper = $this->normalizeHex((string) $value, '#FDFCF8');
    }

    public function updatedInk($value)
    {
        $this->ink = $this->normalizeHex((string) $value, '#1A1918');
    }

    public function updatedAccent($value)
    {
        $this->accent = $this->normalizeHex((string) $value, '#CC5500');
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|min:3',
            'paper' => ['required', 'regex:/^#(?:[A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/'],
            'ink' => ['required', 'regex:/^#(?:[A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/'],
            'accent' => ['required', 'regex:/^#(?:[A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/'],
            'tax_rate' => 'required|numeric|min:0|max:100',
            'currency_code' => 'required|string|min:1|max:3',
            'currency_symbol' => 'required|string|min:1|max:10',
            'currency_decimals' => 'required|integer|in:0,2,3',
            'receipt_header' => 'nullable|string|max:500',
            'language' => 'required|in:en,ar',
            'whatsapp_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]*$/'],
            'whatsapp_notifications_enabled' => 'boolean',
        ]);

        $shop = Auth::user()->shop;
        $paper = $this->normalizeHex($this->paper, '#FDFCF8');
        $ink = $this->normalizeHex($this->ink, '#1A1918');
        $accent = $this->normalizeHex($this->accent, '#CC5500');
        $branding = $shop->branding ?? [];
        $shop->update([
            'name' => $this->name,
            'tax_rate' => $this->tax_rate,
            'currency_code' => $this->currency_code,
            'currency_symbol' => $this->currency_symbol,
            'currency_decimals' => (int) $this->currency_decimals,
            'branding' => array_merge($branding, [
                'paper' => $paper,
                'ink' => $ink,
                'accent' => $accent,
                'receipt_header' => $this->receipt_header ?? '',
                'language' => $this->language,
                'whatsapp_number' => $this->whatsapp_number ?? '',
                'whatsapp_notifications_enabled' => (bool) $this->whatsapp_notifications_enabled,
            ]),
        ]);

        $this->paper = $paper;
        $this->ink = $ink;
        $this->accent = $accent;

        $this->dispatch('toast', message: 'Shop settings saved.', variant: 'success');
    }

    // --- Staff Management ---

    public function addStaff()
    {
        $allowedRoles = Auth::user()->role === 'admin'
            ? 'manager,cashier,kitchen,server'
            : 'cashier,kitchen,server';

        $this->validate([
            'staffName' => 'required|string|min:2|max:255',
            'staffEmail' => 'required|email|unique:users,email',
            'staffRole' => "required|in:{$allowedRoles}",
            'staffPin' => 'nullable|digits:4',
        ]);

        $shop = Auth::user()->shop;

        // Check plan limits before adding staff.
        $billing = app(BillingService::class);
        if (! $billing->canAccess($shop, 'add_staff')) {
            $limits = $billing->getPlanLimits($shop);
            $limitLabel = $limits['staff_limit'] === 1 ? '1 staff member' : "{$limits['staff_limit']} staff members";
            $this->dispatch('toast',
                message: "Staff limit reached ({$limitLabel} on your current plan). Upgrade to Pro for unlimited staff.",
                variant: 'error'
            );

            return;
        }

        User::forceCreate([
            'shop_id' => $shop->id,
            'name' => $this->staffName,
            'email' => $this->staffEmail,
            'role' => $this->staffRole,
            'pin_code' => $this->staffPin ? Hash::make($this->staffPin) : null,
            'password' => Hash::make(str()->random(16)),
        ]);

        $this->resetStaffForm();
        $this->dispatch('toast', message: 'Staff member added.', variant: 'success');
    }

    public function editStaff($userId)
    {
        $shop = Auth::user()->shop;
        $user = User::where('id', $userId)->where('shop_id', $shop->id)->firstOrFail();

        $this->editingStaffId = $user->id;
        $this->staffName = $user->name;
        $this->staffEmail = $user->email;
        $this->staffRole = $user->role ?? 'cashier';
        $this->staffPin = '';
    }

    public function updateStaff()
    {
        $shop = Auth::user()->shop;
        $user = User::where('id', $this->editingStaffId)->where('shop_id', $shop->id)->firstOrFail();

        $allowedRoles = Auth::user()->role === 'admin'
            ? 'manager,cashier,kitchen,server'
            : 'cashier,kitchen,server';

        $this->validate([
            'staffName' => 'required|string|min:2|max:255',
            'staffEmail' => 'required|email|unique:users,email,'.$user->id,
            'staffRole' => "required|in:{$allowedRoles}",
            'staffPin' => 'nullable|digits:4',
        ]);

        $data = [
            'name' => $this->staffName,
            'email' => $this->staffEmail,
            'role' => $this->staffRole,
        ];

        if ($this->staffPin) {
            $data['pin_code'] = Hash::make($this->staffPin);
        }

        $user->update($data);

        $this->resetStaffForm();
        $this->dispatch('toast', message: 'Staff member updated.', variant: 'success');
    }

    public function removeStaff($userId)
    {
        $shop = Auth::user()->shop;
        $user = User::where('id', $userId)->where('shop_id', $shop->id)->firstOrFail();

        // Prevent removing yourself
        if ($user->id === Auth::id()) {
            $this->dispatch('toast', message: 'You cannot remove yourself.', variant: 'error');

            return;
        }

        $user->delete();
        $this->dispatch('toast', message: 'Staff member removed.', variant: 'success');
    }

    public function cancelEditStaff()
    {
        $this->resetStaffForm();
    }

    protected function resetStaffForm()
    {
        $this->editingStaffId = null;
        $this->staffName = '';
        $this->staffEmail = '';
        $this->staffRole = 'cashier';
        $this->staffPin = '';
        $this->resetValidation();
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        $shop = Auth::user()->shop;
        $staff = User::where('shop_id', $shop->id)->orderBy('name')->get();
        $menuUrl = url('/menu/'.$shop->slug);

        return view('livewire.shop-settings', [
            'shop' => $shop,
            'staff' => $staff,
            'menuUrl' => $menuUrl,
        ]);
    }
}
