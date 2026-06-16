{{-- Order tracking. Public route keyed by the UUID tracking_token; renders only
     this order's customer-safe state and keeps internal statuses out of copy. --}}
@php
    $stepLabels = [
        'received' => __('guest.track_step_received'),
        'accepted' => __('guest.track_step_accepted'),
        'preparing' => __('guest.track_step_preparing'),
        'ready' => __('guest.track_step_ready'),
    ];
    $stepHints = [
        'received' => __('guest.track_step_received_hint'),
        'accepted' => __('guest.track_step_accepted_hint'),
        'preparing' => __('guest.track_step_preparing_hint'),
        'ready' => __('guest.track_step_ready_hint'),
    ];
    $stepIcons = [
        'received' => '<path d="M6.5 8.5h11l-.8 10h-9.4l-.8-10Z"/><path d="M9 8.5a3 3 0 0 1 6 0"/><path d="M9.5 12h5"/>',
        'accepted' => '<path d="m5 12 4 4L19 6"/><path d="M4 20h16"/>',
        'preparing' => '<path d="M7 8h8a3 3 0 0 1 0 6H7V8Z"/><path d="M17 10h1a2 2 0 0 1 0 4h-1"/><path d="M6 18h11"/><path d="M9 4v1.5M13 4v1.5"/>',
        'ready' => '<path d="M7 10h10l-.9 9H7.9L7 10Z"/><path d="M9 10V8a3 3 0 0 1 6 0v2"/><path d="m9.5 14 1.7 1.7L15 12"/>',
    ];
    $steps = \App\Livewire\Guest\OrderTracker::TIMELINE_STEPS;
    $isCancelled = $order->status === 'cancelled';
    $itemCount = (int) $order->items->sum('quantity');
    $codePrefix = strtoupper(substr($shop->slug, 0, 2));
    $shortCode = $codePrefix . '-' . str_pad((string) $order->id, 3, '0', STR_PAD_LEFT);
    $showReview = in_array($order->status, ['ready', 'completed'], true);
    $statusCopyKey = 'guest.status_' . $order->status;
    $statusCopy = __($statusCopyKey);
    $paymentMethod = $order->payment_method === 'online'
        ? __('guest.payment_method_online')
        : __('guest.payment_method_counter');
@endphp

<div class="guest-track" wire:poll.5s>
    <section class="guest-track__phone">
        <div class="guest-statusbar" aria-hidden="true">
            <span>9:41</span>
            <span class="guest-statusbar__icons">
                <svg viewBox="0 0 24 24"><path d="M4 17h2v3H4v-3Zm4-4h2v7H8v-7Zm4-4h2v11h-2V9Zm4-4h2v15h-2V5Z"/></svg>
                <svg viewBox="0 0 24 24"><path d="M12 18.5 7.8 14.3a6 6 0 0 1 8.4 0L12 18.5Z"/><path d="M4.5 11a10.5 10.5 0 0 1 15 0"/><path d="M2 7a14 14 0 0 1 20 0"/></svg>
                <svg viewBox="0 0 30 14"><rect x="1" y="2" width="24" height="10" rx="3"/><path d="M27 5v4"/><rect x="3" y="4" width="20" height="6" rx="2"/></svg>
            </span>
        </div>

        <header class="guest-track-header">
            <a href="{{ route('guest.menu', $shop->slug) }}" class="guest-track-header__back" aria-label="{{ __('guest.back_to_menu') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            </a>
            <h1>{{ __('guest.track_pickup_order') }}</h1>
        </header>

        <main class="guest-track__main">
            <section class="guest-track-hero" aria-label="{{ __('guest.tracking_order', ['id' => $order->id]) }}">
                <div class="guest-track-hero__art">
                    <img src="{{ asset('customer-ordering/assets/hopresso/cup-togo.png') }}" alt="" aria-hidden="true">
                </div>
                <div class="guest-codecard">
                    <p class="guest-codecard__lab">{{ __('guest.track_show_counter') }}</p>
                    <p class="guest-codecard__code">{{ $shortCode }}</p>
                    <p class="guest-codecard__sub">
                        @if($order->customer_name){{ $order->customer_name }} · @endif
                        {{ trans_choice('guest.items_count', $itemCount, ['count' => $itemCount]) }}
                        · <x-price :amount="$order->total_amount" :shop="$shop" />
                    </p>
                </div>
            </section>

            @if($isCancelled)
                <section class="guest-track__cancelled" role="status">
                    <span class="guest-track__cancelled-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </span>
                    <div>
                        <b>{{ __('guest.track_cancelled_title') }}</b>
                        <small>{{ __('guest.status_cancelled') }}</small>
                    </div>
                </section>
            @else
                <section class="guest-status-steps" aria-label="{{ __('guest.status_update') }}">
                    @foreach($steps as $i => $stepKey)
                        @php $state = $timelineState[$i] ?? 'pending'; @endphp
                        <div class="guest-status-step guest-status-step--{{ $state }}">
                            <span class="guest-status-step__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $stepIcons[$stepKey] !!}</svg>
                            </span>
                            <span class="guest-status-step__label">{{ $stepLabels[$stepKey] }}</span>
                        </div>
                        @unless($loop->last)
                            <span class="guest-status-step__rail guest-status-step__rail--{{ in_array($state, ['done', 'now'], true) ? 'active' : 'pending' }}" aria-hidden="true"></span>
                        @endunless
                    @endforeach
                </section>

                <section class="guest-track-message" role="status">
                    <span class="guest-track-message__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4 7v5c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V7l-8-4Z"/><path d="M9 12l2 2 4-4"/></svg>
                    </span>
                    <span>{{ $statusCopy === $statusCopyKey ? __('guest.track_step_received_hint') : $statusCopy }}</span>
                </section>
            @endif

            <div class="guest-track-separator" aria-hidden="true"></div>

            <section class="guest-outlet-card">
                <div class="guest-outlet-card__info">
                    <p class="guest-outlet-card__name">
                        <span aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 10h8a3 3 0 0 1 0 6H7V10Z"/><path d="M17 12h1a2 2 0 0 1 0 4h-1"/><path d="M6 20h12"/></svg>
                        </span>
                        {{ __('guest.track_outlet_label') }} · {{ $shop->name }}
                    </p>
                    <p>{{ __('guest.counter_pickup_hint') }}</p>
                </div>
                <img src="{{ asset('customer-ordering/assets/hopresso/map-square.png') }}" alt="{{ __('guest.track_map_alt') }}">
            </section>

            <div class="guest-track-actions">
                <a href="#order-details" class="guest-track-action">{{ __('guest.track_view_detail_order') }}</a>
            </div>

            <section id="order-details" class="guest-detail-card">
                <div class="guest-detail-card__head">
                    <div>
                        <p>{{ __('guest.track_view_detail_order') }}</p>
                        <h2>{{ __('guest.your_order') }}</h2>
                    </div>
                    <strong><x-price :amount="$order->total_amount" :shop="$shop" /></strong>
                </div>

                @if($order->items->isNotEmpty())
                    <div class="guest-detail-card__items">
                        @foreach($order->items as $item)
                            <div class="guest-detail-item">
                                <span>{{ $item->quantity }}x</span>
                                <b>{{ $item->translated('product_name_snapshot') }}</b>
                                <strong><x-price :amount="((float) $item->price_snapshot) * (int) $item->quantity" :shop="$shop" /></strong>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="guest-detail-card__meta">
                    <span>{{ __('guest.payment_method_label') }}</span>
                    <b>{{ $paymentMethod }}</b>
                </div>
            </section>

            @if($showReview)
                <section class="guest-review">
                    @if($feedbackSubmitted)
                        <div class="guest-review__done">
                            <div class="guest-review__stars" aria-label="{{ $order->customer_rating }}">
                                @for($i = 1; $i <= 5; $i++)
                                    <span class="guest-review__star {{ $i <= (int) $order->customer_rating ? 'is-on' : '' }}" aria-hidden="true">
                                        <svg viewBox="0 0 20 20" fill="currentColor"><path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.07 3.29a1 1 0 0 0 .95.69h3.46c.97 0 1.37 1.24.59 1.81l-2.8 2.03a1 1 0 0 0-.37 1.12l1.07 3.29c.3.92-.75 1.69-1.54 1.12l-2.8-2.04a1 1 0 0 0-1.17 0l-2.8 2.04c-.78.57-1.84-.2-1.54-1.12l1.07-3.29a1 1 0 0 0-.36-1.12L2.98 8.72c-.78-.57-.38-1.81.59-1.81h3.46a1 1 0 0 0 .95-.69l1.07-3.29Z"/></svg>
                                    </span>
                                @endfor
                            </div>
                            <p class="guest-review__thanks">{{ __('guest.thank_you_feedback') }}</p>
                        </div>
                    @else
                        <p class="guest-review__q">{{ __('guest.how_was_order') }}</p>
                        <div class="guest-review__stars" role="radiogroup" aria-label="{{ __('guest.how_was_order') }}">
                            @for($i = 1; $i <= 5; $i++)
                                <button type="button" wire:click="$set('rating', {{ $i }})"
                                    class="guest-review__star guest-review__star--btn {{ $i <= $rating ? 'is-on' : '' }}"
                                    role="radio" aria-checked="{{ $i === $rating ? 'true' : 'false' }}"
                                    aria-label="{{ trans_choice('guest.rating_stars', $i, ['count' => $i]) }}">
                                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.07 3.29a1 1 0 0 0 .95.69h3.46c.97 0 1.37 1.24.59 1.81l-2.8 2.03a1 1 0 0 0-.37 1.12l1.07 3.29c.3.92-.75 1.69-1.54 1.12l-2.8-2.04a1 1 0 0 0-1.17 0l-2.8 2.04c-.78.57-1.84-.2-1.54-1.12l1.07-3.29a1 1 0 0 0-.36-1.12L2.98 8.72c-.78-.57-.38-1.81.59-1.81h3.46a1 1 0 0 0 .95-.69l1.07-3.29Z"/></svg>
                                </button>
                            @endfor
                        </div>
                        @if($rating > 0)
                            <div class="guest-review__form">
                                <textarea wire:model="feedbackComment" rows="2" maxlength="500"
                                    class="guest-note__field" placeholder="{{ __('guest.feedback_placeholder') }}"></textarea>
                                @error('feedbackComment')
                                    <p class="guest-review__error">{{ $message }}</p>
                                @enderror
                                <button type="button" wire:click="submitFeedback" class="guest-addbtn">
                                    {{ __('guest.submit_feedback') }}
                                </button>
                            </div>
                        @endif
                    @endif

                    @if($googleReviewUrl || $instagramUrl)
                        <div class="guest-review__links">
                            @if($googleReviewUrl)
                                <a class="guest-review__link" href="{{ $googleReviewUrl }}" target="_blank" rel="noopener noreferrer nofollow">
                                    <span class="guest-review__link-ic" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-5.1 7-11a7 7 0 1 0-14 0c0 5.9 7 11 7 11Z"/><circle cx="12" cy="10" r="2.4"/></svg>
                                    </span>
                                    <span>
                                        <strong>{{ __('guest.rate_on_google') }}</strong>
                                        <small>{{ __('guest.rate_on_google_hint') }}</small>
                                    </span>
                                </a>
                            @endif
                            @if($instagramUrl)
                                <a class="guest-review__link" href="{{ $instagramUrl }}" target="_blank" rel="noopener noreferrer nofollow">
                                    <span class="guest-review__link-ic" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1"/></svg>
                                    </span>
                                    <span>
                                        <strong>{{ __('guest.follow_on_instagram') }}</strong>
                                        <small>{{ __('guest.follow_on_instagram_hint') }}</small>
                                    </span>
                                </a>
                            @endif
                        </div>
                    @endif
                </section>
            @endif

            <a href="{{ route('guest.menu', $shop->slug) }}" class="guest-track__menu-link">{{ __('guest.back_to_menu') }}</a>
        </main>
    </section>
</div>
