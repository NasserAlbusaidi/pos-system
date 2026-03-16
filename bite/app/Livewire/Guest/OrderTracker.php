<?php

namespace App\Livewire\Guest;

use App\Models\Order;
use App\Models\Shop;
use Livewire\Component;

class OrderTracker extends Component
{
    public Order $order;

    public Shop $shop;

    public int $rating = 0;

    public string $feedbackComment = '';

    public bool $feedbackSubmitted = false;

    public function mount(string $trackingToken)
    {
        $this->order = Order::where('tracking_token', $trackingToken)->firstOrFail();
        $this->shop = $this->order->shop;
        $this->feedbackSubmitted = ! empty($this->order->customer_rating);
    }

    public function submitFeedback(): void
    {
        if ($this->rating < 1 || $this->rating > 5) {
            return;
        }

        $comment = mb_substr(trim($this->feedbackComment), 0, 500);

        $this->order->update([
            'customer_rating' => $this->rating,
            'customer_feedback' => $comment ?: null,
        ]);

        $this->feedbackSubmitted = true;
    }

    public function render()
    {
        $this->order->refresh();

        return view('livewire.guest.order-tracker')
            ->layout('layouts.app', ['shop' => $this->shop]);
    }
}
