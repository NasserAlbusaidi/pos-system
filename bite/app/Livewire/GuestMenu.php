<?php

namespace App\Livewire;

use App\Models\GroupCart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemModifier;
use App\Models\PricingRule;
use App\Models\Shop;
use App\Notifications\NewOrderNotification;
use App\Services\LoyaltyService;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
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

    public $recognizedCustomer = null;

    public $showWelcomeBack = false;

    public $orderError = null;

    public $locale = 'en';

    // Group ordering state
    public $groupToken = null;

    public $participantId = null;

    public $showGroupShareModal = false;

    public function mount(Shop $shop)
    {
        $this->shop = $shop;

        // Determine locale: session override > shop default > 'en'
        $branding = $shop->branding ?? [];
        $this->locale = session('guest_locale', $branding['language'] ?? 'en');

        // Generate or retrieve a stable participant ID for this browser session
        $this->participantId = session('guest_participant_id');
        if (! $this->participantId) {
            $this->participantId = (string) Str::uuid();
            session()->put('guest_participant_id', $this->participantId);
        }

        // Check if joining a group via ?group=UUID query parameter
        $groupParam = request()->query('group');
        if ($groupParam && Str::isUuid($groupParam)) {
            $this->joinGroup($groupParam);
        }
    }

    public function switchLanguage(string $lang)
    {
        $lang = in_array($lang, ['en', 'ar']) ? $lang : 'en';
        $this->locale = $lang;
        session()->put('guest_locale', $lang);
        App::setLocale($lang);
    }

    // ──────────────────────────────────
    // Group ordering methods
    // ──────────────────────────────────

    /**
     * Create a new group cart and enter group mode.
     */
    public function createGroup(): void
    {
        $groupCart = GroupCart::create([
            'shop_id' => $this->shop->id,
            'group_token' => (string) Str::uuid(),
            'items' => [],
            'participant_count' => 1,
            'expires_at' => now()->addHour(),
        ]);

        $this->groupToken = $groupCart->group_token;
        $this->showGroupShareModal = true;

        // Migrate any existing solo cart items into the group cart
        if (! empty($this->cart)) {
            foreach ($this->cart as $itemKey => $item) {
                $groupCart->addItem($this->participantId, array_merge($item, [
                    'itemKey' => $itemKey,
                ]));
            }
            $this->cart = [];
        }
    }

    /**
     * Join an existing group cart by token.
     */
    public function joinGroup(string $token): void
    {
        if (! Str::isUuid($token)) {
            return;
        }

        $groupCart = GroupCart::where('group_token', $token)
            ->where('shop_id', $this->shop->id)
            ->first();

        if (! $groupCart || $groupCart->isExpired()) {
            session()->flash('message', __('guest.group_expired'));

            return;
        }

        // Track unique participants atomically with row lock
        DB::transaction(function () use ($groupCart) {
            GroupCart::where('id', $groupCart->id)->lockForUpdate()->first();
            $groupCart->refresh();

            $existingParticipants = collect($groupCart->items ?? [])
                ->pluck('participant_id')
                ->unique()
                ->all();

            if (! in_array($this->participantId, $existingParticipants)) {
                $currentCount = max(count($existingParticipants), (int) $groupCart->participant_count);
                $groupCart->update([
                    'participant_count' => $currentCount + 1,
                ]);
            }
        });

        $this->groupToken = $groupCart->group_token;

        // Clear solo cart when entering group mode
        $this->cart = [];
    }

    /**
     * Leave the group and return to solo mode.
     */
    public function leaveGroup(): void
    {
        $this->groupToken = null;
        $this->showGroupShareModal = false;
    }

    /**
     * Check if the group cart is still valid. If expired, reset to solo mode.
     * Returns true if we are NOT in group mode or the group cart is still valid.
     */
    protected function ensureGroupCartValid(): bool
    {
        if (! $this->groupToken) {
            return true;
        }

        $groupCart = GroupCart::where('group_token', $this->groupToken)
            ->where('shop_id', $this->shop->id)
            ->first();

        if (! $groupCart || $groupCart->isExpired()) {
            $this->groupToken = null;
            $this->showGroupShareModal = false;
            session()->flash('message', __('guest.group_expired'));

            return false;
        }

        return true;
    }

    /**
     * Toggle the share modal for the group link.
     */
    public function toggleGroupShare(): void
    {
        $this->showGroupShareModal = ! $this->showGroupShareModal;
    }

    /**
     * Get the current group cart model (if in group mode).
     */
    #[Computed]
    public function groupCart(): ?GroupCart
    {
        if (! $this->groupToken) {
            return null;
        }

        return GroupCart::where('group_token', $this->groupToken)
            ->where('shop_id', $this->shop->id)
            ->first();
    }

    /**
     * Get the shareable URL for the group cart.
     */
    #[Computed]
    public function groupShareUrl(): ?string
    {
        if (! $this->groupToken) {
            return null;
        }

        return route('guest.menu', $this->shop->slug).'?group='.$this->groupToken;
    }

    /**
     * Whether we are in group ordering mode.
     */
    #[Computed]
    public function isGroupMode(): bool
    {
        return $this->groupToken !== null;
    }

    // ──────────────────────────────────
    // Totals (works for both solo + group)
    // ──────────────────────────────────

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
        $cartItems = $this->getActiveCartItems();

        if (empty($cartItems)) {
            return [0, 0];
        }

        $productIds = collect($cartItems)->pluck('id')->unique()->all();
        $products = $this->shop->products()
            ->with('modifierGroups.options')
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $pricingRules = $this->loadActivePricingRules();

        $subtotal = 0;
        $tax = 0;

        foreach ($cartItems as $item) {
            $product = $products->get($item['id']);
            if (! $product) {
                continue;
            }

            $itemTotal = $pricingRules->isNotEmpty()
                ? $product->getTimePriced($pricingRules)
                : $product->final_price;

            $modifierIds = $this->normalizeModifierIds($item['selectedModifiers'] ?? []);
            if (! empty($modifierIds)) {
                $itemTotal += $this->sumModifierPrices($product, $modifierIds);
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

    /**
     * Get the active cart items — from group cart if in group mode, otherwise solo cart.
     */
    protected function getActiveCartItems(): array
    {
        if ($this->isGroupMode) {
            $groupCart = $this->groupCart;
            if (! $groupCart) {
                return [];
            }

            return $groupCart->items ?? [];
        }

        return $this->cart;
    }

    /**
     * Get the active cart item count for the bottom bar badge.
     */
    #[Computed]
    public function cartItemCount(): int
    {
        $items = $this->getActiveCartItems();

        return (int) collect($items)->sum(fn ($item) => $item['quantity'] ?? 1);
    }

    #[Computed]
    public function customizingProductPrice()
    {
        if (! $this->customizingProduct) {
            return 0;
        }

        $pricingRules = $this->loadActivePricingRules();
        $base = $pricingRules->isNotEmpty()
            ? $this->customizingProduct->getTimePriced($pricingRules)
            : $this->customizingProduct->final_price;

        $modifierIds = $this->normalizeModifierIds($this->selectedModifiers);
        if (empty($modifierIds)) {
            return $base;
        }

        return $base + $this->sumModifierPrices($this->customizingProduct, $modifierIds);
    }

    // ──────────────────────────────────
    // Cart manipulation (solo + group)
    // ──────────────────────────────────

    public function incrementItem($key)
    {
        if ($this->isGroupMode) {
            $groupCart = $this->groupCart;
            if ($groupCart) {
                $groupCart->updateItemQuantity($this->participantId, $key, 1);
            }

            return;
        }

        if (isset($this->cart[$key])) {
            $this->cart[$key]['quantity']++;
        }
    }

    public function decrementItem($key)
    {
        if ($this->isGroupMode) {
            $groupCart = $this->groupCart;
            if ($groupCart) {
                $groupCart->updateItemQuantity($this->participantId, $key, -1);
            }

            return;
        }

        if (isset($this->cart[$key])) {
            $this->cart[$key]['quantity']--;
            if ($this->cart[$key]['quantity'] <= 0) {
                unset($this->cart[$key]);
            }
        }
    }

    public function removeItem($key)
    {
        if ($this->isGroupMode) {
            $groupCart = $this->groupCart;
            if ($groupCart) {
                $groupCart->removeItem($this->participantId, $key);
            }

            return;
        }

        unset($this->cart[$key]);
    }

    /**
     * Remove a specific item from the group cart by its array index.
     * Only allows removing your own items.
     * Uses DB transaction + row lock to prevent race conditions.
     */
    public function removeGroupItem(int $index): void
    {
        if (! $this->isGroupMode) {
            return;
        }

        $groupCart = $this->groupCart;
        if (! $groupCart) {
            return;
        }

        DB::transaction(function () use ($groupCart, $index) {
            GroupCart::where('id', $groupCart->id)->lockForUpdate()->first();
            $groupCart->refresh();
            $items = $groupCart->items ?? [];
            $item = $items[$index] ?? null;

            // Only allow removing your own items
            if (! $item || ($item['participant_id'] ?? '') !== $this->participantId) {
                return;
            }

            unset($items[$index]);
            $groupCart->items = array_values($items);
            $groupCart->save();
        });
    }

    public function toggleReview()
    {
        $this->showReviewModal = ! $this->showReviewModal;
    }

    /**
     * Explicitly manage modifier selection to avoid Livewire hydration issues
     * with mixed scalar (radio) and array (checkbox) values in the same property.
     */
    public function selectModifier(int $groupId, int $optionId, bool $isMultiple = false): void
    {
        $optionIdStr = (string) $optionId;

        if ($isMultiple) {
            // Checkbox: toggle the option in an array
            $current = $this->selectedModifiers[$groupId] ?? [];
            if (! is_array($current)) {
                $current = [$current];
            }

            if (in_array($optionIdStr, $current)) {
                $current = array_values(array_filter($current, fn ($id) => $id !== $optionIdStr));
            } else {
                $current[] = $optionIdStr;
            }
            $this->selectedModifiers[$groupId] = $current;
        } else {
            // Radio: replace the scalar value for this group
            $this->selectedModifiers[$groupId] = $optionIdStr;
        }
    }

    public function addToCart($productId)
    {
        if (! $this->ensureGroupCartValid()) {
            return;
        }

        $product = $this->shop->products()
            ->with('modifierGroups.options')
            ->where('is_available', true)
            ->find($productId);

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
                    $this->modifierError = __('guest.select_at_least', ['count' => $group->min_selection, 'group' => $group->translated('name')]);
                    $this->showModifierModal = true;

                    return;
                }
            }
        }

        $modifierIds = $this->normalizeModifierIds($this->selectedModifiers);

        // Generate a unique key for the cart (product_id + sorted modifiers)
        $modifierKey = ! empty($modifierIds) ? implode('-', collect($modifierIds)->sort()->toArray()) : 'plain';
        $itemKey = $productId.'-'.$modifierKey;

        $pricingRules = $this->loadActivePricingRules();
        $displayPrice = $pricingRules->isNotEmpty()
            ? (float) $product->getTimePriced($pricingRules)
            : (float) $product->final_price;
        $modifierNames = [];
        if (! empty($modifierIds)) {
            $validOptions = $this->getValidModifierOptions($product, $modifierIds);
            $displayPrice += $validOptions->sum('price_adjustment');
            $modifierNames = $validOptions->map(fn ($o) => $o->translated('name'))->all();
            $modifierIds = $validOptions->pluck('id')->all();
        }

        if ($this->isGroupMode) {
            // Group mode: write to GroupCart model
            $groupCart = $this->groupCart;
            if ($groupCart && ! $groupCart->isExpired()) {
                $groupCart->addItem($this->participantId, [
                    'id' => $product->id,
                    'itemKey' => $itemKey,
                    'name' => $product->translated('name'),
                    'price' => $displayPrice,
                    'quantity' => 1,
                    'selectedModifiers' => $modifierIds,
                    'modifierNames' => $modifierNames,
                ]);
            }
        } else {
            // Solo mode: write to local $cart
            if (isset($this->cart[$itemKey])) {
                $this->cart[$itemKey]['quantity']++;
            } else {
                $this->cart[$itemKey] = [
                    'id' => $product->id,
                    'name' => $product->translated('name'),
                    'price' => $displayPrice,
                    'quantity' => 1,
                    'selectedModifiers' => $modifierIds,
                    'modifierNames' => $modifierNames,
                ];
            }
        }

        // Reset customization state
        $this->showModifierModal = false;
        $this->customizingProduct = null;
        $this->selectedModifiers = [];
        $this->modifierError = null;
    }

    /**
     * Called when loyaltyPhone changes. If 8+ digits, look up the customer.
     */
    public function recognizeCustomer(): void
    {
        $phone = trim($this->loyaltyPhone);
        $digits = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($digits) < 8) {
            $this->recognizedCustomer = null;
            $this->showWelcomeBack = false;

            return;
        }

        $loyaltyService = app(LoyaltyService::class);
        $customer = $loyaltyService->recognize($phone, $this->shop->id);

        if ($customer) {
            $this->recognizedCustomer = [
                'name' => $this->firstNameToken($customer->name),
                'points' => (int) $customer->points,
            ];
            $this->showWelcomeBack = true;
        } else {
            $this->recognizedCustomer = null;
            $this->showWelcomeBack = false;
        }
    }

    /**
     * Load the customer's favorites into the cart (reorder their usual).
     */
    public function orderUsual(): void
    {
        if (! $this->recognizedCustomer) {
            return;
        }

        $this->applyFavorite();
    }

    public function submitOrder()
    {
        if (! $this->ensureGroupCartValid()) {
            return;
        }

        $cartItems = $this->getActiveCartItems();

        if (empty($cartItems)) {
            return;
        }

        $rateLimitKey = 'guest-order:'.request()->ip();
        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            $this->orderError = "You're ordering too quickly. Please wait a moment and try again.";
            $this->showReviewModal = true;

            return;
        }
        RateLimiter::hit($rateLimitKey, 900);

        $this->orderError = null;
        $this->loyaltyError = null;
        $loyaltyPhone = $this->normalizePhone($this->loyaltyPhone);
        if ($this->loyaltyPhone !== '' && ! $loyaltyPhone) {
            $this->loyaltyError = __('guest.invalid_phone');
            $this->showReviewModal = true;

            return;
        }

        // Fetch fresh product data to prevent price tampering and verify availability
        $productIds = collect($cartItems)->pluck('id')->unique()->toArray();
        $products = $this->shop->products()
            ->with('modifierGroups.options')
            ->where('is_available', true)
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        // Check for 86'd (unavailable) items
        $unavailableNames = collect($cartItems)
            ->filter(fn ($item) => ! $products->has($item['id']))
            ->pluck('name')
            ->unique()
            ->all();

        if (! empty($unavailableNames)) {
            // Auto-remove unavailable items from cart
            $unavailableIds = collect($cartItems)
                ->filter(fn ($item) => ! $products->has($item['id']))
                ->pluck('id')
                ->unique()
                ->all();

            if ($this->isGroupMode) {
                $groupCart = $this->groupCart;
                if ($groupCart) {
                    DB::transaction(function () use ($groupCart, $unavailableIds) {
                        GroupCart::where('id', $groupCart->id)->lockForUpdate()->first();
                        $groupCart->refresh();
                        $items = collect($groupCart->items ?? [])
                            ->filter(fn ($item) => ! in_array($item['id'], $unavailableIds))
                            ->values()
                            ->all();
                        $groupCart->update(['items' => $items]);
                    });
                }
            } else {
                $this->cart = collect($this->cart)
                    ->filter(fn ($item) => ! in_array($item['id'], $unavailableIds))
                    ->all();
            }

            $this->orderError = __('guest.items_unavailable_removed', [
                'items' => implode(', ', $unavailableNames),
            ]);
            $this->showReviewModal = true;

            return;
        }

        $pricingRules = $this->loadActivePricingRules();

        $subtotalAmount = 0;
        $taxAmount = 0;
        $orderItems = [];

        foreach ($cartItems as $item) {
            $product = $products->get($item['id']);
            if (! $product) {
                continue;
            }

            $quantity = (int) $item['quantity'];
            if ($quantity < 1) {
                continue;
            }

            $itemPrice = $pricingRules->isNotEmpty()
                ? $product->getTimePriced($pricingRules)
                : $product->final_price;
            $modifiersData = [];

            $modifierIds = $this->normalizeModifierIds($item['selectedModifiers'] ?? []);
            $validOptions = $this->getValidModifierOptions($product, $modifierIds);

            foreach ($validOptions as $opt) {
                $itemPrice += $opt->price_adjustment;
                $modifiersData[] = [
                    'name_en' => $opt->name_en,
                    'name_ar' => $opt->name_ar,
                    'price' => $opt->price_adjustment,
                ];
            }

            $lineTotal = $itemPrice * $quantity;
            $subtotalAmount += $lineTotal;

            $taxRate = $product->tax_rate ?? $this->shop->tax_rate ?? 0;
            if ($taxRate > 0) {
                $taxAmount += $lineTotal * ($taxRate / 100);
            }

            $orderItems[] = [
                'product_id' => $product->id,
                'product_name_snapshot_en' => $product->name_en,
                'product_name_snapshot_ar' => $product->name_ar,
                'price_snapshot' => $itemPrice,
                'quantity' => $quantity,
                'modifiers' => $modifiersData,
            ];
        }

        if (empty($orderItems)) {
            return;
        }

        $order = DB::transaction(function () use ($subtotalAmount, $taxAmount, $loyaltyPhone, $orderItems) {
            $order = Order::forceCreate([
                'shop_id' => $this->shop->id,
                'status' => 'unpaid',
                'loyalty_phone' => $loyaltyPhone,
                'subtotal_amount' => $subtotalAmount,
                'tax_amount' => round($taxAmount, 3),
                'total_amount' => round($subtotalAmount + $taxAmount, 3),
                'tracking_token' => (string) Str::uuid(),
                'expires_at' => now()->addMinutes(config('billing.order_expiry_minutes', 6)),
            ]);

            foreach ($orderItems as $item) {
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name_snapshot_en' => $item['product_name_snapshot_en'],
                    'product_name_snapshot_ar' => $item['product_name_snapshot_ar'],
                    'price_snapshot' => $item['price_snapshot'],
                    'quantity' => $item['quantity'],
                ]);

                foreach ($item['modifiers'] as $mod) {
                    OrderItemModifier::create([
                        'order_item_id' => $orderItem->id,
                        'modifier_option_name_snapshot_en' => $mod['name_en'],
                        'modifier_option_name_snapshot_ar' => $mod['name_ar'],
                        'price_adjustment_snapshot' => $mod['price'],
                    ]);
                }
            }

            return $order;
        });

        // Save current cart as customer's favorites for "Order your usual"
        if ($loyaltyPhone) {
            $loyaltyService = app(LoyaltyService::class);
            $customer = $loyaltyService->recognize($loyaltyPhone, $this->shop->id);
            if ($customer) {
                $customer->saveFavorites(array_values($this->isGroupMode ? $cartItems : $this->cart));
            }
        }

        // Send WhatsApp notification to shop if enabled
        $whatsapp = app(WhatsAppService::class);
        if ($whatsapp->isEnabled($this->shop)) {
            $this->shop->notify(new NewOrderNotification($order));
        }

        // Clean up group cart if in group mode
        if ($this->isGroupMode) {
            $groupCart = $this->groupCart;
            if ($groupCart) {
                $groupCart->delete();
            }
            $this->groupToken = null;
        }

        $this->cart = [];
        $this->loyaltyPhone = '';
        $this->loyaltyError = null;
        $this->recognizedCustomer = null;
        $this->showWelcomeBack = false;

        return $this->redirect(route('guest.track', $order->tracking_token), navigate: true);
    }

    public function saveFavorite()
    {
        $cartItems = $this->getActiveCartItems();

        if (empty($cartItems)) {
            session()->flash('message', __('guest.favorite_add_first'));

            return;
        }

        $items = collect($cartItems)
            ->values()
            ->map(fn ($item) => [
                'id' => $item['id'],
                'quantity' => (int) ($item['quantity'] ?? 1),
                'selectedModifiers' => $item['selectedModifiers'] ?? [],
            ])
            ->all();

        $this->dispatch('favorite:save', items: $items, shop: $this->shop->id);
        session()->flash('message', __('guest.favorite_saved'));
    }

    public function applyFavorite(): void
    {
        $customer = app(LoyaltyService::class)->recognize($this->loyaltyPhone, $this->shop->id);
        if (! $customer) {
            session()->flash('message', __('guest.favorite_empty'));

            return;
        }

        $this->applyFavoriteItems($customer->getFavorites());
    }

    #[On('favorite:apply')]
    public function applySavedFavorite($items = []): void
    {
        $this->applyFavoriteItems($items);
    }

    protected function applyFavoriteItems($items = []): void
    {
        $items = collect($items)->filter(fn ($item) => isset($item['id']))->values();
        if ($items->isEmpty()) {
            session()->flash('message', __('guest.favorite_empty'));

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

        $pricingRules = $this->loadActivePricingRules();

        $newCart = [];
        $removedCount = 0;
        foreach ($items as $item) {
            $product = $products->get($item['id']);
            if (! $product) {
                $removedCount++;

                continue;
            }

            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $modifierIds = $this->normalizeModifierIds($item['selectedModifiers'] ?? []);
            $validOptions = $this->getValidModifierOptions($product, $modifierIds);
            $validModifierIds = $validOptions->pluck('id')->all();

            $displayPrice = $pricingRules->isNotEmpty()
                ? (float) $product->getTimePriced($pricingRules)
                : (float) $product->final_price;
            $modifierNames = [];
            if ($validOptions->isNotEmpty()) {
                $displayPrice += $validOptions->sum('price_adjustment');
                $modifierNames = $validOptions->map(fn ($o) => $o->translated('name'))->all();
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
                'name' => $product->translated('name'),
                'price' => $displayPrice,
                'quantity' => $quantity,
                'selectedModifiers' => $validModifierIds,
                'modifierNames' => $modifierNames,
            ];
        }

        if (empty($newCart)) {
            session()->flash('message', __('guest.favorite_unavailable'));

            return;
        }

        $loadedMessage = $removedCount > 0
            ? __('guest.favorite_loaded_partial', ['removed' => $removedCount])
            : __('guest.favorite_loaded');

        // In group mode, push favorites into the group cart
        if ($this->isGroupMode) {
            $groupCart = $this->groupCart;
            if ($groupCart && ! $groupCart->isExpired()) {
                foreach ($newCart as $itemKey => $item) {
                    $groupCart->addItem($this->participantId, array_merge($item, [
                        'itemKey' => $itemKey,
                    ]));
                }
            }
            session()->flash('message', $loadedMessage);

            return;
        }

        $this->cart = $newCart;
        session()->flash('message', $loadedMessage);
    }

    protected function firstNameToken(?string $name): string
    {
        return Str::of($name ?? '')->squish()->before(' ')->toString();
    }

    /**
     * Get modifier options that belong to the product's modifier groups.
     * This is the tenant-safety boundary — only returns options owned by
     * the product (and therefore the shop), never cross-tenant options.
     */
    protected function getValidModifierOptions($product, array $modifierIds): \Illuminate\Support\Collection
    {
        if (empty($modifierIds)) {
            return collect();
        }

        return $product->modifierGroups
            ->pluck('options')
            ->flatten()
            ->whereIn('id', $modifierIds);
    }

    /**
     * Sum price adjustments for valid modifiers on a product.
     */
    protected function sumModifierPrices($product, array $modifierIds): float
    {
        return (float) $this->getValidModifierOptions($product, $modifierIds)
            ->sum('price_adjustment');
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

    /**
     * Load active pricing rules for the current shop.
     * Returns an empty collection if no rules are active right now.
     */
    protected function loadActivePricingRules(): \Illuminate\Support\Collection
    {
        return PricingRule::where('shop_id', $this->shop->id)
            ->activeNow()
            ->get();
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
                    ->with('modifierGroups.options')
                    ->orderBy('sort_order');
            }])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->filter(fn ($category) => $category->products->isNotEmpty());

        // Prepare group cart items for the view
        $groupCartItems = [];
        $participantColors = [];
        if ($this->isGroupMode) {
            $groupCart = $this->groupCart;
            if ($groupCart) {
                $groupCartItems = $groupCart->items ?? [];

                // Build participant color map
                $uniqueParticipants = collect($groupCartItems)
                    ->pluck('participant_id')
                    ->unique()
                    ->values();

                $colors = ['#E57373', '#64B5F6', '#81C784', '#FFD54F', '#BA68C8', '#4DB6AC', '#FF8A65', '#A1887F'];
                foreach ($uniqueParticipants as $index => $pid) {
                    $participantColors[$pid] = $colors[$index % count($colors)];
                }
            }
        }

        $pricingRules = $this->loadActivePricingRules();

        $theme = in_array($this->shop->branding['theme'] ?? '', ['warm', 'modern', 'dark'])
            ? $this->shop->branding['theme']
            : 'warm';

        return view('livewire.guest-menu', [
            'categories' => $categories,
            'locale' => $this->locale,
            'isRtl' => $this->locale === 'ar',
            'groupCartItems' => $groupCartItems,
            'participantColors' => $participantColors,
            'pricingRules' => $pricingRules,
            'theme' => $theme,
        ])->layout('layouts.app', ['shop' => $this->shop]);
    }
}
