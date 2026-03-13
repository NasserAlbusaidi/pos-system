<?php

namespace App\Livewire\Guest;

use App\Models\Order;
use App\Models\Shop;
use Livewire\Component;

class OrderTracker extends Component
{
    public Order $order;

    public Shop $shop;

    public function mount(string $trackingToken)
    {
        $this->order = Order::where('tracking_token', $trackingToken)->firstOrFail();
        $this->shop = $this->order->shop;
    }

    public function render()
    {
        $this->order->refresh();

        return view('livewire.guest.order-tracker')
            ->layout('layouts.app', ['shop' => $this->shop]);
    }
}
