<div class="space-y-6 fade-rise" wire:poll.10s>
    <x-slot:header>Operations Dashboard</x-slot:header>

    <section class="surface-card p-5 sm:p-7">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="section-headline">Daily Snapshot</p>
                <h2 class="mt-2 text-3xl font-extrabold leading-none text-ink sm:text-4xl">Store Pulse</h2>
                <p class="mt-2 max-w-2xl text-sm text-ink-soft">Revenue, throughput, and kitchen state update every 10 seconds so the floor and back of house stay aligned.</p>
            </div>

            <div class="flex items-center gap-2">
                <span class="tag">Auto Refresh</span>
                <span class="inline-flex items-center gap-2 rounded-full border border-signal/30 bg-signal/10 px-3 py-1.5 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-signal">
                    <span class="status-dot status-live"></span>
                    Live
                </span>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-xl border border-line bg-panel p-5">
                <p class="section-headline">Today's Revenue</p>
                <p class="metric-value mt-4">{{ formatPrice($dailyRevenue, $shop) }}</p>
            </article>

            <article class="rounded-xl border border-line bg-panel p-5">
                <p class="section-headline">Orders Today</p>
                <p class="metric-value mt-4 text-signal">{{ $ordersTodayCount }}</p>
            </article>

            <article class="rounded-xl border border-line bg-panel p-5">
                <p class="section-headline">Active Orders</p>
                <p class="metric-value mt-4 text-crema">{{ $activeOrdersCount }}</p>
            </article>

            <article class="rounded-xl border border-line bg-panel p-5">
                <p class="section-headline">System Status</p>
                <div class="mt-4 inline-flex items-center gap-2 rounded-full border border-signal/30 bg-signal/10 px-3 py-1.5 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-signal">
                    <span class="status-dot status-live"></span>
                    Online
                </div>
            </article>
        </div>
    </section>

    <section class="grid gap-4 lg:grid-cols-3">
        <article class="surface-card p-5">
            <p class="section-headline">Items Sold Today</p>
            <p class="metric-value mt-4">{{ $itemsSoldToday }}</p>
        </article>

        <article class="surface-card p-5">
            <p class="section-headline">Average Order Value</p>
            <p class="metric-value mt-4">{{ formatPrice($avgOrderValue, $shop) }}</p>
        </article>

        <article class="surface-card p-5">
            <p class="section-headline">Orders by Status</p>
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach(['unpaid', 'paid', 'preparing', 'ready', 'completed', 'cancelled'] as $status)
                    <span class="tag">{{ $status }}: {{ $ordersByStatus[$status] ?? 0 }}</span>
                @endforeach
            </div>
        </article>
    </section>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="surface-card xl:col-span-2">
            <div class="flex items-center justify-between border-b border-line bg-muted/35 px-5 py-4">
                <h2 class="font-display text-2xl font-extrabold leading-none">Top Products</h2>
                <span class="tag">Last 7 Days</span>
            </div>

            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="border-b border-line bg-panel font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">
                        <th class="px-5 py-3">Product</th>
                        <th class="px-5 py-3">Qty</th>
                        <th class="px-5 py-3">Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/65">
                    @forelse($topProducts as $product)
                        <tr class="transition-colors hover:bg-muted/35">
                            <td class="px-5 py-4 text-sm font-semibold uppercase tracking-tight text-ink">{{ $product->product_name_snapshot }}</td>
                            <td class="px-5 py-4 font-mono text-xs font-bold uppercase">{{ $product->qty }}</td>
                            <td class="px-5 py-4 font-mono text-xs font-bold uppercase">{{ formatPrice($product->revenue, $shop) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-10 text-center font-mono text-[10px] uppercase tracking-[0.2em] text-ink-soft">No sales yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <div class="space-y-6">
            <section class="surface-card">
                <div class="border-b border-line bg-muted/35 px-5 py-4">
                    <h2 class="font-display text-2xl font-extrabold leading-none">Payments</h2>
                </div>
                <div class="space-y-3 p-5">
                    @if(!empty($paymentSummary))
                        @foreach($paymentSummary as $method => $summary)
                            <div class="flex items-center justify-between rounded-xl border border-line bg-panel px-4 py-3">
                                <div>
                                    <div class="font-mono text-[10px] font-semibold uppercase tracking-[0.18em] text-ink-soft">{{ strtoupper($method) }}</div>
                                    <div class="mt-1 text-sm font-medium text-ink">{{ $summary['orders'] }} orders</div>
                                </div>
                                <div class="font-mono text-sm font-bold uppercase">{{ formatPrice($summary['total'], $shop) }}</div>
                            </div>
                        @endforeach
                    @else
                        <div class="rounded-xl border border-dashed border-line bg-panel px-4 py-6 text-center font-mono text-[10px] uppercase tracking-[0.18em] text-ink-soft">No payments yet</div>
                    @endif
                </div>
            </section>

            <section class="surface-card">
                <div class="border-b border-line bg-muted/35 px-5 py-4">
                    <h2 class="font-display text-2xl font-extrabold leading-none">Weekly Revenue</h2>
                </div>
                <div class="space-y-3 p-5">
                    @foreach($weeklyRevenue as $row)
                        <div class="flex items-center justify-between rounded-lg border border-line/75 bg-panel px-3 py-2">
                            <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ \Carbon\Carbon::parse($row['day'])->format('D') }}</span>
                            <span class="font-mono text-xs font-bold uppercase">{{ formatPrice($row['total'], $shop) }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>

    <section class="surface-card">
        <div class="flex items-center justify-between border-b border-line bg-muted/35 px-5 py-4">
            <h2 class="font-display text-2xl font-extrabold leading-none">Recent Activity</h2>
            <a href="{{ route('pos.dashboard') }}" class="btn-secondary !px-3 !py-2" wire:navigate>
                Open POS
            </a>
        </div>

        <table class="w-full border-collapse text-left">
            <thead>
                <tr class="border-b border-line bg-panel font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">
                    <th class="px-5 py-3">Order ID</th>
                    <th class="px-5 py-3">Source</th>
                    <th class="px-5 py-3">Total</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3 text-right">Time</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line/65">
                @forelse($recentOrders as $order)
                    <tr class="transition-colors hover:bg-muted/35">
                        <td class="px-5 py-4 font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-ink-soft">#{{ $order->id }}</td>
                        <td class="px-5 py-4 text-sm font-medium text-ink">Guest</td>
                        <td class="px-5 py-4 font-mono text-xs font-bold uppercase text-ink">{{ formatPrice($order->total_amount, $shop) }}</td>
                        <td class="px-5 py-4">
                            @php
                                $statusClass = match ($order->status) {
                                    'completed' => 'border-signal/35 bg-signal/10 text-signal',
                                    'cancelled' => 'border-alert/35 bg-alert/10 text-alert',
                                    'ready' => 'border-crema/40 bg-crema/10 text-crema',
                                    default => 'border-line bg-muted text-ink-soft',
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-full border px-2.5 py-1 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] {{ $statusClass }}">
                                {{ $order->status }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ $order->created_at->format('H:i:s') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center font-mono text-[10px] uppercase tracking-[0.2em] text-ink-soft">No recent orders found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
