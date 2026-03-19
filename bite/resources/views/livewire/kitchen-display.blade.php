<div class="space-y-6 fade-rise" wire:poll.5s
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

    <section class="surface-card p-5 sm:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="section-headline">Production Queue</p>
                <h2 class="mt-1 text-2xl font-extrabold leading-none text-ink sm:text-3xl">{{ __('admin.back_of_house') }}</h2>
            </div>
            <div class="flex items-center gap-2">
                <span wire:loading class="loading-spinner text-ink-soft" style="width: 14px; height: 14px; border-width: 1.5px;"></span>
                <span class="tag">{{ __('admin.live') }}</span>
                <span class="inline-flex items-center rounded-full px-3 py-1.5 font-mono text-xs font-bold uppercase tracking-[0.16em]"
                      style="background-color: rgb(var(--crema)); color: rgb(var(--panel)); border: 1px solid rgb(var(--crema));">
                    {{ __('admin.active_count', ['count' => count($orders)]) }}
                </span>
            </div>
        </div>
    </section>

    <div class="grid gap-3 sm:gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 transition-opacity duration-300" wire:loading.class="opacity-60">
        @forelse($orders as $order)
            @php
                $action = $order->status === 'paid' ? 'preparing' : ($order->status === 'preparing' ? 'ready' : null);
                $minutesSincePaid = $order->paid_at ? now()->diffInMinutes(\Carbon\Carbon::parse($order->paid_at)) : 0;

                if ($minutesSincePaid > 10) {
                    $timeBarColor = 'rgb(var(--alert))';
                } elseif ($minutesSincePaid >= 5) {
                    $timeBarColor = 'rgb(var(--crema))';
                } else {
                    $timeBarColor = 'rgb(var(--signal))';
                }
            @endphp
            <article class="kds-card surface-card overflow-hidden border-ink/15 bg-ink text-panel">
                {{-- Color-coded time indicator bar --}}
                <div class="h-1.5" style="background-color: {{ $timeBarColor }};"></div>

                <span class="sr-only">Ticket_{{ $order->id }}</span>
                <header class="flex items-center justify-between border-b border-panel/15 bg-panel/10 px-4 py-3">
                    <div>
                        <p class="font-mono text-xs font-bold uppercase tracking-[0.16em] text-panel/70">{{ __('admin.order_number', ['id' => $order->id]) }}</p>
                        <p class="mt-1 font-display text-2xl font-extrabold leading-none text-panel sm:text-3xl">{{ __('admin.kitchen_ticket') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-panel/65">
                            {{ $order->paid_at ? \Carbon\Carbon::parse($order->paid_at)->format('H:i') : __('admin.new') }}
                        </p>
                        <p class="mt-1 font-mono text-sm font-bold tabular-nums" style="color: {{ $timeBarColor }};">
                            {{ $minutesSincePaid }}m
                        </p>
                    </div>
                </header>

                <div class="space-y-4 p-4">
                    <span class="inline-flex items-center rounded-full border border-panel/20 bg-panel/10 px-2.5 py-1 font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-panel/70">
                        {{ __('admin.guest_order') }}
                    </span>

                    <ul class="space-y-2">
                        @foreach($order->items as $item)
                            <li class="flex items-start gap-3 rounded-lg border border-panel/15 bg-panel/10 px-3 py-2.5">
                                <span class="inline-flex min-w-10 items-center justify-center rounded-md bg-crema px-2 py-1 font-mono text-sm font-bold uppercase text-panel">{{ $item->quantity }}x</span>
                                <span class="text-lg font-bold uppercase tracking-tight text-panel">{{ $item->translated('product_name_snapshot') }}</span>
                            </li>
                        @endforeach
                    </ul>

                    @if($order->status === 'paid')
                        <button wire:click="updateStatus({{ $order->id }}, 'preparing')" class="btn-primary w-full justify-center !bg-crema !border-crema text-base">
                            {{ __('admin.start_preparing') }}
                        </button>
                    @elseif($order->status === 'preparing')
                        <button wire:click="updateStatus({{ $order->id }}, 'ready')" class="btn-primary w-full justify-center !bg-signal !border-signal text-base">
                            {{ __('admin.order_ready') }}
                        </button>
                    @else
                        <div class="rounded-lg border border-panel/20 bg-panel/10 px-3 py-2 text-center font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-panel/65">
                            {{ __('admin.awaiting_next_stage') }}
                        </div>
                    @endif

                    @if(in_array(Auth::user()->role, ['admin', 'manager'], true))
                        <button
                            wire:click="cancelOrder({{ $order->id }})"
                            wire:confirm="{{ __('admin.cancel_order_confirm', ['id' => $order->id]) }}"
                            wire:loading.attr="disabled"
                            wire:target="cancelOrder({{ $order->id }})"
                            class="w-full text-center font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-panel/50 hover:text-alert transition-colors"
                        >
                            <span wire:loading.remove wire:target="cancelOrder({{ $order->id }})">{{ __('admin.cancel_order') }}</span>
                            <span wire:loading wire:target="cancelOrder({{ $order->id }})" class="loading-spinner" style="width: 10px; height: 10px; border-width: 1px;"></span>
                        </button>
                    @endif
                </div>
            </article>
        @empty
            <div class="col-span-full">
                <div class="surface-card border-dashed p-16 text-center">
                    <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.2em] text-ink-soft">{{ __('admin.no_active_orders') }}</p>
                </div>
            </div>
        @endforelse
    </div>
</div>
