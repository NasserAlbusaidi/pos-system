{{-- Sticky green cart CTA (prototype web-cart-cta). Rendered on both the home and
     full-menu screens whenever the cart has items (solo or group). Opens the review
     sheet via toggleReview(). Replaces the two earlier dark bottom bars so there is
     a single, consistent cart affordance. Expects: $shop, $this->cartItemCount,
     $this->total. --}}
@if($this->cartItemCount > 0)
    <button type="button" wire:click="toggleReview" class="guest-cta">
        <span class="guest-cta__count">{{ trans_choice('guest.items_count', $this->cartItemCount, ['count' => $this->cartItemCount]) }}</span>
        <strong class="guest-cta__label">{{ __('guest.view_cart') }} · <x-price :amount="$this->total" :shop="$shop" /></strong>
    </button>
@endif
