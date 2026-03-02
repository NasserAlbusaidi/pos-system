<?php

namespace App\Livewire;

use App\Models\ModifierOption;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemModifier;
use App\Models\Shop;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class GuestMenu extends Component
{
    public Shop $shop;

    public $cart = [];

    public $loyaltyPhone = '';

    public $loyaltyError = null;

    // Customization state
    public $showModifierModal = false;

    public $showReviewModal = false;

    public $customizingProduct = null;

    public $selectedModifiers = [];

    public $modifierError = null;

    public function mount(Shop $shop)
    {
        $this->shop = $shop;
    }

    #[Computed]
    public function total()
    {
        [$subtotal, $tax] = $this->calculateTotals();

        return $subtotal + $tax;
    }

    #[Computed]
    public function subtotal()
    {
        [$subtotal] = $this->calculateTotals();

        return $subtotal;
    }

    #[Computed]
    public function tax()
    {
        [, $tax] = $this->calculateTotals();

        return $tax;
    }

    protected function calculateTotals(): array
    {
        $subtotal = 0;
        $tax = 0;

        foreach ($this->cart as $item) {
            $product = \App\Models\Product::find($item['id']);
            if (! $product) {
                continue;
            }

            $itemTotal = $product->final_price;

            $modifierIds = $this->normalizeModifierIds($item['selectedModifiers'] ?? []);
            if (! empty($modifierIds)) {
                $itemTotal += \App\Models\ModifierOption::whereIn('id', $modifierIds)->sum('price_adjustment');
            }

            $lineTotal = $itemTotal * $item['quantity'];
            $subtotal += $lineTotal;

            $taxRate = $product->tax_rate ?? $this->shop->tax_rate ?? 0;
            if ($taxRate > 0) {
                $tax += $lineTotal * ($taxRate / 100);
            }
        }

        return [$subtotal, $tax];
    }

    #[Computed]
    public function customizingProductPrice()
    {
        if (! $this->customizingProduct) {
            return 0;
        }

        $base = $this->customizingProduct->final_price;
        $modifierIds = $this->normalizeModifierIds($this->selectedModifiers);
        $modifiers = empty($modifierIds)
            ? 0
            : \App\Models\ModifierOption::whereIn('id', $modifierIds)->sum('price_adjustment');

        return $base + $modifiers;
    }

    public function incrementItem($key)
    {
        if (isset($this->cart[$key])) {
            $this->cart[$key]['quantity']++;
        }
    }

    public function decrementItem($key)
    {
        if (isset($this->cart[$key])) {
            $this->cart[$key]['quantity']--;
            if ($this->cart[$key]['quantity'] <= 0) {
                unset($this->cart[$key]);
            }
        }
    }

    public function removeItem($key)
    {
        unset($this->cart[$key]);
    }

    public function toggleReview()
    {
        $this->showReviewModal = ! $this->showReviewModal;
    }

    public function addToCart($productId)
    {
        $product = $this->shop->products()->with('modifierGroups.options')->find($productId);

        if (! $product) {
            return;
        }

        // If product has modifiers and we haven't opened the modal yet
        if ($product->modifierGroups->isNotEmpty() && ! $this->showModifierModal) {
            $this->customizingProduct = $product;
            $this->selectedModifiers = [];
            $this->modifierError = null;
            $this->showModifierModal = true;

            return;
        }

        if ($product->modifierGroups->isNotEmpty()) {
            $selectedByGroup = $this->normalizeModifierGroups($this->selectedModifiers);

            foreach ($product->modifierGroups as $group) {
                $selected = $selectedByGroup[$group->id] ?? [];
                $count = count($selected);

                if ($group->min_selection > 0 && $count < $group->min_selection) {
                    $this->modifierError = "Select at least {$group->min_selection} option(s) for {$group->name}.";
                    $this->showModifierModal = true;

                    return;
                }
            }
        }

        $modifierIds = $this->normalizeModifierIds($this->selectedModifiers);

        // Generate a unique key for the cart (product_id + sorted modifiers)
        $modifierKey = ! empty($modifierIds) ? implode('-', collect($modifierIds)->sort()->toArray()) : 'plain';
        $itemKey = $productId.'-'.$modifierKey;

        $displayPrice = (float) $product->final_price;
        $modifierNames = [];
        if (! empty($modifierIds)) {
            $modifierOptions = ModifierOption::whereIn('id', $modifierIds)->get();
            $displayPrice += $modifierOptions->sum('price_adjustment');
            $modifierNames = $modifierOptions->pluck('name')->all();
        }

        if (isset($this->cart[$itemKey])) {
            $this->cart[$itemKey]['quantity']++;
        } else {
            $this->cart[$itemKey] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $displayPrice,
                'quantity' => 1,
                'selectedModifiers' => $modifierIds,
                'modifierNames' => $modifierNames,
            ];
        }

        // Reset customization state
        $this->showModifierModal = false;
        $this->customizingProduct = null;
        $this->selectedModifiers = [];
        $this->modifierError = null;
    }

    public function submitOrder()
    {
        if (empty($this->cart)) {
            return;
        }

        $this->loyaltyError = null;
        $loyaltyPhone = $this->normalizePhone($this->loyaltyPhone);
        if ($this->loyaltyPhone !== '' && ! $loyaltyPhone) {
            $this->loyaltyError = 'Enter a valid phone number.';
            $this->showReviewModal = true;

            return;
        }

        // Fetch fresh product data to prevent price tampering
        $productIds = collect($this->cart)->pluck('id')->unique()->toArray();
        $products = $this->shop->products()
            ->with('modifierGroups.options')
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $subtotalAmount = 0;
        $taxAmount = 0;
        $orderItems = [];

        foreach ($this->cart as $item) {
            $product = $products->get($item['id']);
            if (! $product) {
                continue;
            }

            $quantity = (int) $item['quantity'];
            if ($quantity < 1) {
                continue;
            }

            $itemPrice = $product->final_price;
            $modifiersData = [];

            $modifierIds = $this->normalizeModifierIds($item['selectedModifiers'] ?? []);
            $allowedModifierIds = $product->modifierGroups
                ->pluck('options')
                ->flatten()
                ->pluck('id')
                ->all();

            $validModifierIds = array_values(array_intersect($modifierIds, $allowedModifierIds));

            if (! empty($validModifierIds)) {
                $modifierOptions = ModifierOption::whereIn('id', $validModifierIds)->get();
                foreach ($modifierOptions as $opt) {
                    $itemPrice += $opt->price_adjustment;
                    $modifiersData[] = [
                        'name' => $opt->name,
                        'price' => $opt->price_adjustment,
                    ];
                }
            }

            $lineTotal = $itemPrice * $quantity;
            $subtotalAmount += $lineTotal;

            $taxRate = $product->tax_rate ?? $this->shop->tax_rate ?? 0;
            if ($taxRate > 0) {
                $taxAmount += $lineTotal * ($taxRate / 100);
            }

            $orderItems[] = [
                'product_id' => $product->id,
                'product_name_snapshot' => $product->name,
                'price_snapshot' => $itemPrice,
                'quantity' => $quantity,
                'modifiers' => $modifiersData,
            ];
        }

        if (empty($orderItems)) {
            return;
        }

        $order = Order::create([
            'shop_id' => $this->shop->id,
            'status' => 'unpaid',
            'loyalty_phone' => $loyaltyPhone,
            'subtotal_amount' => $subtotalAmount,
            'tax_amount' => round($taxAmount, 2),
            'total_amount' => round($subtotalAmount + $taxAmount, 2),
            'tracking_token' => (string) Str::uuid(),
            'expires_at' => now()->addMinutes(6),
        ]);

        foreach ($orderItems as $item) {
            $orderItem = OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'product_name_snapshot' => $item['product_name_snapshot'],
                'price_snapshot' => $item['price_snapshot'],
                'quantity' => $item['quantity'],
            ]);

            foreach ($item['modifiers'] as $mod) {
                OrderItemModifier::create([
                    'order_item_id' => $orderItem->id,
                    'modifier_option_name_snapshot' => $mod['name'],
                    'price_adjustment_snapshot' => $mod['price'],
                ]);
            }
        }

        $this->cart = [];
        $this->loyaltyPhone = '';
        $this->loyaltyError = null;

        return $this->redirect(route('guest.track', $order->tracking_token), navigate: true);
    }

    public function saveFavorite()
    {
        if (empty($this->cart)) {
            session()->flash('message', 'Add items to your cart before saving a favorite.');

            return;
        }

        $items = collect($this->cart)
            ->values()
            ->map(fn ($item) => [
                'id' => $item['id'],
                'quantity' => (int) ($item['quantity'] ?? 1),
                'selectedModifiers' => $item['selectedModifiers'] ?? [],
            ])
            ->all();

        $this->dispatch('favorite:save', items: $items, shop: $this->shop->id);
        session()->flash('message', 'Favorite saved on this device.');
    }

    #[On('favorite:apply')]
    public function applyFavorite($items = [])
    {
        $items = collect($items)->filter(fn ($item) => isset($item['id']))->values();
        if ($items->isEmpty()) {
            session()->flash('message', 'No favorite saved on this device yet.');

            return;
        }

        $productIds = $items->pluck('id')->unique()->all();
        $products = $this->shop->products()
            ->with('modifierGroups.options')
            ->whereIn('id', $productIds)
            ->where('is_visible', true)
            ->where('is_available', true)
            ->get()
            ->keyBy('id');

        $newCart = [];
        foreach ($items as $item) {
            $product = $products->get($item['id']);
            if (! $product) {
                continue;
            }

            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $modifierIds = $this->normalizeModifierIds($item['selectedModifiers'] ?? []);
            $allowedModifierIds = $product->modifierGroups
                ->pluck('options')
                ->flatten()
                ->pluck('id')
                ->all();

            $validModifierIds = array_values(array_intersect($modifierIds, $allowedModifierIds));

            $displayPrice = (float) $product->final_price;
            $modifierNames = [];
            if (! empty($validModifierIds)) {
                $modifierOptions = \App\Models\ModifierOption::whereIn('id', $validModifierIds)->get();
                $displayPrice += $modifierOptions->sum('price_adjustment');
                $modifierNames = $modifierOptions->pluck('name')->all();
            }

            $modifierKey = ! empty($validModifierIds)
                ? implode('-', collect($validModifierIds)->sort()->toArray())
                : 'plain';
            $itemKey = $product->id.'-'.$modifierKey;

            if (isset($newCart[$itemKey])) {
                $newCart[$itemKey]['quantity'] += $quantity;

                continue;
            }

            $newCart[$itemKey] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $displayPrice,
                'quantity' => $quantity,
                'selectedModifiers' => $validModifierIds,
                'modifierNames' => $modifierNames,
            ];
        }

        if (empty($newCart)) {
            session()->flash('message', 'Favorite items are no longer available.');

            return;
        }

        $this->cart = $newCart;
        session()->flash('message', 'Favorite loaded.');
    }

    protected function normalizeModifierIds($value): array
    {
        return collect($value)
            ->flatten()
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    protected function normalizeModifierGroups($value): array
    {
        $groups = [];
        foreach ((array) $value as $groupId => $ids) {
            $ids = is_array($ids) ? $ids : [$ids];
            $groups[$groupId] = array_values(array_filter($ids, fn ($id) => $id !== null && $id !== ''));
        }

        return $groups;
    }

    protected function normalizePhone(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $digits = preg_replace('/[^0-9]/', '', $value);
        if ($digits === '' || strlen($digits) < 6) {
            return null;
        }

        return substr($digits, 0, 20);
    }

    public function render()
    {
        $categories = $this->shop->categories()
            ->with(['products' => function ($query) {
                $query->where('is_visible', true)
                    ->where('is_available', true)
                    ->orderBy('sort_order');
            }])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->filter(fn ($category) => $category->products->isNotEmpty());

        return view('livewire.guest-menu', [
            'categories' => $categories,
        ])->layout('layouts.app', ['shop' => $this->shop]);
    }
}
