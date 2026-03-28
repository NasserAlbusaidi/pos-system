<?php

namespace App\Livewire;

use App\Exceptions\MenuExtractionException;
use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Product;
use App\Models\User;
use App\Services\ImageService;
use App\Services\MenuExtractionService;
use Database\Seeders\DemoMenuSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

class OnboardingWizard extends Component
{
    use WithFileUploads;

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

    public array $menuPhotos = [];

    public array $extractedItems = [];

    public string $menuMode = 'choose'; // choose | extracting | review | manual

    public string $extractionError = '';

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

    public function updatedMenuPhotos(): void
    {
        $this->onboardingUser();

        $this->validate([
            'menuPhotos' => 'required|array|min:1|max:4',
        ]);
    }

    public function extractMenu(): void
    {
        $this->onboardingUser();

        $this->validate([
            'menuPhotos' => 'required|array|min:1|max:4',
        ]);

        $this->menuMode = 'extracting';
        $this->extractionError = '';

        try {
            $images = [];
            foreach ($this->menuPhotos as $photo) {
                $stream = $photo->readStream();
                $contents = stream_get_contents($stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
                $images[] = [
                    'mime_type' => $photo->getMimeType(),
                    'data' => base64_encode($contents),
                ];
            }

            $service = app(MenuExtractionService::class);
            $items = $service->extract($images);

            if (empty($items)) {
                $this->extractionError = 'no_items';
                $this->menuMode = 'choose';

                return;
            }

            $this->extractedItems = $items;
            $this->menuMode = 'review';
        } catch (MenuExtractionException $e) {
            report($e);
            $this->extractionError = $e->reason;
            $this->menuMode = 'choose';
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::channel('stderr')->error('Snap-to-Menu error', [
                'class' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
            ]);
            report($e);
            $this->extractionError = 'api_error';
            $this->menuMode = 'choose';
        }
    }

    public function addExtractedItem(): void
    {
        $this->onboardingUser();

        $this->extractedItems[] = [
            'category_en' => '',
            'category_ar' => '',
            'name_en' => '',
            'name_ar' => '',
            'description_en' => '',
            'description_ar' => '',
            'price' => 0,
        ];
    }

    public function removeExtractedItem(int $index): void
    {
        $this->onboardingUser();

        if (count($this->extractedItems) > 1) {
            array_splice($this->extractedItems, $index, 1);
            $this->extractedItems = array_values($this->extractedItems);
        }
    }

    public function resetExtraction(): void
    {
        $this->onboardingUser();

        $this->menuPhotos = [];
        $this->extractedItems = [];
        $this->extractionError = '';
        $this->menuMode = 'choose';
    }

    public function showManualEntry(): void
    {
        $this->onboardingUser();

        $this->menuMode = 'manual';
    }

    public function saveExtractedMenu(): void
    {
        $user = $this->onboardingUser();

        // Filter out items without names
        $items = collect($this->extractedItems)
            ->filter(fn ($item) => ! empty(trim($item['name_en'] ?? '')) || ! empty(trim($item['name_ar'] ?? '')));

        if ($items->isEmpty()) {
            $this->nextStep();

            return;
        }

        $this->validate([
            'extractedItems.*.name_en' => 'nullable|string|max:255',
            'extractedItems.*.name_ar' => 'nullable|string|max:255',
            'extractedItems.*.description_en' => 'nullable|string|max:500',
            'extractedItems.*.description_ar' => 'nullable|string|max:500',
            'extractedItems.*.category_en' => 'nullable|string|max:255',
            'extractedItems.*.category_ar' => 'nullable|string|max:255',
            'extractedItems.*.price' => 'nullable|numeric|min:0',
        ]);

        $shop = $user->shop;

        // Group by category for organized creation
        $grouped = $items->groupBy(fn ($item) => trim($item['category_en'] ?? '') ?: 'Menu');

        $categorySortOrder = Category::where('shop_id', $shop->id)->max('sort_order') ?? 0;

        foreach ($grouped as $categoryName => $categoryItems) {
            $categoryAr = trim($categoryItems->first()['category_ar'] ?? '') ?: 'القائمة';

            $category = Category::firstOrCreate(
                ['shop_id' => $shop->id, 'name_en' => $categoryName],
                ['name_ar' => $categoryAr, 'sort_order' => ++$categorySortOrder]
            );

            $productSortOrder = Product::where('shop_id', $shop->id)
                ->where('category_id', $category->id)
                ->max('sort_order') ?? 0;

            foreach ($categoryItems as $item) {
                $nameEn = trim($item['name_en'] ?? '');
                $nameAr = trim($item['name_ar'] ?? '');

                if ($nameEn === '' && $nameAr === '') {
                    continue;
                }

                $product = Product::forceCreate([
                    'shop_id' => $shop->id,
                    'category_id' => $category->id,
                    'name_en' => $nameEn,
                    'name_ar' => $nameAr,
                    'description_en' => trim($item['description_en'] ?? ''),
                    'description_ar' => trim($item['description_ar'] ?? ''),
                    'price' => max(0.0, (float) ($item['price'] ?? 0)),
                    'sort_order' => ++$productSortOrder,
                ]);

                // Image optimization hook (D-05): process product image if set
                // Currently OnboardingWizard creates products without images,
                // but this hook ensures ImageService is called if image upload is added later.
                if ($product->image_url) {
                    try {
                        $imageService = app(ImageService::class);
                        $product->update(['image_url' => $imageService->processUpload($product->image_url)]);
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            }
        }

        $this->dispatch('toast', message: 'Menu items saved.', variant: 'success');
        $this->nextStep();
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

            $product = Product::forceCreate([
                'shop_id' => $shop->id,
                'category_id' => $category->id,
                'name_en' => $name,
                'price' => $price,
                'sort_order' => ++$order,
            ]);

            // Image optimization hook (D-05): process product image if set
            // Currently OnboardingWizard creates products without images,
            // but this hook ensures ImageService is called if image upload is added later.
            if ($product->image_url) {
                try {
                    $imageService = app(ImageService::class);
                    $product->update(['image_url' => $imageService->processUpload($product->image_url)]);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
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

        // Clear existing menu data before loading demo (safe during onboarding)
        $modifierGroupIds = ModifierGroup::where('shop_id', $shop->id)->pluck('id');
        if ($modifierGroupIds->isNotEmpty()) {
            ModifierOption::whereIn('modifier_group_id', $modifierGroupIds)->delete();
            DB::table('product_modifier_group')->whereIn('modifier_group_id', $modifierGroupIds)->delete();
        }
        ModifierGroup::where('shop_id', $shop->id)->delete();
        Product::where('shop_id', $shop->id)->delete();
        Category::where('shop_id', $shop->id)->delete();

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
