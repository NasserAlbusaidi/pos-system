<?php

namespace App\Livewire;

use App\Models\GroupCart;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Notifications\NewOrderNotification;
use App\Services\BillingService;
use App\Services\GuestOrderService;
use App\Services\LoyaltyService;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

class GuestMenu extends Component
{
    public Shop $shop;

    public $cart = [];

    public $loyaltyPhone = '';

    public $loyaltyError = null;

    // Checkout contact (Phase 4, #24). Name + phone are required at checkout
    // for the pay-at-counter pilot. Untrusted: trimmed on submit.
    public $customerName = '';

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

    // Active screen: 'home' (landing — hero, highlight, popular grid) or 'menu'
    // (full browse — search, category tabs, every product). A Livewire property
    // (not Alpine) so the choice survives re-renders triggered by addToCart and
    // friends — otherwise adding an item from the menu would bounce to home.
    public string $screen = 'home';

    // Group ordering state
    public $groupToken = null;

    #[Locked]
    public $participantId = null;

    public $showGroupShareModal = false;

    public function mount(Shop $shop)
    {
        abort_if($shop->status === 'suspended' || ! app(BillingService::class)->isSubscribed($shop), 404);

        $this->shop = $shop;

        // Determine locale: session override > shop default > 'en'
        $branding = $shop->branding ?? [];
        $this->locale = session('guest_locale', $branding['language'] ?? 'en');

        // Show the language gate only when the visitor has not yet chosen a
        // language this session. The rendering default above ('en' / shop
        // default) is distinct from an explicit choice stored under 'guest_locale'.
        $this->showLanguageGate = ! session()->has('guest_locale');

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

    public function updatedLoyaltyPhone(): void
    {
        $this->recognizeCustomer();
    }

    /**
     * Switch to the full browse screen (search + tabs + every product). Wired to
     * the home screen's "See all" control and the popular-grid section header.
     */
    public function showMenu(): void
    {
        $this->screen = 'menu';
    }

    /**
     * Return to the home landing screen (hero + highlight + popular grid).
     */
    public function showHome(): void
    {
        $this->screen = 'home';
    }

    // ──────────────────────────────────
    // Group ordering methods
    // ──────────────────────────────────

    /**
     * Create a new group cart and enter group mode.
     */
    public function createGroup(): void
    {
        $groupCart = GroupCart::forceCreate([
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
            ->with(['modifierGroups.options', 'category'])
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
            ->with(['modifierGroups.options', 'category'])
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
        $displayPrice = max(0.0, round((float) $displayPrice, 3));

        if ($this->isGroupMode) {
            // Group mode: write to GroupCart model
            $groupCart = $this->groupCart;
            if ($groupCart && ! $groupCart->isExpired()) {
                $groupCart->addItem($this->participantId, [
                    'id' => $product->id,
                    'itemKey' => $itemKey,
                    'name' => $product->translated('name'),
                    'image' => productImage($product, 'thumb'),
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
                    'image' => productImage($product, 'thumb'),
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
        return $this->orderService()->sanitizeNote($value);
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

        // The server-side order path (pricing, validation, caps, idempotency,
        // persistence) lives in GuestOrderService so JSON endpoints can reuse
        // it. This component owns only the UI mapping of the outcome.
        $result = $this->orderService()->create($this->shop, $cartItems, [
            'idempotency_key' => $this->idempotencyKey,
            'customer_name' => $this->customerName,
            'loyalty_phone' => $this->loyaltyPhone,
            'order_note' => $this->orderNote,
        ]);

        switch ($result['outcome']) {
            case 'duplicate':
                // Same token already produced an order (double-click / replay).
            case 'raced':
                // A concurrent request with the same token won the UNIQUE index;
                // redirect without re-running side effects — the winner did them.
                return $this->redirectToOrder($result['order']);

            case 'empty':
                return;

            case 'unavailable':
                $this->removeUnavailableItems($result['unavailable_ids']);
                $this->orderError = __('guest.items_unavailable_removed', [
                    'items' => implode(', ', $result['unavailable']),
                ]);
                $this->showReviewModal = true;

                return;

            case 'invalid':
                if (($result['error_field'] ?? 'order') === 'loyalty') {
                    $this->loyaltyError = $result['error'];
                } else {
                    $this->orderError = $result['error'];
                }
                $this->showReviewModal = true;

                return;

            case 'created':
                $this->finalizeOrderState($result['order'], $cartItems, $result['loyalty_phone']);

                return $this->redirectToOrder($result['order']);
        }
    }

    /**
     * Resolve the stateless order service. Resolved per call (not stored) so the
     * component stays serializable across Livewire requests.
     */
    protected function orderService(): GuestOrderService
    {
        return app(GuestOrderService::class);
    }

    /**
     * Prune 86'd / hidden items from the active cart after the service flags
     * them. Group carts are pruned under a row lock; the solo cart in-place.
     *
     * @param  int[]  $unavailableIds
     */
    protected function removeUnavailableItems(array $unavailableIds): void
    {
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

            return;
        }

        $this->cart = collect($this->cart)
            ->filter(fn ($item) => ! in_array($item['id'], $unavailableIds))
            ->all();
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
            app(LoyaltyService::class)->rememberFavorites(
                $loyaltyPhone,
                $this->shop->id,
                $order->customer_name,
                array_values($this->isGroupMode ? $cartItems : $this->cart)
            );
        }

        $this->notifyShopOfNewOrderSafely($order);

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
        $this->orderNote = '';
        $this->loyaltyPhone = '';
        $this->loyaltyError = null;
        $this->recognizedCustomer = null;
        $this->showWelcomeBack = false;

        // Regenerate the idempotency token so a fresh checkout creates a new,
        // distinct order rather than colliding with the one just placed.
        $this->idempotencyKey = (string) Str::uuid();
    }

    protected function notifyShopOfNewOrderSafely(Order $order): void
    {
        try {
            $whatsapp = app(WhatsAppService::class);
            if (! $whatsapp->isEnabled($this->shop)) {
                return;
            }

            $this->shop->notify(new NewOrderNotification($order));
        } catch (Throwable $e) {
            Log::warning('WhatsApp order notification failed after guest checkout.', [
                'order_id' => $order->id,
                'shop_id' => $order->shop_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Redirect the guest to an order's public tracker. Centralised so the
     * happy path and both idempotent-replay paths behave identically.
     */
    protected function redirectToOrder(Order $order)
    {
        return $this->redirect(route('guest.track', $order->tracking_token), navigate: true);
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
        $maxQuantity = max(1, (int) config('ordering.max_quantity_per_line', 99));
        foreach ($items as $item) {
            $product = $products->get($item['id']);
            if (! $product) {
                $removedCount++;

                continue;
            }

            $quantity = min($maxQuantity, max(1, (int) ($item['quantity'] ?? 1)));
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
            $displayPrice = max(0.0, round((float) $displayPrice, 3));

            $modifierKey = ! empty($validModifierIds)
                ? implode('-', collect($validModifierIds)->sort()->toArray())
                : 'plain';
            $itemKey = $product->id.'-'.$modifierKey;

            if (isset($newCart[$itemKey])) {
                $newCart[$itemKey]['quantity'] = min($maxQuantity, $newCart[$itemKey]['quantity'] + $quantity);

                continue;
            }

            $newCart[$itemKey] = [
                'id' => $product->id,
                'name' => $product->translated('name'),
                'image' => productImage($product, 'thumb'),
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
        return $this->orderService()->getValidModifierOptions($product, $modifierIds);
    }

    protected function validateSelectedModifierGroups(Product $product, array $selectedByGroup): ?string
    {
        return $this->orderService()->validateSelectedModifierGroups($product, $selectedByGroup);
    }

    /**
     * Sum price adjustments for valid modifiers on a product.
     */
    protected function sumModifierPrices($product, array $modifierIds): float
    {
        return $this->orderService()->sumModifierPrices($product, $modifierIds);
    }

    protected function normalizeModifierIds($value): array
    {
        return $this->orderService()->normalizeModifierIds($value);
    }

    protected function normalizeModifierGroups($value): array
    {
        return $this->orderService()->normalizeModifierGroups($value);
    }

    /**
     * Load active pricing rules for the current shop.
     * Returns an empty collection if no rules are active right now.
     */
    protected function loadActivePricingRules(): \Illuminate\Support\Collection
    {
        return $this->orderService()->loadActivePricingRules($this->shop);
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

        // Home screen view-model derived from the same popular set (no extra
        // query): the leading item is the owner's "Today's Highlight", the next
        // four fill the popular grid so the highlight is not shown twice.
        $homeHighlight = $popularProducts->first();
        $homeGrid = $popularProducts->slice(1)->take(4)->values();

        return view('livewire.guest-menu', [
            'categories' => $categories,
            'popularProducts' => $popularProducts,
            'homeHighlight' => $homeHighlight,
            'homeGrid' => $homeGrid,
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
