<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemModifier;
use App\Models\PricingRule;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Quote + create the guest order from an untrusted cart.
 *
 * Owns the server-side order path that used to live inline in
 * GuestMenu::submitOrder(): shop-scoped product lookup, availability/visibility,
 * qty/line/total caps, modifier validation, re-pricing from fresh data,
 * snapshots, tax/totals, note/phone sanitization, idempotency, and persistence.
 *
 * Stateless: every call takes the shop, the cart, and a context array, so the
 * Livewire component drives it today and JSON endpoints can reuse it later.
 * The component keeps all UI concerns (rate-limiting, group-cart sync, error
 * display, redirects, post-create side effects).
 */
class GuestOrderService
{
    /**
     * Re-price an untrusted cart server-side and return the normalized order
     * lines + totals, WITHOUT creating anything. Used for display/quote and as
     * the shared core of create().
     *
     * Returns one of:
     *   ['outcome' => 'invalid', 'error' => string, 'error_field' => 'order']
     *   ['outcome' => 'unavailable', 'unavailable' => string[], 'unavailable_ids' => int[]]
     *   ['outcome' => 'ok', 'items' => array, 'subtotal' => float, 'tax' => float, 'total' => float]
     *
     * @param  array  $cart  Untrusted cart lines (solo or group JSON).
     */
    public function quote(Shop $shop, array $cart, array $context = []): array
    {
        if (empty($cart)) {
            return ['outcome' => 'ok', 'items' => [], 'subtotal' => 0.0, 'tax' => 0.0, 'total' => 0.0];
        }

        // Caps: reject oversized / abusive carts before any pricing work.
        if (! $this->passesQuantityAndLineCaps($cart)) {
            return ['outcome' => 'invalid', 'error' => __('guest.cart_too_large'), 'error_field' => 'order'];
        }

        $products = $this->fetchOrderableProducts($shop, $cart);

        $unavailable = $this->findUnavailable($cart, $products);
        if (! empty($unavailable['names'])) {
            return [
                'outcome' => 'unavailable',
                'unavailable' => $unavailable['names'],
                'unavailable_ids' => $unavailable['ids'],
            ];
        }

        $built = $this->buildOrderItems($shop, $cart, $products);
        if ($built['error'] !== null) {
            return ['outcome' => 'invalid', 'error' => $built['error'], 'error_field' => 'order'];
        }

        $subtotal = $built['subtotal'];
        $tax = $built['tax'];
        $total = round($subtotal + $tax, 3);

        // Total cap: enforce against the server-side re-priced total, never the
        // client-sent prices, so a tampered cart cannot slip past.
        if ($total > (float) config('ordering.max_order_total', 1000)) {
            return ['outcome' => 'invalid', 'error' => __('guest.order_total_too_high'), 'error_field' => 'order'];
        }

        return [
            'outcome' => 'ok',
            'items' => $built['items'],
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
        ];
    }

    /**
     * Quote the cart and, if valid, persist the order + items + modifiers inside
     * a transaction. Idempotent on `context['idempotency_key']`.
     *
     * Context: idempotency_key (required), customer_name, loyalty_phone, order_note.
     *
     * Returns one of:
     *   ['outcome' => 'duplicate', 'order' => Order]      pre-existing order for this token
     *   ['outcome' => 'raced', 'order' => Order]          concurrent insert won the unique index
     *   ['outcome' => 'empty']                            nothing to order
     *   ['outcome' => 'unavailable', 'unavailable' => string[], 'unavailable_ids' => int[]]
     *   ['outcome' => 'invalid', 'error' => string, 'error_field' => 'order'|'loyalty']
     *   ['outcome' => 'created', 'order' => Order, 'loyalty_phone' => string]
     *
     * @param  array  $cart  Untrusted cart lines (solo or group JSON).
     */
    public function create(Shop $shop, array $cart, array $context = []): array
    {
        if (empty($cart)) {
            return ['outcome' => 'empty'];
        }

        $idempotencyKey = $context['idempotency_key'] ?? (string) Str::uuid();

        // If this exact token already produced an order (double-click, network
        // retry, replayed request), short-circuit to it. Scoped to this shop.
        $existing = Order::where('shop_id', $shop->id)
            ->where('idempotency_key', $idempotencyKey)
            ->first();
        if ($existing) {
            return ['outcome' => 'duplicate', 'order' => $existing];
        }

        // Caps before any pricing work (the group-cart JSON is untrusted).
        if (! $this->passesQuantityAndLineCaps($cart)) {
            return ['outcome' => 'invalid', 'error' => __('guest.cart_too_large'), 'error_field' => 'order'];
        }

        $products = $this->fetchOrderableProducts($shop, $cart);

        // 86'd / hidden items: surface them so the caller can prune the cart.
        $unavailable = $this->findUnavailable($cart, $products);
        if (! empty($unavailable['names'])) {
            return [
                'outcome' => 'unavailable',
                'unavailable' => $unavailable['names'],
                'unavailable_ids' => $unavailable['ids'],
            ];
        }

        // Contact details are required for pay-at-counter (Phase 4, #24). Checked
        // after the availability/price-integrity guard so a stale or tampered cart
        // is always caught regardless of contact entry.
        $customerName = trim((string) ($context['customer_name'] ?? ''));
        if ($customerName === '') {
            return ['outcome' => 'invalid', 'error' => __('guest.name_required'), 'error_field' => 'order'];
        }
        $customerName = mb_substr($customerName, 0, 255);

        $rawPhone = $context['loyalty_phone'] ?? null;
        $loyaltyPhone = $this->normalizePhone($rawPhone);
        if (! $loyaltyPhone) {
            $error = trim((string) $rawPhone) === ''
                ? __('guest.phone_required')
                : __('guest.invalid_phone');

            return ['outcome' => 'invalid', 'error' => $error, 'error_field' => 'loyalty'];
        }

        $built = $this->buildOrderItems($shop, $cart, $products);
        if ($built['error'] !== null) {
            return ['outcome' => 'invalid', 'error' => $built['error'], 'error_field' => 'order'];
        }

        $orderItems = $built['items'];
        if (empty($orderItems)) {
            return ['outcome' => 'empty'];
        }

        $subtotalAmount = $built['subtotal'];
        $taxAmount = $built['tax'];
        $totalAmount = round($subtotalAmount + $taxAmount, 3);

        if ($totalAmount > (float) config('ordering.max_order_total', 1000)) {
            return ['outcome' => 'invalid', 'error' => __('guest.order_total_too_high'), 'error_field' => 'order'];
        }

        $orderNote = $this->sanitizeOrderNote($context['order_note'] ?? null);

        $persisted = $this->persistOrder($shop, $idempotencyKey, $orderItems, $subtotalAmount, $taxAmount, $totalAmount, $customerName, $loyaltyPhone, $orderNote);

        if (! $persisted['created']) {
            return ['outcome' => 'raced', 'order' => $persisted['order']];
        }

        return ['outcome' => 'created', 'order' => $persisted['order'], 'loyalty_phone' => $loyaltyPhone];
    }

    /**
     * Fetch fresh, orderable product data (shop-scoped, with modifiers) for the
     * cart's product ids — the source of truth for re-pricing and availability.
     */
    public function fetchOrderableProducts(Shop $shop, array $cart): Collection
    {
        $productIds = collect($cart)->pluck('id')->unique()->toArray();

        return $shop->products()
            ->with('modifierGroups.options')
            ->orderable()
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');
    }

    /**
     * Identify cart lines whose product is no longer orderable (86'd or hidden).
     *
     * @return array{names: string[], ids: int[]}
     */
    public function findUnavailable(array $cart, Collection $products): array
    {
        $missing = collect($cart)->filter(fn ($item) => ! $products->has($item['id']));

        return [
            'names' => $missing->pluck('name')->unique()->values()->all(),
            'ids' => $missing->pluck('id')->unique()->values()->all(),
        ];
    }

    /**
     * Re-price the untrusted cart server-side and assemble the persisted order
     * items. Prices are recomputed from fresh product/modifier data (never the
     * client-sent values) and modifier ids are validated. Returns an `error`
     * string on the first invalid line; otherwise the items and re-priced totals.
     *
     * @return array{error: ?string, items: array, subtotal: float, tax: float}
     */
    public function buildOrderItems(Shop $shop, array $cart, Collection $products): array
    {
        $pricingRules = $this->loadActivePricingRules($shop);

        $subtotalAmount = 0;
        $taxAmount = 0;
        $orderItems = [];

        foreach ($cart as $item) {
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

            $taxRate = $product->tax_rate ?? $shop->tax_rate ?? 0;
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
     * Persist the order and its items inside a transaction. On a unique-key race
     * (a concurrent request with the same idempotency token inserted first) the
     * existing order is returned with `created => false` so the caller redirects
     * without re-running side effects.
     *
     * @return array{order: Order, created: bool}
     */
    public function persistOrder(Shop $shop, string $idempotencyKey, array $orderItems, float $subtotalAmount, float $taxAmount, float $totalAmount, string $customerName, ?string $loyaltyPhone, ?string $orderNote): array
    {
        try {
            $order = DB::transaction(function () use ($shop, $idempotencyKey, $subtotalAmount, $taxAmount, $totalAmount, $loyaltyPhone, $customerName, $orderNote, $orderItems) {
                $order = Order::forceCreate([
                    'shop_id' => $shop->id,
                    'status' => 'unpaid',
                    'customer_name' => $customerName,
                    'loyalty_phone' => $loyaltyPhone,
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
        } catch (UniqueConstraintViolationException $e) {
            // Race: a concurrent request with the same token inserted first and
            // won the UNIQUE index. Return that order rather than erroring or
            // duplicating. Scoped to this shop.
            $existing = Order::where('shop_id', $shop->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                return ['order' => $existing, 'created' => false];
            }

            throw $e;
        }

        return ['order' => $order, 'created' => true];
    }

    /**
     * Enforce the per-line quantity cap and the distinct-line-count cap on an
     * untrusted cart. The total cap is enforced separately against the re-priced
     * total. Returns false if either cap is exceeded.
     */
    public function passesQuantityAndLineCaps(array $cart): bool
    {
        $maxLines = (int) config('ordering.max_lines_per_order', 50);
        if (count($cart) > $maxLines) {
            return false;
        }

        $maxQty = (int) config('ordering.max_quantity_per_line', 99);
        foreach ($cart as $item) {
            if ((int) ($item['quantity'] ?? 0) > $maxQty) {
                return false;
            }
        }

        return true;
    }

    /**
     * Load active pricing rules for the shop. Empty collection if none active now.
     */
    public function loadActivePricingRules(Shop $shop): Collection
    {
        return PricingRule::where('shop_id', $shop->id)
            ->activeNow()
            ->get();
    }

    public function getValidModifierOptions(Product $product, array $modifierIds): Collection
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
    public function sumModifierPrices(Product $product, array $modifierIds): float
    {
        return (float) $this->getValidModifierOptions($product, $modifierIds)
            ->sum('price_adjustment');
    }

    /**
     * Validate the modifier selection grouped by modifier-group id (modal flow):
     * each group's options must belong to it, no duplicates, and min/max honored.
     */
    public function validateSelectedModifierGroups(Product $product, array $selectedByGroup): ?string
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

    /**
     * Validate a flat list of modifier ids against a product (cart/order flow):
     * all ids must belong to the product, no duplicates, and min/max honored.
     */
    public function validateCartModifierIds(Product $product, array $modifierIds): ?string
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

    public function hasDuplicateModifierIds(array $modifierIds): bool
    {
        return count($modifierIds) !== count(array_unique($modifierIds));
    }

    public function normalizeModifierIds($value): array
    {
        return collect($value)
            ->flatten()
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function normalizeModifierGroups($value): array
    {
        $groups = [];
        foreach ((array) $value as $groupId => $ids) {
            $ids = is_array($ids) ? $ids : [$ids];
            $groups[$groupId] = array_values(array_filter($ids, fn ($id) => $id !== null && $id !== ''));
        }

        return $groups;
    }

    /**
     * Trim and cap an untrusted item note. Returns null for blank input.
     */
    public function sanitizeNote($value): ?string
    {
        $note = trim((string) $value);
        if ($note === '') {
            return null;
        }

        return mb_substr($note, 0, 255);
    }

    /**
     * Trim and cap the untrusted order-level note. Returns null for blank input.
     */
    public function sanitizeOrderNote($value): ?string
    {
        $note = trim((string) $value);
        if ($note === '') {
            return null;
        }

        return mb_substr($note, 0, 500);
    }

    public function normalizePhone(?string $value): ?string
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
}
