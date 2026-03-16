<div class="min-h-full bg-transparent px-4 py-8" wire:poll.5s>
    <div class="mx-auto w-full max-w-3xl space-y-6 fade-rise">
        <header class="surface-card p-6 text-center sm:p-8">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl border border-line bg-ink text-panel font-display text-2xl font-black">B</div>
            <h1 class="mt-4 font-display text-4xl font-extrabold leading-none text-ink">{{ $shop->name }}</h1>
            <p class="mt-2 font-mono text-[10px] font-semibold uppercase tracking-[0.18em] text-ink-soft">{{ __('guest.tracking_order', ['id' => $order->id]) }}</p>
        </header>

        @if($order->status === 'cancelled')
            <div class="rounded-xl border border-alert/35 bg-alert/10 px-4 py-3 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-alert">
                {{ __('guest.order_expired') }}
            </div>
        @endif

        <section class="surface-card p-6 sm:p-8">
            <div class="relative">
                <div class="absolute left-0 right-0 top-2 h-0.5 bg-line"></div>
                <div class="relative grid grid-cols-5 gap-2">
                    @php
                        $statusLabels = [
                            'unpaid' => __('pos.unpaid'),
                            'paid' => __('pos.paid'),
                            'preparing' => __('pos.preparing'),
                            'ready' => __('pos.ready'),
                            'completed' => __('guest.delivered'),
                        ];
                    @endphp
                    @foreach(['unpaid', 'paid', 'preparing', 'ready', 'completed'] as $status)
                        @php
                            $statuses = collect(['unpaid', 'paid', 'preparing', 'ready', 'completed']);
                            $active = $order->status === $status;
                            $completed = $statuses->search($order->status) > $statuses->search($status);
                        @endphp
                        <div class="flex flex-col items-center gap-3 text-center">
                            <span @class([
                                'inline-flex h-4 w-4 rounded-full border-2 transition-all duration-300',
                                'border-crema bg-crema' => $active,
                                'border-signal bg-signal' => $completed,
                                'border-line bg-panel' => ! $active && ! $completed,
                            ])></span>
                            <span @class([
                                'font-mono text-[9px] font-semibold uppercase tracking-[0.14em]',
                                'text-crema' => $active,
                                'text-signal' => $completed,
                                'text-ink-soft' => ! $active && ! $completed,
                            ])>{{ $statusLabels[$status] ?? $status }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-8 space-y-5">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-xl border border-line bg-panel px-4 py-3">
                        <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('guest.order_type') }}</p>
                        <p class="mt-2 text-lg font-semibold uppercase tracking-tight text-ink">{{ __('guest.guest_pickup') }}</p>
                    </div>
                    <div class="rounded-xl border border-line bg-panel px-4 py-3 text-left sm:text-right">
                        <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('guest.total') }}</p>
                        <p class="mt-2 font-display text-3xl font-extrabold leading-none text-ink"><x-price :amount="$order->total_amount" :shop="$shop" /></p>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-lg border border-line bg-panel px-3 py-2">
                        <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('guest.subtotal') }}</p>
                        <p class="mt-1 font-mono text-xs font-bold uppercase text-ink"><x-price :amount="$order->subtotal_amount ?? $order->total_amount" :shop="$shop" /></p>
                    </div>
                    <div class="rounded-lg border border-line bg-panel px-3 py-2">
                        <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('guest.tax') }}</p>
                        <p class="mt-1 font-mono text-xs font-bold uppercase text-ink"><x-price :amount="$order->tax_amount ?? 0" :shop="$shop" /></p>
                    </div>
                    <div class="rounded-lg border border-line bg-panel px-3 py-2">
                        <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('guest.total') }}</p>
                        <p class="mt-1 font-mono text-xs font-bold uppercase text-ink"><x-price :amount="$order->total_amount" :shop="$shop" /></p>
                    </div>
                </div>

                <div class="rounded-xl border border-line bg-panel px-4 py-4">
                    <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('guest.status_update') }}</p>
                    <p class="mt-2 text-sm leading-relaxed text-ink">
                        {{ __('guest.status_' . $order->status) }}
                    </p>
                </div>
            </div>
        </section>

        @if($order->status === 'completed' || $order->status === 'ready')
            <section class="surface-card p-6 sm:p-8">
                @if($feedbackSubmitted)
                    <div class="text-center space-y-2">
                        <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-signal">{{ __('guest.thank_you_feedback') }}</p>
                        <div class="flex items-center justify-center gap-1">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="w-6 h-6 {{ $i <= $order->customer_rating ? 'text-crema' : 'text-line' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                            @endfor
                        </div>
                    </div>
                @else
                    <div class="text-center space-y-4">
                        <div>
                            <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('guest.how_was_order') }}</p>
                        </div>
                        <div class="flex items-center justify-center gap-2">
                            @for($i = 1; $i <= 5; $i++)
                                <button wire:click="$set('rating', {{ $i }})" class="transition-transform hover:scale-110">
                                    <svg class="w-10 h-10 {{ $i <= $rating ? 'text-crema' : 'text-line hover:text-ink-soft' }} transition-colors" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                </button>
                            @endfor
                        </div>
                        @if($rating > 0)
                            <div class="space-y-3">
                                <textarea wire:model="feedbackComment" rows="2" class="field w-full text-sm" placeholder="{{ __('guest.feedback_placeholder') }}"></textarea>
                                <button wire:click="submitFeedback" class="btn-primary">
                                    {{ __('guest.submit_feedback') }}
                                </button>
                            </div>
                        @endif
                    </div>
                @endif
            </section>
        @endif

        <div class="text-center">
            <a href="{{ route('guest.menu', $shop->slug) }}" class="btn-secondary inline-flex !px-4 !py-2">
                {{ __('guest.back_to_menu') }}
            </a>
        </div>
    </div>
</div>
