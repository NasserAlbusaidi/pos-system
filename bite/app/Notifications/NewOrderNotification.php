<?php

namespace App\Notifications;

use App\Models\Order;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewOrderNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Order $order
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $shop = $notifiable;
        $whatsappService = app(WhatsAppService::class);

        return [
            'order_id' => $this->order->id,
            'total' => (float) $this->order->total_amount,
            'item_count' => $this->order->items()->sum('quantity'),
            'whatsapp_link' => $whatsappService->buildOrderLink($shop, $this->order),
        ];
    }
}
