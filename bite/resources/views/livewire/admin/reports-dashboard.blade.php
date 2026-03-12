<div class="space-y-6 fade-rise" wire:poll.30s>
    <x-slot:header>Reports</x-slot:header>

    <div class="flex flex-wrap items-center gap-4">
        <div class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Export completed orders</div>
        <a href="{{ route('admin.reports.export') }}" class="btn-secondary">Export CSV (30 days)</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <article class="rounded-xl border border-line bg-panel p-5">
            <p class="section-headline">Revenue ({{ $rangeDays }} days)</p>
            <p class="metric-value mt-4">{{ formatPrice($totalRevenue, $shop) }}</p>
        </article>
        <article class="rounded-xl border border-line bg-panel p-5">
            <p class="section-headline">Orders ({{ $rangeDays }} days)</p>
            <p class="metric-value mt-4">{{ $totalOrders }}</p>
        </article>
        <article class="rounded-xl border border-line bg-panel p-5">
            <p class="section-headline">Avg Order Value</p>
            <p class="metric-value mt-4">{{ formatPrice($avgOrder, $shop) }}</p>
        </article>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="surface-card p-5 sm:p-6">
            <h2 class="font-display text-xl font-extrabold leading-none text-ink mb-5">Revenue by Day</h2>
            <canvas id="revenueChart" height="120"></canvas>
        </div>
        <div class="surface-card p-5 sm:p-6">
            <h2 class="font-display text-xl font-extrabold leading-none text-ink mb-5">Orders by Hour</h2>
            <canvas id="hourChart" height="120"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <section class="xl:col-span-2 surface-card overflow-hidden">
            <div class="border-b border-line bg-muted/35 px-5 py-4">
                <h2 class="font-display text-xl font-extrabold leading-none">Top Products ({{ $rangeDays }} days)</h2>
            </div>
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-line bg-panel font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">
                        <th class="px-5 py-4">Product</th>
                        <th class="px-5 py-4">Qty</th>
                        <th class="px-5 py-4">Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/65">
                    @forelse($topProducts as $product)
                        <tr class="hover:bg-muted/35 transition-colors">
                            <td class="px-5 py-4 text-sm font-semibold uppercase tracking-tight text-ink">{{ $product->product_name_snapshot_en }}</td>
                            <td class="px-5 py-4 font-mono text-xs font-bold">{{ $product->qty }}</td>
                            <td class="px-5 py-4 font-mono text-xs font-bold">{{ formatPrice($product->revenue, $shop) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-10 text-center font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">No sales yet...</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="surface-card overflow-hidden">
            <div class="border-b border-line bg-muted/35 px-5 py-4">
                <h2 class="font-display text-xl font-extrabold leading-none">Payments</h2>
            </div>
            <div class="p-5 space-y-4">
                @forelse($paymentSummary as $row)
                    <div class="flex items-center justify-between rounded-xl border border-line bg-panel px-4 py-3">
                        <div>
                            <div class="font-mono text-[10px] font-semibold uppercase tracking-[0.18em] text-ink-soft">{{ strtoupper($row->payment_method) }}</div>
                            <div class="mt-1 text-sm font-medium text-ink">{{ $row->orders }} orders</div>
                        </div>
                        <div class="font-mono text-sm font-bold">{{ formatPrice($row->total, $shop) }}</div>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-line bg-panel px-4 py-6 text-center font-mono text-[10px] uppercase tracking-[0.18em] text-ink-soft">No payments yet</div>
                @endforelse
            </div>
        </section>
    </div>
</div>

@push('scripts')
    @once
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" integrity="sha384-9nhczxUqK87bcKHh20fSQcTGD4qq5GhayNYSYWqwBkINBhOfQLg/P5HG5lF1urn4" crossorigin="anonymous"></script>
    @endonce
    <script>
        const revenueLabels = @json($revenueSeries->pluck('day')->map(fn ($day) => \Carbon\Carbon::parse($day)->format('M d')));
        const revenueData = @json($revenueSeries->pluck('total'));
        const hourLabels = @json($ordersByHour->pluck('hour'));
        const hourData = @json($ordersByHour->pluck('count'));

        const renderReportsCharts = () => {
            const revenueCtx = document.getElementById('revenueChart');
            if (revenueCtx && !window.revenueChartInstance) {
                window.revenueChartInstance = new Chart(revenueCtx, {
                    type: 'line',
                    data: {
                        labels: revenueLabels,
                        datasets: [{
                            label: 'Revenue',
                            data: revenueData,
                            borderColor: '#CC5500',
                            backgroundColor: 'rgba(204,85,0,0.15)',
                            fill: true,
                            tension: 0.3,
                        }],
                    },
                    options: {
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { ticks: { color: '#1A1918' } },
                            x: { ticks: { color: '#1A1918' } },
                        },
                    },
                });
            }

            const hourCtx = document.getElementById('hourChart');
            if (hourCtx && !window.hourChartInstance) {
                window.hourChartInstance = new Chart(hourCtx, {
                    type: 'bar',
                    data: {
                        labels: hourLabels,
                        datasets: [{
                            label: 'Orders',
                            data: hourData,
                            backgroundColor: 'rgba(26,25,24,0.8)',
                        }],
                    },
                    options: {
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { ticks: { color: '#1A1918' } },
                            x: { ticks: { color: '#1A1918' } },
                        },
                    },
                });
            }
        };

        document.addEventListener('livewire:navigated', renderReportsCharts, { once: true });
        document.addEventListener('DOMContentLoaded', renderReportsCharts, { once: true });
    </script>
@endpush
