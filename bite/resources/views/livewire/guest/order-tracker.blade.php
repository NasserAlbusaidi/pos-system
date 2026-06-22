{{-- Order tracking. Public route keyed by the UUID tracking_token; renders only
     this order's customer-safe state and keeps internal statuses out of copy. --}}
@php
    $stepLabels = [
        'received' => __('guest.track_step_received'),
        'accepted' => __('guest.track_step_accepted'),
        'preparing' => __('guest.track_step_preparing'),
        'ready' => __('guest.track_step_ready'),
    ];
    $steps = \App\Livewire\Guest\OrderTracker::TIMELINE_STEPS;
    $isCancelled = $order->status === 'cancelled';
    $shortCode = strtoupper(substr($shop->slug, 0, 2)) . '-' . str_pad((string) $order->id, 3, '0', STR_PAD_LEFT);
    $showReview = in_array($order->status, ['ready', 'completed'], true);
    $trackingTableLabel = $tableLabel ?: null;
    $orderReference = $trackingTableLabel
        ? __('guest.track_order_reference', ['order' => $shortCode, 'table' => $trackingTableLabel])
        : __('guest.track_order_reference_no_table', ['order' => $shortCode]);
    $trackingQuery = $trackingTableLabel ? '?' . http_build_query(['table' => $trackingTableLabel]) : '';
    $menuUrl = route('guest.menu', $shop->slug) . $trackingQuery;
    $trackUrl = route('guest.track', $order->tracking_token) . $trackingQuery;
@endphp

<div class="guest-track" wire:poll.5s>
    <section class="guest-track__phone">
        <header class="guest-track-header">
            <div class="guest-track-header__content">
                <button
                    type="button"
                    class="guest-track-lang"
                    wire:click="switchLanguage('{{ $locale === 'ar' ? 'en' : 'ar' }}')"
                    lang="en"
                    dir="ltr"
                    aria-label="{{ __('guest.language') }}"
                >{{ $locale === 'ar' ? 'EN' : 'AR' }}</button>

                <div class="guest-track-header__copy">
                    <h1>{{ $isCancelled ? __('guest.track_cancelled_title') : __('guest.track_received_title') }}</h1>
                    <p>{{ $orderReference }}</p>
                </div>

                <a href="{{ $menuUrl }}" class="guest-track-header__back" aria-label="{{ __('guest.back_to_menu') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                </a>
            </div>
        </header>

        <main class="guest-track__main">
            <section class="guest-track-hero" aria-label="{{ __('guest.tracking_order', ['id' => $order->id]) }}">
                <img src="{{ asset('customer-ordering/assets/hopresso/cup-togo.png') }}" alt="" aria-hidden="true">
            </section>

            <section class="guest-track-received-card" aria-labelledby="guest-track-title">
                <h2 id="guest-track-title">{{ $isCancelled ? __('guest.track_cancelled_title') : __('guest.track_received_title') }}</h2>
                <p>{{ $isCancelled ? __('guest.status_cancelled') : __('guest.track_received_body', ['shop' => $shop->name]) }}</p>

                <ol class="guest-track-progress-list" aria-label="{{ __('guest.status_update') }}">
                    @foreach($steps as $i => $stepKey)
                        @php $state = $timelineState[$i] ?? 'pending'; @endphp
                        <li class="guest-track-progress-step guest-track-progress-step--{{ $state }}">
                            <span class="guest-track-progress-step__number">{{ $i + 1 }}</span>
                            <span class="guest-track-progress-step__label">{{ $stepLabels[$stepKey] }}</span>
                        </li>
                    @endforeach
                </ol>

                <div class="guest-track-actions">
                    @if($this->canSimulateStatus())
                        <button type="button" wire:click="simulateNextStatus" class="guest-track-action">
                            {{ __('guest.track_simulate_next') }}
                        </button>
                    @endif

                    @if($showReview)
                        <a href="#visit-rating" class="guest-track-action guest-track-action--secondary">{{ __('guest.rate_your_visit') }}</a>
                    @else
                        <button type="button" class="guest-track-action guest-track-action--secondary" disabled>
                            {{ __('guest.rate_your_visit') }}
                        </button>
                    @endif
                </div>
            </section>

            @if($showReview)
                <section id="visit-rating" class="guest-visit-review" aria-labelledby="guest-visit-review-title">
                    <div class="guest-visit-review__hero">
                        <p>{{ __('guest.visit_review_before_leave') }}</p>
                        <h2 id="guest-visit-review-title">
                            {!! __('guest.visit_review_title', ['shop' => '<bdi>' . e($shop->name) . '</bdi>']) !!}
                        </h2>
                        <span>{{ __('guest.visit_review_body') }}</span>
                    </div>

                    <div class="guest-visit-review__panel">
                        <div class="guest-visit-review__stars" aria-hidden="true">
                            @for($i = 1; $i <= 5; $i++)
                                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.07 3.29a1 1 0 0 0 .95.69h3.46c.97 0 1.37 1.24.59 1.81l-2.8 2.03a1 1 0 0 0-.37 1.12l1.07 3.29c.3.92-.75 1.69-1.54 1.12l-2.8-2.04a1 1 0 0 0-1.17 0l-2.8 2.04c-.78.57-1.84-.2-1.54-1.12l1.07-3.29a1 1 0 0 0-.36-1.12L2.98 8.72c-.78-.57-.38-1.81.59-1.81h3.46a1 1 0 0 0 .95-.69l1.07-3.29Z"/></svg>
                            @endfor
                        </div>

                        @if($googleReviewUrl || $instagramUrl)
                            <div class="guest-visit-review__links">
                                @if($googleReviewUrl)
                                    <a class="guest-visit-review__link guest-visit-review__link--primary" href="{{ $googleReviewUrl }}" target="_blank" rel="noopener noreferrer nofollow">
                                        <span class="guest-visit-review__link-copy">
                                            <strong>{{ __('guest.visit_google_cta') }}</strong>
                                            <small>{{ __('guest.visit_google_hint') }}</small>
                                        </span>
                                        <span class="guest-visit-review__link-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-5.1 7-11a7 7 0 1 0-14 0c0 5.9 7 11 7 11Z"/><circle cx="12" cy="10" r="2.4"/></svg>
                                        </span>
                                    </a>
                                @endif

                                @if($instagramUrl)
                                    <a class="guest-visit-review__link guest-visit-review__link--secondary" href="{{ $instagramUrl }}" target="_blank" rel="noopener noreferrer nofollow">
                                        <span class="guest-visit-review__link-copy">
                                            <strong>{{ __('guest.visit_instagram_cta') }}</strong>
                                            <small>{{ __('guest.visit_instagram_hint') }}</small>
                                        </span>
                                        <span class="guest-visit-review__link-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 10.7 6.8-4.4M8.6 13.3l6.8 4.4"/></svg>
                                        </span>
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="guest-visit-review__actions">
                        <a href="{{ $menuUrl }}" class="guest-visit-review__button guest-visit-review__button--primary">
                            {{ __('guest.visit_back_to_menu') }}
                        </a>
                        <a href="{{ $trackUrl }}" class="guest-visit-review__button guest-visit-review__button--secondary">
                            {{ __('guest.visit_track_current_order') }}
                        </a>
                    </div>

                    <footer class="guest-visit-review__powered" aria-label="{{ __('guest.powered_by') }} Bite">
                        <span>{{ __('guest.powered_by') }}</span>
                        <img src="{{ asset('customer-ordering/assets/brand/bite-powered-logo.png') }}" alt="Bite">
                    </footer>
                </section>
            @endif
        </main>
    </section>
</div>

@script
<script>
    $wire.on('guest-locale-changed', ({ direction }) => {
        const dir = direction === 'rtl' ? 'rtl' : 'ltr';
        document.documentElement.setAttribute('dir', dir);
        document.documentElement.setAttribute('lang', dir === 'rtl' ? 'ar' : 'en');
    });
</script>
@endscript
