<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PrintNodeService
{
    public function printOrder(Order $order, string $type = 'kitchen'): bool
    {
        if (! config('printnode.enabled')) {
            return false;
        }

        $apiKey = config('printnode.api_key');
        $printerId = config('printnode.default_printer_id');

        if (! $apiKey || ! $printerId) {
            Log::warning('PrintNode is enabled but missing API key or printer ID.');

            return false;
        }

        $payload = [
            'printerId' => (int) $printerId,
            'title' => strtoupper($type).' ORDER #'.$order->id,
            'contentType' => 'raw_base64',
            'content' => base64_encode($this->buildTicket($order, $type)),
            'source' => 'Bite POS',
        ];

        $response = Http::withBasicAuth($apiKey, '')
            ->post(rtrim(config('printnode.endpoint'), '/').'/printjobs', $payload);

        if (! $response->successful()) {
            Log::error('PrintNode error', ['response' => $response->body()]);
        }

        return $response->successful();
    }

    protected function buildTicket(Order $order, string $type): string
    {
        $lines = [];
        $lines[] = 'BITE POS';
        $lines[] = strtoupper($type).' TICKET';
        $lines[] = 'Order #'.$order->id;
        $lines[] = 'Order Type: Guest Pickup';
        $lines[] = '-------------------------';

        $order->loadMissing('items.modifiers', 'shop');

        foreach ($order->items as $item) {
            $lines[] = $item->quantity.'x '.$item->product_name_snapshot_en;
            if ($item->modifiers->isNotEmpty()) {
                foreach ($item->modifiers as $modifier) {
                    $lines[] = '  + '.$modifier->modifier_option_name_snapshot_en;
                }
            }
        }

        $lines[] = '-------------------------';
        $lines[] = 'Total: '.formatPrice($order->total_amount, $order->shop);
        $lines[] = now()->format('Y-m-d H:i');

        return implode("\n", $lines)."\n";
    }
}
