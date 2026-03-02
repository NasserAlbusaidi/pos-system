<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Shop;

class WhatsAppService
{
    /**
     * Check if WhatsApp notifications are enabled for a shop.
     */
    public function isEnabled(Shop $shop): bool
    {
        $branding = $shop->branding ?? [];

        return ! empty($branding['whatsapp_notifications_enabled'])
            && ! empty($branding['whatsapp_number']);
    }

    /**
     * Get the normalized WhatsApp number for a shop.
     */
    public function getNumber(Shop $shop): ?string
    {
        $branding = $shop->branding ?? [];
        $number = $branding['whatsapp_number'] ?? null;

        if (! $number) {
            return null;
        }

        // Strip everything except digits and leading +
        return preg_replace('/[^0-9]/', '', (string) $number);
    }

    /**
     * Build a wa.me deep link for a given order.
     */
    public function buildOrderLink(Shop $shop, Order $order): ?string
    {
        $number = $this->getNumber($shop);

        if (! $number) {
            return null;
        }

        $itemCount = $order->items()->sum('quantity');
        $total = number_format((float) $order->total_amount, $shop->currency_decimals ?? 3, '.', '');
        $currency = $shop->currency_code ?? 'OMR';

        $message = "New Order #{$order->id}\n"
            ."Items: {$itemCount}\n"
            ."Total: {$currency} {$total}\n"
            ."Shop: {$shop->name}";

        return 'https://wa.me/'.$number.'?text='.urlencode($message);
    }
}
