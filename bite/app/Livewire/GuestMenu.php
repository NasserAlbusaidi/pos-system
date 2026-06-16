<?php

namespace App\Livewire;

use App\Models\GroupCart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemModifier;
use App\Models\PricingRule;
use App\Models\Product;
use App\Models\Shop;
use App\Notifications\NewOrderNotification;
use App\Services\LoyaltyService;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class GuestMenu extends Component
{
    public Shop $shop;

    public $cart = [];

    public $loyaltyPhone = '';

    public $loyaltyError = null;

    // Checkout contact (Phase 4, #24). Name + phone are required at checkout
    // for the pay-at-counter pilot. Untrusted: trimmed on submit.
    public $customerName = '';

    public string $paymentMethod = 'counter';

    public string $voucherCode = '';

    public bool $voucherApplied = false;

    // Guest-supplied order-level note (Phase 4, #24): one note for the whole
    // order ("table by the window", shared allergen flag). Untrusted: trimmed
    // + capped on submit. Reaches the kitchen (KDS card + printed ticket).
    public $orderNote = '';

    // Customization state
    public $showModifierModal = false;

    public $showReviewModal = false;

    public $customizingProduct = null;

    public $selectedModifiers = [];

    // Guest-supplied special request for the line being customized (allergen
    // safety in the bakery pilot). Untrusted: trimmed + capped on add-to-cart.
    public $itemNote = '';

    public $modifierError = null;

    public $recognizedCustomer = null;

    public $showWelcomeBack = false;

    public $orderError = null;

    // Per-checkout idempotency token (Phase 7a, #28). Set when the review sheet
    // opens and sent with submitOrder. A double-click / network retry / replayed
    // Livewire request carries the SAME token, so the order insert collides on
    // the orders.idempotency_key UNIQUE index and we redirect to the existing
    // order instead of creating a duplicate. Regenerated after a successful
    // order so the next checkout is distinct.
    //
    // #[Locked]: server-minted, the client must never mutate it. Without the
    // lock a guest could $wire.set() it to null (silently defeating idempotency)
    // or to another order's known key (redirecting to a stranger's tracker).
    #[Locked]
    public ?string $idempotencyKey = null;

    public $locale = 'en';

    // Whether the full-screen language gate should block the menu.
    // True only when the visitor has NOT yet chosen a language this session.
    public bool $showLanguageGate = false;

    public string $screen = 'home';

    public ?string $tableLabel = null;

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

        // Show the language gate only when the visitor has not yet chosen a
        // language this session. The rendering default above ('en' / shop
        // default) is distinct from an explicit choice stored under 'guest_locale'.
        $this->showLanguageGate = ! session()->has('guest_locale');

        $table = request()->query('table');
        if (is_scalar($table)) {
            $this->tableLabel = Str::of((string) $table)
                ->squish()
                ->limit(20, '')
                ->toString() ?: null;
        }

        $this->screen = request()->query('view') === 'menu' ? 'full_menu' : 'home';

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

        // The <html dir> attribute is set by SetLocale middleware on full page
        // loads only. Livewire updates are AJAX partials, so push the new
        // direction to the browser immediately to keep RTL/LTR layout in sync.
        $this->dispatch('guest-locale-changed', direction: $lang === 'ar' ? 'rtl' : 'ltr');
    }

    /**
     * Handle a language pick from the full-screen gate: persist the choice via
     * the existing switchLanguage() and dismiss the gate so the menu is shown.
     */
    public function chooseLanguage(string $lang): void
    {
        $this->switchLanguage($lang);
        $this->showLanguageGate = false;
    }

    public function showFullMenu(): void
    {
        $this->screen = 'full_menu';
        $this->dispatch('guest-screen-changed', screen: 'full_menu');
    }

    public function showHome(): void
    {
        $this->screen = 'home';
        $this->dispatch('guest-screen-changed', screen: 'home');
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

        // Mint a per-checkout idempotency token when the review sheet opens so
        // every submit attempt for this checkout (including a double-click) is
        // tied to one token. Only mint if absent, so re-opening the sheet does
        // not reset a token already in flight.
        if ($this->showReviewModal && $this->idempotencyKey === null) {
            $this->idempotencyKey = (string) Str::uuid();
        }
    }

    public function updatedVoucherCode(mixed $value): void
    {
        $this->voucherCode = Str::upper(mb_substr((string) $value, 0, 40));
        $this->voucherApplied = false;
    }

    public function applyVoucher(): void
    {
        $this->voucherCode = Str::upper(trim($this->voucherCode));
        $this->voucherApplied = $this->voucherCode !== '';
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

    /**
     * Open the product-detail sheet for ANY product, with or without modifiers
     * (Phase 7e, #23-followup). The sheet is the only place the per-item note
     * textarea lives, so on a modifier-less bakery menu it must still be
     * reachable for allergen-safety requests. Tapping a product card calls this;
     * the '+' button stays a quick-add via addToCart().
     *
     * The product is loaded shop-scoped + orderable() so a foreign or
     * unavailable product id can never populate the sheet (tenant isolation).
     */
    public function openProductSheet(int $productId): void
    {
        $product = $this->shop->products()
            ->with('modifierGroups.options')
            ->orderable()
            ->find($productId);

        if (! $product) {
            return;
        }

        $this->customizingProduct = $product;
        $this->selectedModifiers = [];
        $this->itemNote = '';
        $this->modifierError = null;
        $this->showModifierModal = true;
    }

    public function addToCart($productId)
    {
        if (! $this->ensureGroupCartValid()) {
            return;
        }

        $product = $this->shop->products()
            ->with('modifierGroups.options')
            ->orderable()
            ->find($productId);

        if (! $product) {
            return;
        }

        // If product has modifiers and we haven't opened the modal yet
        if ($product->modifierGroups->isNotEmpty() && ! $this->showModifierModal) {
            $this->customizingProduct = $product;
            $this->selectedModifiers = [];
            $this->itemNote = '';
            $this->modifierError = null;
            $this->showModifierModal = true;

            return;
        }

        $selectedByGroup = $this->normalizeModifierGroups($this->selectedModifiers);
        if ($product->modifierGroups->isNotEmpty() || ! empty($selectedByGroup)) {
            $modifierError = $this->validateSelectedModifierGroups($product, $selectedByGroup);
            if ($modifierError) {
                $this->modifierError = $modifierError;
                $this->showModifierModal = true;

                return;
            }
        }

        $modifierIds = $this->normalizeModifierIds($this->selectedModifiers);

        // Sanitize the untrusted note before it enters cart state.
        $note = $this->sanitizeNote($this->itemNote);

        // Generate a unique key for the cart (product_id + sorted modifiers +
        // note). Two otherwise-identical lines with different notes must stay
        // separate so a per-line allergen request is never merged away.
        $modifierKey = ! empty($modifierIds) ? implode('-', collect($modifierIds)->sort()->toArray()) : 'plain';
        $itemKey = $productId.'-'.$modifierKey;
        if ($note !== null) {
            $itemKey .= '-n'.substr(md5($note), 0, 8);
        }

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
                    'note' => $note,
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
                    'note' => $note,
                ];
            }
        }

        // Reset customization state
        $this->showModifierModal = false;
        $this->customizingProduct = null;
        $this->selectedModifiers = [];
        $this->itemNote = '';
        $this->modifierError = null;
    }

    /**
     * Trim and cap an untrusted guest item note. Returns null for blank input
     * so the column stays NULL rather than an empty string.
     */
    protected function sanitizeNote($value): ?string
    {
        $note = trim((string) $value);
        if ($note === '') {
            return null;
        }

        return mb_substr($note, 0, 255);
    }

    /**
     * Trim and cap the untrusted order-level note. Returns null for blank input
     * so the column stays NULL rather than an empty string. Capped at 500 to
     * match the DB text column's practical use (longer than an item note since
     * it can carry several instructions for the whole order).
     */
    protected function sanitizeOrderNote($value): ?string
    {
        $note = trim((string) $value);
        if ($note === '') {
            return null;
        }

        return mb_substr($note, 0, 500);
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
            $this->orderError = __('guest.rate_limit_error');
            $this->showReviewModal = true;

            return;
        }
        RateLimiter::hit($rateLimitKey, 900);

        $this->orderError = null;
        $this->loyaltyError = null;

        // Idempotency (Phase 7a, #28). Ensure a token exists even if submit is
        // reached without the review sheet having opened (defence in depth), so
        // every order created here is tied to a UNIQUE key.
        if ($this->idempotencyKey === null) {
            $this->idempotencyKey = (string) Str::uuid();
        }

        // If this exact token already produced an order (double-click, network
        // retry, replayed request), short-circuit to that order's tracker
        // instead of creating a duplicate. Scoped to this shop.
        $existing = Order::where('shop_id', $this->shop->id)
            ->where('idempotency_key', $this->idempotencyKey)
            ->first();
        if ($existing) {
            return $this->redirectToOrder($existing);
        }

        // Caps (Phase 7a, #28): reject oversized / abusive carts early, before
        // any order is created. Treat the cart as untrusted (the group-cart JSON
        // especially). The total cap is checked later, once the server-side
        // re-priced total is known.
        if (! $this->passesQuantityAndLineCaps($cartItems)) {
            $this->orderError = __('guest.cart_too_large');
            $this->showReviewModal = true;

            return;
        }

        // Fetch fresh product data to prevent price tampering and verify availability
        $productIds = collect($cartItems)->pluck('id')->unique()->toArray();
        $products = $this->shop->products()
            ->with('modifierGroups.options')
            ->orderable()
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

        // Pay-at-counter checkout (Phase 4, #24): name + phone are required.
        // Runs after the availability/price-integrity guard above so a stale or
        // tampered cart is always caught, regardless of contact entry.
        $customerName = trim((string) $this->customerName);
        if ($customerName === '') {
            $this->orderError = __('guest.name_required');
            $this->showReviewModal = true;

            return;
        }
        $customerName = mb_substr($customerName, 0, 255);

        // Phone is required and validated through the existing loyalty regex.
        $loyaltyPhone = $this->normalizePhone($this->loyaltyPhone);
        if (! $loyaltyPhone) {
            $this->loyaltyError = trim((string) $this->loyaltyPhone) === ''
                ? __('guest.phone_required')
                : __('guest.invalid_phone');
            $this->showReviewModal = true;

            return;
        }

        $paymentMethod = $this->normalizePaymentMethod($this->paymentMethod);
        if ($paymentMethod === null) {
            $this->orderError = __('guest.payment_method_invalid');
            $this->showReviewModal = true;

            return;
        }

        $built = $this->buildOrderItems($cartItems, $products);
        if ($built['error'] !== null) {
            $this->orderError = $built['error'];
            $this->showReviewModal = true;

            return;
        }

        $orderItems = $built['items'];
        $subtotalAmount = $built['subtotal'];
        $taxAmount = $built['tax'];

        if (empty($orderItems)) {
            return;
        }

        // Total cap (Phase 7a, #28): use the server-side re-priced total, never
        // the client-sent prices, so a tampered cart cannot slip past.
        $totalAmount = round($subtotalAmount + $taxAmount, 3);
        if ($totalAmount > (float) config('ordering.max_order_total', 1000)) {
            $this->orderError = __('guest.order_total_too_high');
            $this->showReviewModal = true;

            return;
        }

        // Sanitize the untrusted order-level note before it touches the DB.
        $orderNote = $this->sanitizeOrderNote($this->orderNote);

        $persisted = $this->persistOrder($orderItems, $subtotalAmount, $taxAmount, $totalAmount, $customerName, $loyaltyPhone, $orderNote, $paymentMethod);

        // Race-replay: a concurrent request with the same token won the UNIQUE
        // index. Redirect to that order without re-running side effects — the
        // winning request already did them.
        if (! $persisted['created']) {
            return $this->redirectToOrder($persisted['order']);
        }

        $this->finalizeOrderState($persisted['order'], $cartItems, $loyaltyPhone);

        return $this->redirectToOrder($persisted['order']);
    }

    /**
     * Persist the order and its items inside a transaction. On a unique-key
     * race (a concurrent request with the same idempotency token inserted
     * first) the existing order is returned with `created => false` so the
     * caller redirects without re-running side effects.
     *
     * @return array{order: Order, created: bool}
     */
    protected function persistOrder(array $orderItems, float $subtotalAmount, float $taxAmount, float $totalAmount, string $customerName, ?string $loyaltyPhone, ?string $orderNote, string $paymentMethod): array
    {
        $idempotencyKey = $this->idempotencyKey;

        try {
            $order = DB::transaction(function () use ($subtotalAmount, $taxAmount, $totalAmount, $loyaltyPhone, $customerName, $orderNote, $orderItems, $idempotencyKey, $paymentMethod) {
                $order = Order::forceCreate([
                    'shop_id' => $this->shop->id,
                    'status' => 'unpaid',
                    'customer_name' => $customerName,
                    'loyalty_phone' => $loyaltyPhone,
                    'payment_method' => $paymentMethod,
                    'order_note' => $orderNote,
                    'subtotal_amount' => $subtotalAmount,
                    'tax_amount' => round($taxAmount, 3),
                    'total_amount' => $totalAmount,
                    'tracking_token' => (string) Str::uuid(),
                    'idempotency_key' => $idempotencyKey,
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
                        'note' => $item['note'],
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
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Race: a concurrent request with the same token inserted first and
            // won the UNIQUE index. Return that order rather than erroring or
            // duplicating. Scoped to this shop.
            $existing = Order::where('shop_id', $this->shop->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                return ['order' => $existing, 'created' => false];
            }

            throw $e;
        }

        return ['order' => $order, 'created' => true];
    }

    protected function normalizePaymentMethod(mixed $method): ?string
    {
        $method = trim((string) $method);

        return in_array($method, ['counter', 'online'], true) ? $method : null;
    }

    /**
     * Re-price the untrusted cart server-side and assemble the persisted order
     * items. Prices are recomputed from fresh product/modifier data (never the
     * client-sent values) and modifier ids are validated. Returns an `error`
     * string on the first invalid line so the caller can reject before any
     * order is created; otherwise returns the items and the re-priced totals.
     *
     * @param  array  $cartItems  Untrusted cart lines (POS or group JSON).
     * @param  \Illuminate\Support\Collection  $products  Fresh products keyed by id.
     * @return array{error: ?string, items: array, subtotal: float, tax: float}
     */
    protected function buildOrderItems(array $cartItems, $products): array
    {
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
            $modifierError = $this->validateCartModifierIds($product, $modifierIds);
            if ($modifierError) {
                return ['error' => $modifierError, 'items' => [], 'subtotal' => 0, 'tax' => 0];
            }

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
                'note' => $this->sanitizeNote($item['note'] ?? null),
                'modifiers' => $modifiersData,
            ];
        }

        return [
            'error' => null,
            'items' => $orderItems,
            'subtotal' => $subtotalAmount,
            'tax' => $taxAmount,
        ];
    }

    /**
     * Post-transaction side effects and state reset after an order is created:
     * save favorites, fire the WhatsApp notification, tear down the group cart,
     * clear the checkout fields, and regenerate the idempotency token so the
     * next checkout is distinct.
     */
    protected function finalizeOrderState(Order $order, array $cartItems, ?string $loyaltyPhone): void
    {
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
        $this->customerName = '';
        $this->paymentMethod = 'counter';
        $this->voucherCode = '';
        $this->voucherApplied = false;
        $this->orderNote = '';
        $this->loyaltyPhone = '';
        $this->loyaltyError = null;
        $this->recognizedCustomer = null;
        $this->showWelcomeBack = false;

        // Regenerate the idempotency token so a fresh checkout creates a new,
        // distinct order rather than colliding with the one just placed.
        $this->idempotencyKey = (string) Str::uuid();
    }

    /**
     * Redirect the guest to an order's public tracker. Centralised so the
     * happy path and both idempotent-replay paths behave identically.
     */
    protected function redirectToOrder(Order $order)
    {
        return $this->redirect(route('guest.track', $order->tracking_token), navigate: true);
    }

    /**
     * Enforce the per-line quantity cap and the distinct-line-count cap on an
     * untrusted cart (Phase 7a, #28). The total cap is enforced separately
     * against the server-side re-priced total. Returns false if either cap is
     * exceeded so the caller can reject before any order is created.
     */
    protected function passesQuantityAndLineCaps(array $cartItems): bool
    {
        $maxLines = (int) config('ordering.max_lines_per_order', 50);
        if (count($cartItems) > $maxLines) {
            return false;
        }

        $maxQty = (int) config('ordering.max_quantity_per_line', 99);
        foreach ($cartItems as $item) {
            if ((int) ($item['quantity'] ?? 0) > $maxQty) {
                return false;
            }
        }

        return true;
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
            ->orderable()
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

    protected function validateSelectedModifierGroups(Product $product, array $selectedByGroup): ?string
    {
        $groups = $product->modifierGroups->keyBy('id');

        foreach ($selectedByGroup as $groupId => $selectedIds) {
            $group = $groups->get((int) $groupId);
            if (! $group) {
                return __('guest.invalid_modifier_selection');
            }

            $selectedIds = $this->normalizeModifierIds($selectedIds);
            if ($this->hasDuplicateModifierIds($selectedIds)) {
                return __('guest.invalid_modifier_selection');
            }

            $allowedIds = $group->options
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (collect($selectedIds)->diff($allowedIds)->isNotEmpty()) {
                return __('guest.invalid_modifier_selection');
            }
        }

        foreach ($groups as $group) {
            $selectedIds = $this->normalizeModifierIds($selectedByGroup[$group->id] ?? []);
            $count = count($selectedIds);

            if ($group->min_selection > 0 && $count < $group->min_selection) {
                return __('guest.select_at_least', [
                    'count' => $group->min_selection,
                    'group' => $group->translated('name'),
                ]);
            }

            if ($group->max_selection > 0 && $count > $group->max_selection) {
                return __('guest.select_at_most', [
                    'count' => $group->max_selection,
                    'group' => $group->translated('name'),
                ]);
            }
        }

        return null;
    }

    protected function validateCartModifierIds(Product $product, array $modifierIds): ?string
    {
        if ($this->hasDuplicateModifierIds($modifierIds)) {
            return __('guest.invalid_modifier_selection');
        }

        $groups = $product->modifierGroups;
        $allowedIds = $groups
            ->pluck('options')
            ->flatten()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (collect($modifierIds)->diff($allowedIds)->isNotEmpty()) {
            return __('guest.invalid_modifier_selection');
        }

        foreach ($groups as $group) {
            $groupOptionIds = $group->options
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $count = collect($modifierIds)
                ->intersect($groupOptionIds)
                ->count();

            if ($group->min_selection > 0 && $count < $group->min_selection) {
                return __('guest.select_at_least', [
                    'count' => $group->min_selection,
                    'group' => $group->translated('name'),
                ]);
            }

            if ($group->max_selection > 0 && $count > $group->max_selection) {
                return __('guest.select_at_most', [
                    'count' => $group->max_selection,
                    'group' => $group->translated('name'),
                ]);
            }
        }

        return null;
    }

    protected function hasDuplicateModifierIds(array $modifierIds): bool
    {
        return count($modifierIds) !== count(array_unique($modifierIds));
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
                $query->visible()
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

        $popularProducts = $this->buildPopularProducts($categories);

        return view('livewire.guest-menu', [
            'categories' => $categories,
            'popularProducts' => $popularProducts,
            'searchNames' => $this->buildSearchNames($categories),
            'locale' => $this->locale,
            'isRtl' => $this->locale === 'ar',
            'groupCartItems' => $groupCartItems,
            'participantColors' => $participantColors,
            'pricingRules' => $pricingRules,
            'theme' => $theme,
        ])->layout('layouts.app', ['shop' => $this->shop]);
    }

    /**
     * Lowercased "name + description" search string per product, keyed by id.
     *
     * Computed once per render so the client-side search index (the <main>
     * allNames list, each section's names list, and every card's data-name
     * attribute) reads from one map instead of re-resolving translated() and
     * lowercasing the same product three times in the view.
     */
    protected function buildSearchNames(\Illuminate\Support\Collection $categories): array
    {
        return $categories
            ->flatMap(fn ($category) => $category->products)
            ->mapWithKeys(fn ($product) => [
                $product->id => Str::lower(
                    $product->translated('name').' '.($product->translated('description') ?? '')
                ),
            ])
            ->all();
    }

    /**
     * Owner-highlighted "Popular today" rail (mockup screen 2/2b/3).
     *
     * There is no dedicated featured flag on products, so we derive the rail
     * from already-loaded, shop-scoped categories: on-sale items first (the
     * owner's active promotions), then the leading orderable items by
     * sort order. Sold-out items are excluded from the rail.
     */
    protected function buildPopularProducts(\Illuminate\Support\Collection $categories): \Illuminate\Support\Collection
    {
        $orderable = $categories
            ->flatMap(fn ($category) => $category->products)
            ->filter(fn ($product) => $product->is_available);

        $onSale = $orderable->filter(fn ($product) => $product->is_on_sale);
        $rest = $orderable->reject(fn ($product) => $product->is_on_sale);

        return $onSale->concat($rest)
            ->unique('id')
            ->take(8)
            ->values();
    }
}
