{{-- Order tracking (mockup screen 7, #25). Re-skin on the existing OrderTracker
     server logic. Tokens only (paper/ink/crema/signal), RTL-aware. Public route
     keyed by the UUID tracking_token — renders only this order's data. --}}
@php
    // Customer-safe step copy. Internal statuses map to friendly visual steps via
    // the component's timelineState(); 'unpaid' is framed as "awaiting confirmation",
    // never surfaced as an alarming word.
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
    $steps = \App\Livewire\Guest\OrderTracker::TIMELINE_STEPS;
    $isCancelled = $order->status === 'cancelled';
    $itemCount = (int) $order->items->sum('quantity');
    // Counter code prefix is tenant-derived (first 2 slug chars, uppercased) so
    // every shop gets its own short code — never a hardcoded pilot prefix.
    $codePrefix = strtoupper(substr($shop->slug, 0, 2));
    $shortCode = $codePrefix . '-' . str_pad((string) $order->id, 3, '0', STR_PAD_LEFT);
    $showReview = in_array($order->status, ['ready', 'completed'], true);
@endphp

<div class="guest-track" wire:poll.5s>
    <div class="guest-track__inner fade-rise">

        {{-- Counter code card — what the guest shows at the counter --}}
        <section class="guest-codecard">
            <p class="guest-codecard__lab">{{ __('guest.track_show_counter') }}</p>
            <p class="guest-codecard__code">{{ $shortCode }}</p>
            <p class="guest-codecard__sub">
                @if($order->customer_name){{ $order->customer_name }} · @endif
                {{ trans_choice('guest.items_count', $itemCount, ['count' => $itemCount]) }}
                · <x-price :amount="$order->total_amount" :shop="$shop" />
            </p>
        </section>

        @if($isCancelled)
            {{-- Distinct cancelled state — clear, non-technical copy --}}
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
            {{-- Vertical step timeline — done / now / pending --}}
            <section class="guest-timeline" aria-label="{{ __('guest.status_update') }}">
                @foreach($steps as $i => $stepKey)
                    @php $state = $timelineState[$i] ?? 'pending'; @endphp
                    <div class="guest-tnode guest-tnode--{{ $state }}">
                        @unless($loop->last)
                            <span class="guest-tnode__rail" aria-hidden="true"></span>
                        @endunless
                        <span class="guest-tnode__bub" aria-hidden="true">
                            @if($state === 'done')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4L19 6"/></svg>
                            @elseif($state === 'now')
                                <span class="guest-tnode__pulse"></span>
                            @else
                                {{ $i + 1 }}
                            @endif
                        </span>
                        <span class="guest-tnode__tx">
                            <b>{{ $stepLabels[$stepKey] }}</b>
                            @if($state !== 'pending' && !empty($stepHints[$stepKey]))
                                <small>{{ $stepHints[$stepKey] }}</small>
                            @endif
                        </span>
                    </div>
                @endforeach
            </section>
        @endif

        {{-- Post-order review invite — only once ready or completed --}}
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

                {{-- Branding-driven actions — each rendered ONLY when present + sanitized --}}
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

        <div class="guest-track__back">
            <a href="{{ route('guest.menu', $shop->slug) }}" class="guest-tab">{{ __('guest.back_to_menu') }}</a>
        </div>

        <footer class="guest-powered guest-powered--page">{{ __('guest.powered_by') }} <b>Bite</b></footer>
    </div>
</div>
