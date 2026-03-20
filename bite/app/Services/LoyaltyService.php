<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\LoyaltyCustomer;
use App\Models\Order;
use Illuminate\Support\Collection;

class LoyaltyService
{
    public function award(Order $order): void
    {
        $phone = $this->normalizePhone($order->loyalty_phone);
        if (! $phone) {
            return;
        }

        $baseAmount = (float) ($order->subtotal_amount ?? $order->total_amount ?? 0);
        $points = (int) floor($baseAmount);
        if ($points <= 0) {
            return;
        }

        $customer = LoyaltyCustomer::firstOrCreate([
            'shop_id' => $order->shop_id,
            'phone' => $phone,
        ]);

        $customer->increment('points', $points);
        $customer->recordVisit();

        AuditLog::record('loyalty.awarded', $order, [
            'phone' => $phone,
            'points' => $points,
            'total_points' => $customer->points,
        ]);
    }

    /**
     * Look up a loyalty customer by normalized phone + shop_id.
     * Returns the customer with history, or null if not found.
     */
    public function recognize(string $phone, int $shopId): ?LoyaltyCustomer
    {
        $normalized = $this->normalizePhone($phone);
        if (! $normalized) {
            return null;
        }

        return LoyaltyCustomer::where('shop_id', $shopId)
            ->where('phone', $normalized)
            ->first();
    }

    /**
     * Return the last N orders for a given phone number at a shop.
     */
    public function getOrderHistory(string $phone, int $shopId, int $limit = 5): Collection
    {
        $normalized = $this->normalizePhone($phone);
        if (! $normalized) {
            return collect();
        }

        return Order::where('shop_id', $shopId)
            ->where('loyalty_phone', $normalized)
            ->whereIn('status', ['paid', 'preparing', 'ready', 'completed'])
            ->with('items')
            ->orderByDesc('created_at')
            ->limit($limit)
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
}
