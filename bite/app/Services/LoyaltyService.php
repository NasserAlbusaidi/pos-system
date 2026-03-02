<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\LoyaltyCustomer;
use App\Models\Order;

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

        $customer = LoyaltyCustomer::firstOrNew([
            'shop_id' => $order->shop_id,
            'phone' => $phone,
        ]);

        $customer->points = (int) ($customer->points ?? 0) + $points;
        $customer->save();

        AuditLog::record('loyalty.awarded', $order, [
            'phone' => $phone,
            'points' => $points,
            'total_points' => $customer->points,
        ]);
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
