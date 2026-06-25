<div class="space-y-[18px] fade-rise" wire:poll.5s
     x-data="{
         audioCtx: null,
         playChime() {
             if (!this.audioCtx) this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
             const ctx = this.audioCtx;
             const osc = ctx.createOscillator();
             const gain = ctx.createGain();
             osc.connect(gain);
             gain.connect(ctx.destination);
             osc.frequency.setValueAtTime(880, ctx.currentTime);
             osc.frequency.setValueAtTime(1100, ctx.currentTime + 0.1);
             osc.frequency.setValueAtTime(880, ctx.currentTime + 0.2);
             gain.gain.setValueAtTime(0.3, ctx.currentTime);
             gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.4);
             osc.start(ctx.currentTime);
             osc.stop(ctx.currentTime + 0.4);
         }
     }"
     x-on:kds-new-order.window="playChime()"
>
    <x-slot:header>{{ __('admin.kitchen_display') }}</x-slot:header>

    {{-- ===== PRODUCTION QUEUE BAR ===== --}}
    <section class="surface-card">
        <div class="flex flex-col gap-3 px-[22px] py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2.5">
                <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.production_queue') }}</h2>
            </div>
            <div class="flex items-center gap-2">
                <span wire:loading class="loading-spinner text-ink-soft" style="width: 14px; height: 14px; border-width: 1.5px;"></span>
                <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 font-mono text-[10px] font-bold uppercase tracking-[0.14em]"
                      style="border-color: color-mix(in srgb, var(--bite-green) 35%, transparent); background: var(--bite-lime-100); color: var(--bite-pine);">
                    <span class="h-1.5 w-1.5 rounded-full" style="background: var(--bite-green); animation: pulseDot 1.8s ease-in-out infinite;"></span>
                    {{ __('admin.kds_live_refresh') }}
                </span>
                <span class="inline-flex items-center rounded-full px-3 py-1.5 font-mono text-[10px] font-bold uppercase tracking-[0.14em]"
                      style="background: var(--bite-lime); color: var(--bite-forest);">
                    {{ __('admin.active_count', ['count' => count($orders)]) }}
                </span>
            </div>
        </div>
    </section>

    {{-- ===== TICKET GRID ===== --}}
    <div class="grid gap-4 transition-opacity duration-300" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));" wire:loading.class="opacity-60">
        @forelse($orders as $order)
            @php
                // Elapsed since payment — drives both the mm:ss timer and the urgency
                // tiers. Derive minutes from seconds so a green test (paid_at null) and
                // the live board agree, and so it is robust across Carbon versions.
                $elapsedSeconds = $order->paid_at ? max(0, (int) round(now()->diffInSeconds(\Carbon\Carbon::parse($order->paid_at)))) : 0;
                $minutesSincePaid = intdiv($elapsedSeconds, 60);
                $elapsedLabel = sprintf('%02d:%02d', intdiv($elapsedSeconds, 60), $elapsedSeconds % 60);

                $isLate = $minutesSincePaid > 10;
                $isWarn = ! $isLate && $minutesSincePaid >= 5;

                // Header bar colour by status (mockup): New = lime, Preparing = lighter lime.
                $headerBg = $order->status === 'paid' ? 'var(--bite-lime)' : 'var(--bite-lime-300)';
                // Timer colour preserves the three-tier urgency signal of the old board.
                $timerColor = $isLate ? 'rgb(var(--alert))' : ($isWarn ? 'var(--bite-olive)' : 'var(--bite-forest)');
            @endphp
            <article class="kds-card surface-card flex flex-col overflow-hidden" wire:key="kds-{{ $order->id }}">
                <span class="sr-only">Ticket_{{ $order->id }}</span>

                {{-- Header bar — order #, status, elapsed timer --}}
                <header class="flex items-center justify-between px-4 py-3" style="background: {{ $headerBg }};">
                    <div class="flex flex-col gap-1">
                        <span class="font-mono text-sm font-bold tracking-[0.04em] text-forest">#{{ $order->id }}</span>
                        <span class="font-mono text-[9px] font-bold uppercase tracking-[0.14em]" style="color: color-mix(in srgb, var(--bite-forest) 72%, transparent);">
                            {{ $order->status === 'paid' ? __('admin.new') : __('admin.status_preparing') }}
                        </span>
                        @if(filled($order->customer_name))
                            <span class="truncate text-xs font-semibold text-forest">{{ $order->customer_name }}</span>
                        @endif
                        <span class="font-mono text-[9px] font-bold uppercase tracking-[0.14em]" style="color: color-mix(in srgb, var(--bite-forest) 62%, transparent);">
                            {{ $order->sourceLabel() }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($isLate)
                            <span class="rounded-full px-2 py-0.5 font-mono text-[9px] font-bold uppercase tracking-[0.1em] text-white" style="background: rgb(var(--alert));">{{ __('admin.late') }}</span>
                        @endif
                        <span class="font-mono text-lg font-bold tabular-nums tracking-[0.04em]" style="color: {{ $timerColor }};">{{ $elapsedLabel }}</span>
                    </div>
                </header>

                {{-- Items --}}
                <div class="flex flex-1 flex-col gap-2.5 px-4 py-3.5">
                    @foreach($order->items as $item)
                        <div class="flex flex-col gap-1">
                            <div class="flex gap-2">
                                <span class="font-display text-sm font-bold text-forest">{{ $item->quantity }}x</span>
                                <span class="text-sm font-medium text-ink">{{ $item->translated('product_name_snapshot') }}</span>
                            </div>

                            @if($item->modifiers->isNotEmpty())
                                <ul class="ms-[26px] mt-0.5 space-y-0.5 pl-1 font-mono text-[11px] text-ink-soft">
                                    @foreach($item->modifiers as $modifier)
                                        <li>{{ $modifier->translated('modifier_option_name_snapshot') }}</li>
                                    @endforeach
                                </ul>
                            @endif

                            {{-- Guest special request — safety-critical (allergens). Highlighted so it can't be missed. --}}
                            @if(filled($item->note))
                                <p class="kds-note ms-[26px]">
                                    <svg class="kds-note__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>
                                    <span>{{ $item->note }}</span>
                                </p>
                            @endif
                        </div>
                    @endforeach

                    {{-- Guest order-level note (Phase 4) — one instruction for the whole
                         order, may carry a shared allergen flag. Highlighted distinctly
                         from per-item notes so the kitchen cannot miss it. --}}
                    @if(filled($order->order_note))
                        <p class="kds-order-note">
                            <svg class="kds-note__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z"/></svg>
                            <span>{{ $order->order_note }}</span>
                        </p>
                    @endif
                </div>

                {{-- Actions --}}
                <div class="flex flex-col gap-2 px-4 pb-4">
                    @if($order->status === 'paid')
                        <button wire:click="updateStatus({{ $order->id }}, 'preparing')" class="btn-primary w-full justify-center text-base" style="background: var(--bite-forest); border-color: var(--bite-forest);">
                            {{ __('admin.start_preparing') }}
                        </button>
                    @elseif($order->status === 'preparing')
                        <button wire:click="updateStatus({{ $order->id }}, 'ready')" class="btn-primary w-full justify-center text-base" style="background: var(--bite-forest); border-color: var(--bite-forest);">
                            {{ __('admin.order_ready') }}
                        </button>
                    @else
                        <div class="rounded-lg border border-line bg-cream px-3 py-2 text-center font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">
                            {{ __('admin.awaiting_next_stage') }}
                        </div>
                    @endif

                    @if(in_array(Auth::user()->role, ['admin', 'manager'], true))
                        @php
                            $hasPayment = $order->trustedPayments()->isNotEmpty();
                        @endphp
                        <button
                            wire:click="cancelOrder({{ $order->id }})"
                            wire:confirm="{{ $hasPayment ? __('admin.refund_void_order_confirm', ['id' => $order->id]) : __('admin.cancel_order_confirm', ['id' => $order->id]) }}"
                            wire:loading.attr="disabled"
                            wire:target="cancelOrder({{ $order->id }})"
                            class="w-full text-center font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft transition-colors hover:text-alert"
                        >
                            <span wire:loading.remove wire:target="cancelOrder({{ $order->id }})">{{ $hasPayment ? __('admin.refund_void_order') : __('admin.cancel_order') }}</span>
                            <span wire:loading wire:target="cancelOrder({{ $order->id }})" class="loading-spinner" style="width: 10px; height: 10px; border-width: 1px;"></span>
                        </button>
                    @endif
                </div>
            </article>
        @empty
            <div style="grid-column: 1 / -1;">
                <div class="surface-card border-dashed p-16 text-center">
                    <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.2em] text-ink-soft">{{ __('admin.no_active_orders') }}</p>
                </div>
            </div>
        @endforelse
    </div>
</div>
