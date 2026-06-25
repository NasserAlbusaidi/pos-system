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

        if ($this->loyaltyAlreadyAwarded($order)) {
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

    public function reverseAwardForRefundedOrder(Order $order): void
    {
        $phone = $this->normalizePhone($order->loyalty_phone);
        if (! $phone) {
            return;
        }

        if ($this->loyaltyAlreadyReversed($order)) {
            return;
        }

        $points = (int) AuditLog::where('auditable_type', Order::class)
            ->where('auditable_id', $order->id)
            ->where('action', 'loyalty.awarded')
            ->get()
            ->sum(fn (AuditLog $log): int => (int) ($log->meta['points'] ?? 0));

        if ($points <= 0) {
            return;
        }

        $customer = LoyaltyCustomer::where('shop_id', $order->shop_id)
            ->where('phone', $phone)
            ->first();

        if (! $customer) {
            return;
        }

        $previousPoints = (int) $customer->points;
        $pointsReversed = min($points, $previousPoints);
        $customer->points = max(0, $previousPoints - $points);

        if ((int) ($customer->visit_count ?? 0) > 0) {
            $customer->visit_count = (int) $customer->visit_count - 1;
        }

        $customer->save();

        AuditLog::record('loyalty.reversed', $order, [
            'phone' => $phone,
            'points' => $pointsReversed,
            'awarded_points' => $points,
            'previous_points' => $previousPoints,
            'total_points' => (int) $customer->points,
        ]);
    }

    public function rememberFavorites(?string $phone, int $shopId, ?string $name, array $cartItems): ?LoyaltyCustomer
    {
        $normalized = $this->normalizePhone($phone);
        if (! $normalized) {
            return null;
        }

        $customer = LoyaltyCustomer::firstOrCreate(
            [
                'shop_id' => $shopId,
                'phone' => $normalized,
            ],
            [
                'name' => $this->cleanName($name),
            ],
        );

        if (blank($customer->name) && filled($name)) {
            $customer->name = $this->cleanName($name);
            $customer->save();
        }

        return $customer->saveFavorites($cartItems);
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
            ->whereIn('status', Order::REVENUE_STATUSES)
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

    protected function cleanName(?string $value): ?string
    {
        $name = trim((string) $value);

        return $name === '' ? null : mb_substr($name, 0, 255);
    }

    private function loyaltyAlreadyReversed(Order $order): bool
    {
        return AuditLog::where('auditable_type', Order::class)
            ->where('auditable_id', $order->id)
            ->where('action', 'loyalty.reversed')
            ->exists();
    }

    private function loyaltyAlreadyAwarded(Order $order): bool
    {
        return AuditLog::where('auditable_type', Order::class)
            ->where('auditable_id', $order->id)
            ->where('action', 'loyalty.awarded')
            ->exists();
    }
}
