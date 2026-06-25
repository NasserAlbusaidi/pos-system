<?php

namespace App\Http\Resources\Guest;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Customer-safe JSON view of a guest order. The tracking_token (an unguessable
 * UUID) is the bearer secret that authorizes access, so this is the ONLY shape
 * returned to the public. It deliberately omits internal/PII fields:
 * loyalty_phone, idempotency_key, shop_id, expires_at, internal status, ids.
 *
 * @property \App\Models\Order $resource
 */
class OrderStatusResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'tracking_token' => $this->tracking_token,
            'status' => $this->customerStatus(),
            'source' => $this->source ?? 'guest',
            'customer_name' => $this->customer_name,
            'order_note' => $this->order_note,
            'subtotal' => round((float) $this->subtotal_amount, 3),
            'tax' => round((float) $this->tax_amount, 3),
            'total' => round((float) $this->total_amount, 3),
            'placed_at' => $this->created_at?->toIso8601String(),
            'items' => $this->items->map(fn ($item) => [
                'name' => $item->translated('product_name_snapshot'),
                'quantity' => $item->quantity,
                'unit_price' => round((float) $item->price_snapshot, 3),
                'note' => $item->note,
                'modifiers' => $item->modifiers->map(fn ($modifier) => [
                    'name' => $locale === 'ar'
                        ? ($modifier->modifier_option_name_snapshot_ar ?: $modifier->modifier_option_name_snapshot_en)
                        : $modifier->modifier_option_name_snapshot_en,
                    'price' => round((float) $modifier->price_adjustment_snapshot, 3),
                ])->values(),
            ])->values(),
            'shop' => [
                'name' => $this->shop->name,
                'slug' => $this->shop->slug,
            ],
        ];
    }
}
