<?php

namespace App\Http\Controllers\Api\Guest;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Guest\QuoteOrderRequest;
use App\Http\Requests\Api\Guest\StoreOrderRequest;
use App\Http\Resources\Guest\OrderStatusResource;
use App\Models\Order;
use App\Models\Shop;
use App\Services\BillingService;
use App\Services\GuestOrderService;
use Illuminate\Http\JsonResponse;

/**
 * Public JSON API for the guest ordering flow (#51). Thin HTTP adapter over
 * GuestOrderService — the same stateless core the Livewire guest menu uses, so
 * pricing, modifier validation, caps and idempotency are identical across both
 * surfaces. No auth: these are guest endpoints (throttled by IP in the routes).
 * The tracking_token is the bearer secret for reading an order.
 */
class GuestOrderController extends Controller
{
    public function __construct(
        private readonly GuestOrderService $service,
        private readonly BillingService $billing,
    ) {}

    /**
     * Re-price an untrusted cart server-side without persisting anything.
     */
    public function quote(QuoteOrderRequest $request): JsonResponse
    {
        $shop = $this->resolveShop($request->validated('shop'));

        $result = $this->service->quote($shop, $request->validated('cart', []));

        return match ($result['outcome']) {
            'ok' => response()->json(['data' => [
                'items' => $this->presentItems($result['items']),
                'subtotal' => round((float) $result['subtotal'], 3),
                'tax' => round((float) $result['tax'], 3),
                'total' => round((float) $result['total'], 3),
            ]]),
            'unavailable' => $this->unavailableResponse($result),
            default => $this->invalidResponse($result),
        };
    }

    /**
     * Create the order. Idempotent on idempotency_key; a duplicate/raced retry
     * returns the existing order with 200 instead of creating a second one.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $shop = $this->resolveShop($request->validated('shop'));

        $result = $this->service->create($shop, $request->validated('cart', []), $request->orderContext());

        return match ($result['outcome']) {
            'created' => $this->orderResource($result['order'])->response()->setStatusCode(201),
            'duplicate', 'raced' => $this->orderResource($result['order'])->response()->setStatusCode(200),
            'unavailable' => $this->unavailableResponse($result),
            'empty' => response()->json(['message' => __('guest.cart_empty_title'), 'field' => 'order'], 422),
            default => $this->invalidResponse($result),
        };
    }

    /**
     * Read a single order by its tracking token (the bearer secret). The route
     * binds {order:tracking_token} and constrains the token to a UUID, so an
     * unknown or malformed token yields a 404.
     */
    public function show(Order $order): OrderStatusResource
    {
        $this->assertShopPubliclyAvailable($order->shop);
        $order->cancelIfExpiredUnpaid();

        return $this->orderResource($order);
    }

    private function resolveShop(string $slug): Shop
    {
        $shop = Shop::where('slug', $slug)->firstOrFail();

        $this->assertShopPubliclyAvailable($shop);

        return $shop;
    }

    private function assertShopPubliclyAvailable(Shop $shop): void
    {
        abort_if($shop->status === 'suspended' || ! $this->billing->isSubscribed($shop), 404);
    }

    private function orderResource(Order $order): OrderStatusResource
    {
        return new OrderStatusResource($order->load('items.modifiers', 'shop'));
    }

    /**
     * Map the service's internal order-item arrays to a customer-facing shape
     * (locale-resolved names, no snapshot column leakage).
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function presentItems(array $items): array
    {
        $locale = app()->getLocale();

        return array_map(fn ($item) => [
            'name' => $locale === 'ar'
                ? ($item['product_name_snapshot_ar'] ?: $item['product_name_snapshot_en'])
                : $item['product_name_snapshot_en'],
            'quantity' => $item['quantity'],
            'unit_price' => round((float) $item['price_snapshot'], 3),
            'note' => $item['note'],
            'modifiers' => array_map(fn ($modifier) => [
                'name' => $locale === 'ar'
                    ? ($modifier['name_ar'] ?: $modifier['name_en'])
                    : $modifier['name_en'],
                'price' => round((float) $modifier['price'], 3),
            ], $item['modifiers']),
        ], $items);
    }

    /**
     * 86'd / hidden items in the cart. 409 with the structured list so the
     * client can prune and re-quote.
     *
     * @param  array<string, mixed>  $result
     */
    private function unavailableResponse(array $result): JsonResponse
    {
        return response()->json([
            'message' => __('guest.items_unavailable_removed', ['items' => implode(', ', $result['unavailable'])]),
            'unavailable' => $result['unavailable'],
            'unavailable_ids' => $result['unavailable_ids'],
        ], 409);
    }

    /**
     * A cart/contact integrity failure (caps, tampered total, bad phone, bad
     * modifier selection). 422 with the field the client should surface.
     *
     * @param  array<string, mixed>  $result
     */
    private function invalidResponse(array $result): JsonResponse
    {
        return response()->json([
            'message' => $result['error'] ?? __('guest.cart_empty_title'),
            'field' => $result['error_field'] ?? 'order',
        ], 422);
    }
}
