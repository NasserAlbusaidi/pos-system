<div class="h-full" wire:poll.30s>
    <x-slot:header>Reports</x-slot:header>

    <div class="mb-6 flex flex-wrap items-center gap-4">
        <div class="font-mono text-[9px] uppercase tracking-widest opacity-40">Export completed orders</div>
        <a href="{{ route('admin.reports.export') }}" class="px-4 py-2 border border-ink font-mono text-[9px] uppercase tracking-widest hover:bg-ink hover:text-paper">Export CSV (30 days)</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-paper border border-ink p-6 shadow-[4px_4px_0_0_#000000]">
            <div class="font-mono text-[10px] uppercase font-bold text-ink/40 mb-3 tracking-widest">Revenue ({{ $rangeDays }} days)</div>
            <div class="text-3xl font-mono font-black">${{ number_format($totalRevenue, 2) }}</div>
        </div>
        <div class="bg-paper border border-ink p-6 shadow-[4px_4px_0_0_#000000]">
            <div class="font-mono text-[10px] uppercase font-bold text-ink/40 mb-3 tracking-widest">Orders ({{ $rangeDays }} days)</div>
            <div class="text-3xl font-mono font-black">{{ $totalOrders }}</div>
        </div>
        <div class="bg-paper border border-ink p-6 shadow-[4px_4px_0_0_#000000]">
            <div class="font-mono text-[10px] uppercase font-bold text-ink/40 mb-3 tracking-widest">Avg Order Value</div>
            <div class="text-3xl font-mono font-black">${{ number_format($avgOrder, 2) }}</div>
        </div>
    </div>

    <div class="mt-10 grid grid-cols-1 xl:grid-cols-2 gap-8">
        <div class="bg-paper border-2 border-ink p-6 shadow-[6px_6px_0_0_#000000]">
            <h2 class="font-mono font-black text-xs uppercase tracking-[0.4em] mb-6">Revenue by Day</h2>
            <canvas id="revenueChart" height="120"></canvas>
        </div>
        <div class="bg-paper border-2 border-ink p-6 shadow-[6px_6px_0_0_#000000]">
            <h2 class="font-mono font-black text-xs uppercase tracking-[0.4em] mb-6">Orders by Hour</h2>
            <canvas id="hourChart" height="120"></canvas>
        </div>
    </div>

    <div class="mt-10 grid grid-cols-1 xl:grid-cols-3 gap-8">
        <div class="xl:col-span-2 bg-paper border-2 border-ink shadow-[8px_8px_0_0_#000000] overflow-hidden">
            <div class="p-6 bg-muted border-b border-ink">
                <h2 class="font-mono font-black text-xs uppercase tracking-[0.4em]">Top Products ({{ $rangeDays }} days)</h2>
            </div>
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-ink/10 font-mono text-[10px] uppercase tracking-widest text-ink/40">
                        <th class="p-6">Product</th>
                        <th class="p-6">Qty</th>
                        <th class="p-6">Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink/5">
                    @forelse($topProducts as $product)
                        <tr class="hover:bg-muted/50 transition-colors">
                            <td class="p-6 font-mono text-xs uppercase tracking-tighter">{{ $product->product_name_snapshot }}</td>
                            <td class="p-6 font-mono text-xs font-black">{{ $product->qty }}</td>
                            <td class="p-6 font-mono text-xs font-black">${{ number_format($product->revenue, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="p-12 text-center font-mono text-xs opacity-30 italic uppercase tracking-widest">No sales yet...</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-paper border-2 border-ink shadow-[8px_8px_0_0_#000000] overflow-hidden">
            <div class="p-6 bg-muted border-b border-ink">
                <h2 class="font-mono font-black text-xs uppercase tracking-[0.4em]">Payments</h2>
            </div>
            <div class="p-6 space-y-4">
                @forelse($paymentSummary as $row)
                    <div class="border border-ink/10 p-4 flex justify-between items-center">
                        <div>
                            <div class="font-mono text-[10px] uppercase tracking-widest opacity-40">{{ strtoupper($row->payment_method) }}</div>
                            <div class="font-mono text-xs">{{ $row->orders }} orders</div>
                        </div>
                        <div class="font-mono font-black text-sm">${{ number_format($row->total, 2) }}</div>
                    </div>
                @empty
                    <div class="text-center font-mono text-[10px] uppercase tracking-widest opacity-30">No payments yet</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
    @once
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
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
