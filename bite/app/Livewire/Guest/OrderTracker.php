<?php

namespace App\Livewire\Guest;

use App\Models\Order;
use App\Models\Shop;
use Illuminate\Support\Facades\App;
use Livewire\Component;

class OrderTracker extends Component
{
    public Order $order;

    public Shop $shop;

    public function mount(string $trackingToken)
    {
        $this->order = Order::where('tracking_token', $trackingToken)->firstOrFail();
        $this->shop = $this->order->shop;

        // Set locale based on session or shop default
        $branding = $this->shop->branding ?? [];
        $locale = session('guest_locale', $branding['language'] ?? 'en');
        App::setLocale($locale);
    }

    public function render()
    {
        $this->order->refresh();

        // Ensure locale is set on every render
        $branding = $this->shop->branding ?? [];
        $locale = session('guest_locale', $branding['language'] ?? 'en');
        App::setLocale($locale);

        return view('livewire.guest.order-tracker')
            ->layout('layouts.app', ['shop' => $this->shop]);
    }
}
