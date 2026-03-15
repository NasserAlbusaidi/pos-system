<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DemoMenuSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

class OnboardingWizard extends Component
{
    // ── Wizard state ────────────────────────────────────────
    public int $step = 1;

    public int $totalSteps = 5;

    // ── Step 1: Welcome ─────────────────────────────────────
    public string $shopName = '';

    // ── Step 2: Shop Profile ────────────────────────────────
    public string $currency_code = 'OMR';

    public string $currency_symbol = 'ر.ع.';

    public int $currency_decimals = 3;

    public float $tax_rate = 0;

    public string $accent = '#CC5500';

    public string $paper = '#FDFCF8';

    public string $ink = '#1A1918';

    // ── Step 3: First Menu Items ────────────────────────────
    public array $menuItems = [];

    // ── Step 4: Staff PINs ──────────────────────────────────
    public array $staffMembers = [];

    public string $staffName = '';

    public string $staffEmail = '';

    public string $staffRole = 'cashier';

    public string $staffPin = '';

    // ── Step 5: Done ────────────────────────────────────────
    public bool $demoMenuLoaded = false;

    public function mount()
    {
        $user = $this->onboardingUser();
        $shop = $user->shop;

        // If onboarding is already completed, redirect to dashboard
        if ($user->hasCompletedOnboarding()) {
            $this->redirect(route('dashboard'), navigate: true);

            return;
        }
        $branding = $shop->branding ?? [];

        $this->shopName = $shop->name;
        $this->currency_code = $shop->currency_code ?? 'OMR';
        $this->currency_symbol = $shop->currency_symbol ?? 'ر.ع.';
        $this->currency_decimals = $shop->currency_decimals ?? 3;
        $this->tax_rate = (float) ($shop->tax_rate ?? 0);
        $this->accent = $this->normalizeHex($branding['accent'] ?? '#CC5500', '#CC5500');
        $this->paper = $this->normalizeHex($branding['paper'] ?? '#FDFCF8', '#FDFCF8');
        $this->ink = $this->normalizeHex($branding['ink'] ?? '#1A1918', '#1A1918');

        // Initialize empty menu items (3 slots)
        $this->menuItems = [
            ['name' => '', 'price' => ''],
            ['name' => '', 'price' => ''],
            ['name' => '', 'price' => ''],
        ];

        // Load existing staff for display
        $this->loadStaff();
    }

    // ── Property Sanitizers ──────────────────────────────────

    public function updatedAccent(string $value): void
    {
        $this->accent = $this->normalizeHex($value, '#CC5500');
    }

    public function updatedPaper(string $value): void
    {
        $this->paper = $this->normalizeHex($value, '#FDFCF8');
    }

    public function updatedInk(string $value): void
    {
        $this->ink = $this->normalizeHex($value, '#1A1918');
    }

    // ── Navigation ──────────────────────────────────────────

    public function nextStep()
    {
        $this->onboardingUser();

        if ($this->step < $this->totalSteps) {
            $this->step++;
        }
    }

    public function previousStep()
    {
        $this->onboardingUser();

        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function goToStep(int $step)
    {
        $this->onboardingUser();

        if ($step >= 1 && $step <= $this->totalSteps) {
            $this->step = $step;
        }
    }

    // ── Step 2: Save Shop Profile ───────────────────────────

    public function saveShopProfile()
    {
        $user = $this->onboardingUser();

        $this->validate([
            'currency_code' => 'required|string|min:1|max:3',
            'currency_symbol' => 'required|string|min:1|max:10',
            'currency_decimals' => 'required|integer|in:0,2,3',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'accent' => ['required', 'regex:/^#(?:[A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/'],
            'paper' => ['required', 'regex:/^#(?:[A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/'],
            'ink' => ['required', 'regex:/^#(?:[A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/'],
        ]);

        $shop = $user->shop;
        $branding = $shop->branding ?? [];

        $shop->update([
            'currency_code' => $this->currency_code,
            'currency_symbol' => $this->currency_symbol,
            'currency_decimals' => (int) $this->currency_decimals,
            'tax_rate' => $this->tax_rate,
            'branding' => array_merge($branding, [
                'accent' => $this->normalizeHex($this->accent, '#CC5500'),
                'paper' => $this->normalizeHex($this->paper, '#FDFCF8'),
                'ink' => $this->normalizeHex($this->ink, '#1A1918'),
            ]),
        ]);

        $this->dispatch('toast', message: 'Shop profile saved.', variant: 'success');
        $this->nextStep();
    }

    // ── Step 3: Save Menu Items ─────────────────────────────

    public function addMenuItem()
    {
        $this->onboardingUser();

        if (count($this->menuItems) < 10) {
            $this->menuItems[] = ['name' => '', 'price' => ''];
        }
    }

    public function removeMenuItem(int $index)
    {
        $this->onboardingUser();

        if (count($this->menuItems) > 1) {
            array_splice($this->menuItems, $index, 1);
            $this->menuItems = array_values($this->menuItems);
        }
    }

    public function saveMenuItems()
    {
        $user = $this->onboardingUser();

        // Filter out empty rows
        $items = collect($this->menuItems)->filter(fn ($item) => ! empty(trim($item['name'] ?? '')));

        if ($items->isEmpty()) {
            // Allow skipping -- just go to next step
            $this->nextStep();

            return;
        }

        $this->validate([
            'menuItems.*.name' => 'nullable|string|max:255',
            'menuItems.*.price' => 'nullable|numeric|min:0',
        ]);

        $shop = $user->shop;

        // Create or find the "Menu" category
        $category = Category::firstOrCreate(
            ['shop_id' => $shop->id, 'name_en' => 'Menu'],
            ['name_ar' => 'القائمة', 'sort_order' => 1]
        );

        $order = Product::where('shop_id', $shop->id)
            ->where('category_id', $category->id)
            ->max('sort_order') ?? 0;

        foreach ($items as $item) {
            $name = trim($item['name']);
            $price = (float) ($item['price'] ?? 0);

            if (empty($name)) {
                continue;
            }

            Product::forceCreate([
                'shop_id' => $shop->id,
                'category_id' => $category->id,
                'name_en' => $name,
                'price' => $price,
                'sort_order' => ++$order,
            ]);
        }

        $this->dispatch('toast', message: 'Menu items saved.', variant: 'success');
        $this->nextStep();
    }

    // ── Step 4: Staff PINs ──────────────────────────────────

    public function addStaff()
    {
        $user = $this->onboardingUser();

        $this->validate([
            'staffName' => 'required|string|min:2|max:255',
            'staffEmail' => 'required|email|unique:users,email',
            'staffRole' => 'required|in:manager,cashier,kitchen,server',
            'staffPin' => 'required|digits:4',
        ]);

        $shop = $user->shop;

        User::forceCreate([
            'shop_id' => $shop->id,
            'name' => $this->staffName,
            'email' => $this->staffEmail,
            'role' => $this->staffRole,
            'pin_code' => Hash::make($this->staffPin),
            'password' => Hash::make(str()->random(16)),
        ]);

        $this->resetStaffForm();
        $this->loadStaff();
        $this->dispatch('toast', message: 'Staff member added.', variant: 'success');
    }

    public function saveStaffAndContinue()
    {
        $this->onboardingUser();
        $this->nextStep();
    }

    // ── Step 5: Complete Onboarding ─────────────────────────

    public function loadDemoMenu()
    {
        $shop = $this->onboardingUser()->shop;

        // Only load if the shop has no categories yet (or very few)
        $existingCategories = Category::where('shop_id', $shop->id)->count();
        if ($existingCategories > 1) {
            $this->dispatch('toast', message: 'Your shop already has menu items. Demo menu not loaded.', variant: 'error');

            return;
        }

        (new DemoMenuSeeder)->seedForShop($shop);
        $this->demoMenuLoaded = true;
        $this->dispatch('toast', message: 'Demo menu loaded with 18 products and 3 modifier groups.', variant: 'success');
    }

    public function completeOnboarding()
    {
        $shop = $this->onboardingUser()->shop;
        $branding = $shop->branding ?? [];

        $shop->update([
            'branding' => array_merge($branding, [
                'onboarding_completed' => true,
            ]),
        ]);

        $this->redirect(route('dashboard'), navigate: true);
    }

    // ── Helpers ──────────────────────────────────────────────

    protected function loadStaff()
    {
        $shop = $this->onboardingUser()->shop;
        $this->staffMembers = User::where('shop_id', $shop->id)
            ->where('id', '!=', Auth::id())
            ->orderBy('name')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role ?? 'staff',
                'has_pin' => ! empty($u->pin_code),
            ])
            ->toArray();
    }

    protected function resetStaffForm()
    {
        $this->staffName = '';
        $this->staffEmail = '';
        $this->staffRole = 'cashier';
        $this->staffPin = '';
        $this->resetValidation();
    }

    protected function normalizeHex(string $value, string $fallback): string
    {
        $hex = ltrim($value, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (strlen($hex) !== 6) {
            $hex = ltrim($fallback, '#');
        }

        return '#'.strtolower($hex);
    }

    protected function onboardingUser(): User
    {
        $user = Auth::user();

        if (! $user instanceof User || ! $user->canAccessOnboarding()) {
            abort(403, 'Unauthorized onboarding access.');
        }

        return $user;
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $shop = $this->onboardingUser()->shop;
        $menuUrl = url('/menu/'.$shop->slug);

        return view('livewire.onboarding-wizard', [
            'shop' => $shop,
            'menuUrl' => $menuUrl,
        ]);
    }
}
