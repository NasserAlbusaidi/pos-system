<div class="min-h-full bg-transparent px-4 py-8" wire:poll.5s>
    <div class="mx-auto w-full max-w-3xl space-y-6 fade-rise">
        <header class="surface-card p-6 text-center sm:p-8">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl border border-line bg-ink text-panel font-display text-2xl font-black">B</div>
            <h1 class="mt-4 font-display text-4xl font-extrabold leading-none text-ink">{{ $shop->name }}</h1>
            <p class="mt-2 font-mono text-[10px] font-semibold uppercase tracking-[0.18em] text-ink-soft">Tracking Order #{{ $order->id }}</p>
        </header>

        @if($order->status === 'cancelled')
            <div class="rounded-xl border border-alert/35 bg-alert/10 px-4 py-3 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-alert">
                Order expired. Please place a new order or ask a server for help.
            </div>
        @endif

        <section class="surface-card p-6 sm:p-8">
            <div class="relative">
                <div class="absolute left-0 right-0 top-2 h-0.5 bg-line"></div>
                <div class="relative grid grid-cols-5 gap-2">
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
                            ])>{{ $status === 'completed' ? 'Delivered' : $status }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-8 space-y-5">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-xl border border-line bg-panel px-4 py-3">
                        <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Order Type</p>
                        <p class="mt-2 text-lg font-semibold uppercase tracking-tight text-ink">Guest Pickup</p>
                    </div>
                    <div class="rounded-xl border border-line bg-panel px-4 py-3 text-left sm:text-right">
                        <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Total</p>
                        <p class="mt-2 font-display text-3xl font-extrabold leading-none text-ink">${{ number_format($order->total_amount, 2) }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-lg border border-line bg-panel px-3 py-2">
                        <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.14em] text-ink-soft">Subtotal</p>
                        <p class="mt-1 font-mono text-xs font-bold uppercase text-ink">${{ number_format($order->subtotal_amount ?? $order->total_amount, 2) }}</p>
                    </div>
                    <div class="rounded-lg border border-line bg-panel px-3 py-2">
                        <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.14em] text-ink-soft">Tax</p>
                        <p class="mt-1 font-mono text-xs font-bold uppercase text-ink">${{ number_format($order->tax_amount ?? 0, 2) }}</p>
                    </div>
                    <div class="rounded-lg border border-line bg-panel px-3 py-2">
                        <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.14em] text-ink-soft">Total</p>
                        <p class="mt-1 font-mono text-xs font-bold uppercase text-ink">${{ number_format($order->total_amount, 2) }}</p>
                    </div>
                </div>

                <div class="rounded-xl border border-line bg-panel px-4 py-4">
                    <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Status Update</p>
                    <p class="mt-2 text-sm leading-relaxed text-ink">
                        @switch($order->status)
                            @case('unpaid')
                                Awaiting payment verification. Please check with the counter.
                                @break
                            @case('paid')
                                Payment confirmed. Order entered the kitchen queue.
                                @break
                            @case('preparing')
                                Kitchen is actively preparing your order.
                                @break
                            @case('ready')
                                Order is complete and handover is in progress.
                                @break
                            @case('completed')
                                Order delivered. Enjoy your meal.
                                @break
                            @case('cancelled')
                                Order expired before payment. Please reorder.
                                @break
                        @endswitch
                    </p>
                </div>
            </div>
        </section>

        <div class="text-center">
            <a href="{{ route('guest.menu', $shop->slug) }}" class="btn-secondary inline-flex !px-4 !py-2">
                Back to Menu
            </a>
        </div>
    </div>
</div>
