<div class="space-y-6 fade-rise" wire:poll.5s>
    <x-slot:header>Kitchen Display</x-slot:header>

    <section class="surface-card p-5 sm:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="section-headline">Production Queue</p>
                <h2 class="mt-1 text-3xl font-extrabold leading-none text-ink">Back-of-House Flow</h2>
            </div>
            <div class="flex items-center gap-2">
                <span class="tag">Live</span>
                <span class="inline-flex items-center rounded-full px-3 py-1.5 font-mono text-xs font-bold uppercase tracking-[0.16em]"
                      style="background-color: rgb(var(--crema)); color: rgb(var(--panel)); border: 1px solid rgb(var(--crema));">
                    {{ count($orders) }} active
                </span>
            </div>
        </div>
    </section>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
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
                        <p class="font-mono text-xs font-bold uppercase tracking-[0.16em] text-panel/70">Order #{{ $order->id }}</p>
                        <p class="mt-1 font-display text-3xl font-extrabold leading-none text-panel">Kitchen Ticket</p>
                    </div>
                    <div class="text-right">
                        <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-panel/65">
                            {{ $order->paid_at ? \Carbon\Carbon::parse($order->paid_at)->format('H:i') : 'New' }}
                        </p>
                        <p class="mt-1 font-mono text-sm font-bold tabular-nums" style="color: {{ $timeBarColor }};">
                            {{ $minutesSincePaid }}m
                        </p>
                    </div>
                </header>

                <div class="space-y-4 p-4">
                    <span class="inline-flex items-center rounded-full border border-panel/20 bg-panel/10 px-2.5 py-1 font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-panel/70">
                        Guest Order
                    </span>

                    <ul class="space-y-2">
                        @foreach($order->items as $item)
                            <li class="flex items-start gap-3 rounded-lg border border-panel/15 bg-panel/10 px-3 py-2.5">
                                <span class="inline-flex min-w-10 items-center justify-center rounded-md bg-crema px-2 py-1 font-mono text-sm font-bold uppercase text-panel">{{ $item->quantity }}x</span>
                                <span class="text-lg font-bold uppercase tracking-tight text-panel">{{ $item->product_name_snapshot }}</span>
                            </li>
                        @endforeach
                    </ul>

                    @if($order->status === 'paid')
                        <button wire:click="updateStatus({{ $order->id }}, 'preparing')" class="btn-primary w-full justify-center !bg-crema !border-crema text-base">
                            Start Preparing
                        </button>
                    @elseif($order->status === 'preparing')
                        <button wire:click="updateStatus({{ $order->id }}, 'ready')" class="btn-primary w-full justify-center !bg-signal !border-signal text-base">
                            Order Ready
                        </button>
                    @else
                        <div class="rounded-lg border border-panel/20 bg-panel/10 px-3 py-2 text-center font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-panel/65">
                            Awaiting Next Stage
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <div class="col-span-full">
                <div class="surface-card border-dashed p-16 text-center">
                    <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.2em] text-ink-soft">No Active Orders</p>
                </div>
            </div>
        @endforelse
    </div>
</div>
