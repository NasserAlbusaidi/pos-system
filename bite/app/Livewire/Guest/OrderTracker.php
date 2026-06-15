<?php

namespace App\Livewire\Guest;

use App\Models\Order;
use App\Models\Shop;
use App\Support\BrandingUrl;
use Livewire\Component;

class OrderTracker extends Component
{
    public Order $order;

    public Shop $shop;

    public int $rating = 0;

    public string $feedbackComment = '';

    public bool $feedbackSubmitted = false;

    /**
     * Customer-safe visual timeline steps. Internal order statuses are mapped
     * to friendly, non-alarming customer copy — e.g. 'unpaid' is framed as
     * "Order received / awaiting confirmation", never surfaced as a scary word.
     *
     * @var list<string>
     */
    public const TIMELINE_STEPS = ['received', 'accepted', 'preparing', 'ready'];

    /**
     * Maps an internal order status to the index of the timeline step that is
     * currently "active" (the now-step). 'completed' marks every step done.
     *
     * @var array<string, int>
     */
    private const STATUS_STEP_INDEX = [
        'unpaid' => 0,
        'paid' => 1,
        'preparing' => 2,
        'ready' => 3,
    ];

    public function mount(string $trackingToken): void
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

        // Re-resolve by token so the write is always scoped to this one order
        // (the public route is keyed by the UUID token only) and idempotent.
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

    /**
     * Per-step visual state for the timeline: 'done' | 'now' | 'pending'.
     * Cancelled orders short-circuit to a distinct cancelled state in the view.
     *
     * @return array<int, string>
     */
    public function timelineState(): array
    {
        $status = $this->order->status;

        if ($status === 'completed') {
            return array_fill(0, count(self::TIMELINE_STEPS), 'done');
        }

        $activeIndex = self::STATUS_STEP_INDEX[$status] ?? null;

        return array_map(function (int $index) use ($activeIndex): string {
            if ($activeIndex === null) {
                return 'pending';
            }

            return match (true) {
                $index < $activeIndex => 'done',
                $index === $activeIndex => 'now',
                default => 'pending',
            };
        }, array_keys(self::TIMELINE_STEPS));
    }

    /**
     * Sanitized shop-supplied review/social links. Returns null when the
     * branding value is absent or carries an unsafe scheme so the view can
     * hide the link entirely. Trust boundary for guest-rendered hrefs.
     */
    public function googleReviewUrl(): ?string
    {
        return BrandingUrl::safe(($this->shop->branding ?? [])['google_review_url'] ?? null);
    }

    public function instagramUrl(): ?string
    {
        return BrandingUrl::safe(($this->shop->branding ?? [])['instagram_url'] ?? null);
    }

    public function render()
    {
        $this->order->refresh();

        return view('livewire.guest.order-tracker', [
            'timelineState' => $this->timelineState(),
            'googleReviewUrl' => $this->googleReviewUrl(),
            'instagramUrl' => $this->instagramUrl(),
        ])->layout('layouts.app', ['shop' => $this->shop]);
    }
}
