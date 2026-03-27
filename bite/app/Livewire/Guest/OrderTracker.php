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
        // SEC-03: Validate inputs before mutation to prevent invalid data and stored XSS.
        $this->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'feedbackComment' => ['nullable', 'string', 'max:500'],
        ]);

        if ($this->order->customer_rating !== null) {
            return;
        }

        // Sanitize comment to prevent stored XSS — strip any HTML/script tags.
        $comment = strip_tags(mb_substr(trim($this->feedbackComment), 0, 500));

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
