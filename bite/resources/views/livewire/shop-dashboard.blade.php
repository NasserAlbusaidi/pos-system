<div class="space-y-6 fade-rise" wire:poll.10s>
    <x-slot:header>{{ __('admin.operations_dashboard') }}</x-slot:header>

    <section class="surface-card p-5 sm:p-7">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="section-headline">Daily Snapshot</p>
                <h2 class="mt-2 text-3xl font-extrabold leading-none text-ink sm:text-4xl">{{ __('admin.store_pulse') }}</h2>
                <p class="mt-2 max-w-2xl text-sm text-ink-soft">{{ __('admin.store_pulse_desc') }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                {{-- Notification Bell --}}
                <div class="relative">
                    <button wire:click="toggleNotifications" class="relative rounded-lg border border-line bg-panel p-2.5 text-ink-soft transition-colors hover:border-ink hover:text-ink">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                        @if($unreadCount > 0)
                            <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-crema px-1 font-mono text-[9px] font-bold text-panel">{{ $unreadCount }}</span>
                        @endif
                    </button>

                    @if($showNotifications)
                        <div class="absolute right-0 top-full z-50 mt-2 w-[calc(100vw-2rem)] rounded-xl border border-line bg-panel shadow-xl sm:w-96">
                            <div class="flex items-center justify-between border-b border-line px-4 py-3">
                                <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.notifications') }}</p>
                                @if($notifications->isNotEmpty())
                                    <button wire:click="clearAllNotifications" class="font-mono text-[9px] font-semibold uppercase tracking-[0.14em] text-ink-soft hover:text-alert">{{ __('admin.clear_all') }}</button>
                                @endif
                            </div>
                            <div class="max-h-72 divide-y divide-line overflow-y-auto">
                                @forelse($notifications as $notification)
                                    <div class="px-4 py-3 {{ $notification->read_at ? 'opacity-60' : '' }}">
                                        <div class="flex items-start justify-between gap-2">
                                            <div>
                                                <p class="text-sm font-semibold text-ink">Order #{{ $notification->data['order_id'] ?? '—' }}</p>
                                                <p class="mt-0.5 font-mono text-[10px] text-ink-soft">{{ $notification->data['item_count'] ?? 0 }} items &middot; {{ isset($notification->data['total']) ? formatPrice($notification->data['total'], $shop) : '—' }}</p>
                                            </div>
                                            @if(!empty($notification->data['whatsapp_link']) && str_starts_with($notification->data['whatsapp_link'], 'https://wa.me/'))
                                                <a href="{{ $notification->data['whatsapp_link'] }}" target="_blank" rel="noopener" class="shrink-0 rounded-lg border border-signal/30 bg-signal/10 p-1.5 text-signal hover:bg-signal/20">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                                </a>
                                            @endif
                                        </div>
                                        <p class="mt-1 font-mono text-[9px] text-ink-soft/60">{{ $notification->created_at->diffForHumans() }}</p>
                                    </div>
                                @empty
                                    <div class="px-4 py-8 text-center">
                                        <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.no_notifications') }}</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @endif
                </div>

                <span wire:loading class="loading-spinner text-ink-soft" style="width: 14px; height: 14px; border-width: 1.5px;"></span>
                <span class="tag">{{ __('admin.auto_refresh') }}</span>
                <span class="inline-flex items-center gap-2 rounded-full border border-signal/30 bg-signal/10 px-3 py-1.5 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-signal">
                    <span class="status-dot status-live"></span>
                    {{ __('admin.live') }}
                </span>
            </div>
        </div>

        <div class="mt-6 grid gap-3 sm:gap-4 sm:grid-cols-2 xl:grid-cols-4 transition-opacity duration-300" wire:loading.class="opacity-60">
            <article class="rounded-xl border border-line bg-panel p-5">
                <p class="section-headline">{{ __('admin.todays_revenue') }}</p>
                <p class="metric-value mt-4">{{ formatPrice($dailyRevenue, $shop) }}</p>
            </article>

            <article class="rounded-xl border border-line bg-panel p-5">
                <p class="section-headline">{{ __('admin.orders_today') }}</p>
                <p class="metric-value mt-4 text-signal">{{ $ordersTodayCount }}</p>
            </article>

            <article class="rounded-xl border border-line bg-panel p-5">
                <p class="section-headline">{{ __('admin.active_orders') }}</p>
                <p class="metric-value mt-4 text-crema">{{ $activeOrdersCount }}</p>
            </article>

            <article class="rounded-xl border border-line bg-panel p-5">
                <p class="section-headline">{{ __('admin.system_status') }}</p>
                <div class="mt-4 inline-flex items-center gap-2 rounded-full border border-signal/30 bg-signal/10 px-3 py-1.5 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-signal">
                    <span class="status-dot status-live"></span>
                    {{ __('admin.online') }}
                </div>
            </article>
        </div>
    </section>

    <section class="grid gap-4 lg:grid-cols-3 transition-opacity duration-300" wire:loading.class="opacity-60">
        <article class="surface-card p-5">
            <p class="section-headline">{{ __('admin.items_sold_today') }}</p>
            <p class="metric-value mt-4">{{ $itemsSoldToday }}</p>
        </article>

        <article class="surface-card p-5">
            <p class="section-headline">{{ __('admin.avg_order_value') }}</p>
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

    <div class="grid gap-6 xl:grid-cols-3 transition-opacity duration-300" wire:loading.class="opacity-60">
        <section class="surface-card xl:col-span-2">
            <div class="flex items-center justify-between border-b border-line bg-muted/35 px-5 py-4">
                <h2 class="font-display text-2xl font-extrabold leading-none">Top Products</h2>
                <span class="tag">Last 7 Days</span>
            </div>

            <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="border-b border-line bg-panel font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">
                        <th class="whitespace-nowrap px-5 py-3">Product</th>
                        <th class="whitespace-nowrap px-5 py-3">Qty</th>
                        <th class="whitespace-nowrap px-5 py-3">Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/65">
                    @forelse($topProducts as $product)
                        <tr class="transition-colors hover:bg-muted/35">
                            <td class="px-3 py-3 sm:px-5 sm:py-4 text-sm font-semibold uppercase tracking-tight text-ink">{{ $product->product_name_snapshot_en }}</td>
                            <td class="px-3 py-3 sm:px-5 sm:py-4 font-mono text-xs font-bold uppercase">{{ $product->qty }}</td>
                            <td class="px-3 py-3 sm:px-5 sm:py-4 font-mono text-xs font-bold uppercase">{{ formatPrice($product->revenue, $shop) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-3 py-3 sm:px-5 sm:py-4 text-center font-mono text-[10px] uppercase tracking-[0.2em] text-ink-soft">No sales yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
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
                <div class="p-5" x-data="weeklyRevenueChart({{ Js::from($weeklyRevenue) }}, '{{ $shop->currency ?? 'OMR' }}')" x-init="initChart()">
                    <canvas x-ref="revenueChart" height="220"></canvas>
                </div>
            </section>

            <section class="surface-card" x-data="{ copied: false }">
                <div class="border-b border-line bg-muted/35 px-5 py-4">
                    <h2 class="font-display text-2xl font-extrabold leading-none">Guest Menu QR</h2>
                </div>
                <div class="flex flex-col items-center gap-4 p-5">
                    <div class="rounded-xl border border-line bg-white p-3">
                        <img
                            src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode(url('/menu/' . $shop->slug)) }}"
                            alt="QR code for guest menu"
                            width="200"
                            height="200"
                            class="block"
                            loading="lazy"
                        />
                    </div>
                    <p class="max-w-full break-all text-center font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">
                        {{ url('/menu/' . $shop->slug) }}
                    </p>
                    <button
                        type="button"
                        class="btn-secondary !px-4 !py-2"
                        x-on:click="
                            navigator.clipboard.writeText('{{ url('/menu/' . $shop->slug) }}');
                            copied = true;
                            setTimeout(() => copied = false, 2000);
                        "
                    >
                        <span x-show="!copied">Copy Link</span>
                        <span x-show="copied" x-cloak>Copied!</span>
                    </button>
                </div>
            </section>
        </div>
    </div>

    <section class="surface-card transition-opacity duration-300" wire:loading.class="opacity-60">
        <div class="flex items-center justify-between border-b border-line bg-muted/35 px-5 py-4">
            <h2 class="font-display text-2xl font-extrabold leading-none">Recent Activity</h2>
            <a href="{{ route('pos.dashboard') }}" class="btn-secondary !px-3 !py-2" wire:navigate>
                Open POS
            </a>
        </div>

        <div class="overflow-x-auto">
        <table class="w-full border-collapse text-left">
            <thead>
                <tr class="border-b border-line bg-panel font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">
                    <th class="whitespace-nowrap px-5 py-3">Order ID</th>
                    <th class="whitespace-nowrap px-5 py-3">Source</th>
                    <th class="whitespace-nowrap px-5 py-3">Total</th>
                    <th class="whitespace-nowrap px-5 py-3">Status</th>
                    <th class="whitespace-nowrap px-5 py-3 text-right">Time</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line/65">
                @forelse($recentOrders as $order)
                    <tr class="transition-colors hover:bg-muted/35">
                        <td class="px-3 py-3 sm:px-5 sm:py-4 font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-ink-soft">#{{ $order->id }}</td>
                        <td class="px-3 py-3 sm:px-5 sm:py-4 text-sm font-medium text-ink">Guest</td>
                        <td class="px-3 py-3 sm:px-5 sm:py-4 font-mono text-xs font-bold uppercase text-ink">{{ formatPrice($order->total_amount, $shop) }}</td>
                        <td class="px-3 py-3 sm:px-5 sm:py-4">
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
                        <td class="px-3 py-3 sm:px-5 sm:py-4 text-right font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ $order->created_at->format('H:i:s') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-3 sm:px-5 sm:py-4 text-center font-mono text-[10px] uppercase tracking-[0.2em] text-ink-soft">No recent orders found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </section>
</div>

@script
<script>
    // New order notification sound (Web Audio API beep)
    Livewire.on('new-order-sound', () => {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = 880;
            osc.type = 'sine';
            gain.gain.setValueAtTime(0.3, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.5);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.5);
        } catch (e) { /* Audio not available */ }
    });

    // Register Alpine component (handles wire:navigate re-execution)
    Alpine.data('weeklyRevenueChart', (revenueData, currency) => ({
        chart: null,

        async initChart() {
            // Dynamically load Chart.js if not already loaded
            if (typeof Chart === 'undefined') {
                await new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
                    script.integrity = 'sha384-9nhczxUqK87bcKHh20fSQcTGD4qq5GhayNYSYWqwBkINBhOfQLg/P5HG5lF1urn4';
                    script.crossOrigin = 'anonymous';
                    script.onload = resolve;
                    script.onerror = reject;
                    document.head.appendChild(script);
                });
            }

            const canvas = this.$refs.revenueChart;
            const style = getComputedStyle(document.documentElement);
            const crema = style.getPropertyValue('--crema').trim();
            const ink = style.getPropertyValue('--ink').trim();

            const labels = revenueData.map(row => {
                const d = new Date(row.day + 'T00:00:00');
                return d.toLocaleDateString('en-US', { weekday: 'short' });
            });
            const values = revenueData.map(row => parseFloat(row.total) || 0);

            this.chart = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: `rgb(${crema} / 0.75)`,
                        hoverBackgroundColor: `rgb(${crema})`,
                        borderRadius: 6,
                        borderSkipped: false,
                        maxBarThickness: 40,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: `rgb(${ink})`,
                            titleFont: { family: 'ui-monospace, monospace', size: 10, weight: '600' },
                            bodyFont: { family: 'ui-monospace, monospace', size: 12, weight: '700' },
                            padding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                label: ctx => currency + ' ' + ctx.parsed.y.toFixed(3)
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            border: { display: false },
                            ticks: {
                                color: `rgb(${ink} / 0.45)`,
                                font: { family: 'ui-monospace, monospace', size: 10, weight: '600' },
                                padding: 4,
                            }
                        },
                        y: {
                            grid: {
                                color: `rgb(${ink} / 0.07)`,
                                drawTicks: false,
                            },
                            border: { display: false },
                            ticks: {
                                color: `rgb(${ink} / 0.4)`,
                                font: { family: 'ui-monospace, monospace', size: 10, weight: '600' },
                                padding: 8,
                                callback: val => currency + ' ' + val.toFixed(0)
                            },
                            beginAtZero: true,
                        }
                    }
                }
            });
        },

        destroy() {
            if (this.chart) this.chart.destroy();
        }
    }));
</script>
@endscript
