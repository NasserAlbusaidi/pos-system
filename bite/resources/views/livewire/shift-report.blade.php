<div class="h-full space-y-6 fade-rise shift-report-printable">
    <x-slot:header>Shift Report</x-slot:header>

    {{-- Controls --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between print-hidden">
        <div>
            <p class="section-headline">End of Day</p>
            <h2 class="mt-1 text-3xl font-extrabold leading-none text-ink">Daily Shift Report</h2>
        </div>
        <div class="flex items-center gap-3">
            <input
                type="date"
                wire:model.live="date"
                class="field w-auto font-mono text-xs font-bold uppercase"
            >
            <button
                onclick="window.print()"
                class="btn-primary"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                Print
            </button>
        </div>
    </div>

    {{-- Print-only header --}}
    <div class="hidden print-show" style="display: none;">
        <div style="text-align: center; margin-bottom: 16px;">
            <h1 style="font-size: 18px; font-weight: 800; margin: 0;">{{ $shop->name }} - Shift Report</h1>
            <p style="font-size: 12px; color: #4f5661; margin-top: 4px;">{{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}</p>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="surface-card p-5">
            <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Total Revenue</p>
            <p class="mt-2 font-display text-3xl font-extrabold leading-none text-ink">{{ formatPrice($totalRevenue, $shop) }}</p>
        </div>
        <div class="surface-card p-5">
            <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Orders</p>
            <p class="mt-2 font-display text-3xl font-extrabold leading-none text-ink">{{ $totalOrders }}</p>
        </div>
        <div class="surface-card p-5">
            <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Avg Order Value</p>
            <p class="mt-2 font-display text-3xl font-extrabold leading-none text-ink">{{ formatPrice($avgOrder, $shop) }}</p>
        </div>
        <div class="surface-card p-5">
            <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Tax Collected</p>
            <p class="mt-2 font-display text-3xl font-extrabold leading-none text-ink">{{ formatPrice($totalTax, $shop) }}</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        {{-- Payment Breakdown --}}
        <div class="surface-card overflow-hidden">
            <div class="border-b border-line bg-muted/30 px-5 py-4">
                <p class="section-headline">Payment Breakdown</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-line">
                            <th class="px-5 py-3 font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Method</th>
                            <th class="px-5 py-3 text-right font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Count</th>
                            <th class="px-5 py-3 text-right font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/50">
                        @forelse($paymentBreakdown as $row)
                            <tr>
                                <td class="px-5 py-3 font-mono text-xs font-semibold uppercase tracking-[0.1em] text-ink">{{ ucfirst($row->method) }}</td>
                                <td class="px-5 py-3 text-right font-mono text-xs font-bold text-ink">{{ $row->count }}</td>
                                <td class="px-5 py-3 text-right font-mono text-xs font-bold text-ink">{{ formatPrice($row->total, $shop) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-8 text-center font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">No payments recorded</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Top Products --}}
        <div class="surface-card overflow-hidden">
            <div class="border-b border-line bg-muted/30 px-5 py-4">
                <p class="section-headline">Top 5 Products</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-line">
                            <th class="px-5 py-3 font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Product</th>
                            <th class="px-5 py-3 text-right font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Qty</th>
                            <th class="px-5 py-3 text-right font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/50">
                        @forelse($topProducts as $product)
                            <tr>
                                <td class="px-5 py-3 font-mono text-xs font-semibold uppercase tracking-tight text-ink">{{ $product->product_name_snapshot }}</td>
                                <td class="px-5 py-3 text-right font-mono text-xs font-bold text-ink">{{ $product->qty }}</td>
                                <td class="px-5 py-3 text-right font-mono text-xs font-bold text-ink">{{ formatPrice($product->revenue, $shop) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-8 text-center font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">No products sold</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Hourly Breakdown --}}
    <div class="surface-card overflow-hidden">
        <div class="border-b border-line bg-muted/30 px-5 py-4">
            <div class="flex items-center justify-between">
                <p class="section-headline">Orders by Hour</p>
                @if($peakHour && $peakHour['count'] > 0)
                    <span class="tag">
                        Peak: {{ $peakHour['hour'] }} ({{ $peakHour['count'] }} orders)
                    </span>
                @endif
            </div>
        </div>
        <div class="p-5">
            @php
                $maxCount = $ordersByHour->max('count');
                $activeHours = $ordersByHour->filter(fn ($h) => $h['count'] > 0);
            @endphp

            @if($activeHours->isEmpty())
                <div class="py-8 text-center font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">
                    No orders for this date
                </div>
            @else
                <div class="space-y-1">
                    @foreach($ordersByHour as $hourData)
                        @if($hourData['count'] > 0)
                            <div class="flex items-center gap-3">
                                <span class="w-14 shrink-0 text-right font-mono text-[10px] font-semibold text-ink-soft">{{ $hourData['hour'] }}</span>
                                <div class="flex-1">
                                    <div
                                        class="h-6 rounded-md"
                                        style="width: {{ $maxCount > 0 ? max(2, ($hourData['count'] / $maxCount) * 100) : 0 }}%; background-color: rgb(var(--crema) / 0.7);"
                                    ></div>
                                </div>
                                <span class="w-8 shrink-0 font-mono text-[10px] font-bold text-ink">{{ $hourData['count'] }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Report Footer (visible on print) --}}
    <div class="hidden print-show" style="display: none;">
        <div style="text-align: center; margin-top: 24px; padding-top: 12px; border-top: 1px solid #c3c7cb; font-size: 10px; color: #4f5661;">
            <p>Generated {{ now()->format('d/m/Y H:i') }} | Powered by Bite</p>
        </div>
    </div>
</div>

@push('scripts')
<style>
    @media print {
        .print-show {
            display: block !important;
        }

        .shift-report-printable .surface-card {
            page-break-inside: avoid;
            border: 1px solid #e0e0e0 !important;
            box-shadow: none !important;
            border-radius: 4px !important;
            margin-bottom: 12px;
        }

        .shift-report-printable .surface-card::before {
            display: none !important;
        }
    }
</style>
@endpush
