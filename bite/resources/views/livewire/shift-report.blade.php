<div class="h-full space-y-[18px] fade-rise shift-report-printable">
    <x-slot:header>{{ __('admin.shift_report') }}</x-slot:header>

    {{-- Print-only header --}}
    <div class="hidden print-show">
        <div style="text-align: center; margin-bottom: 16px;">
            <h1 style="font-size: 18px; font-weight: 800; margin: 0;">{{ $shop->name }} - {{ __('admin.shift_report') }}</h1>
            <p style="font-size: 12px; color: #4f5661; margin-top: 4px;">{{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}</p>
        </div>
    </div>

    @php
        $payTotal = max(0.001, (float) collect($paymentBreakdown)->sum(fn ($row) => abs((float) $row->total)));
        $payColors = ['card' => 'var(--bite-green)', 'cash' => 'var(--bite-lime-300)'];
        $payFallback = ['var(--bite-olive)', 'var(--bite-pine)', 'var(--bite-lime)', 'var(--bite-lime-200)'];
        $tpMaxQty = max(1, (int) collect($topProducts)->max('qty'));
        $hourMax = max(1, (int) collect($ordersByHour)->max('count'));
        $activeHours = collect($ordersByHour)->filter(fn ($h) => $h['count'] > 0);
    @endphp

    {{-- ===== SHIFT SUMMARY ===== --}}
    <section class="surface-card transition-opacity duration-300" wire:loading.class="opacity-60">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-[22px] py-4">
            <div class="flex items-center gap-2.5">
                <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.shift_summary') }}</h2>
            </div>
            <div class="flex items-center gap-2.5 print-hidden">
                <input type="date" wire:model.live="date" class="field w-auto font-mono text-xs font-bold uppercase">
                <button onclick="window.print()" class="btn-primary !px-4 !py-2" style="background: var(--bite-forest); border-color: var(--bite-forest);">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                    {{ __('admin.shift_print') }}
                </button>
            </div>
        </div>
        <div class="grid grid-cols-1 gap-3.5 p-[22px] sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-line bg-cream p-4 sm:p-[18px]">
                <div class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.shift_total_revenue') }}</div>
                <div class="mt-3 font-display text-[28px] font-bold leading-none text-forest sm:text-[32px]"><x-price :amount="$totalRevenue" :shop="$shop" /></div>
            </div>
            <div class="rounded-2xl border border-line bg-cream p-4 sm:p-[18px]">
                <div class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.shift_orders') }}</div>
                <div class="mt-3 font-display text-[28px] font-bold leading-none text-signal sm:text-[32px]">{{ number_format($totalOrders) }}</div>
            </div>
            <div class="rounded-2xl border border-line bg-cream p-4 sm:p-[18px]">
                <div class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.shift_avg_order') }}</div>
                <div class="mt-3 font-display text-[28px] font-bold leading-none text-olive sm:text-[32px]"><x-price :amount="$avgOrder" :shop="$shop" /></div>
            </div>
            <div class="rounded-2xl border border-line bg-cream p-4 sm:p-[18px]">
                <div class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.shift_tax_collected') }}</div>
                <div class="mt-3 font-display text-[28px] font-bold leading-none text-forest sm:text-[32px]"><x-price :amount="$totalTax" :shop="$shop" /></div>
            </div>
        </div>
    </section>

    <div class="grid gap-[18px] xl:grid-cols-2">
        {{-- ===== PAYMENT BREAKDOWN ===== --}}
        <section class="surface-card transition-opacity duration-300" wire:loading.class="opacity-60">
            <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
                <div class="flex items-center gap-2.5">
                    <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                    <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.shift_payment_breakdown') }}</h2>
                </div>
                <span class="tag">{{ __('admin.order_count', ['count' => $totalOrders]) }}</span>
            </div>
            <div class="p-[22px]">
                @if($paymentBreakdown->isNotEmpty())
                    <div class="flex h-3.5 overflow-hidden rounded-full bg-mist">
                        @foreach($paymentBreakdown as $row)
                            @php
                                $fill = $payColors[strtolower($row->method)] ?? $payFallback[$loop->index % count($payFallback)];
                                $rowTotalAbs = abs((float) $row->total);
                            @endphp
                            @if($rowTotalAbs > 0)
                                <div style="width: {{ round(($rowTotalAbs / $payTotal) * 100, 1) }}%; background: {{ $fill }};" title="{{ ucfirst($row->method) }}"></div>
                            @endif
                        @endforeach
                    </div>
                    <div class="mt-5 flex flex-col gap-3">
                        @foreach($paymentBreakdown as $row)
                            @php
                                $fill = $payColors[strtolower($row->method)] ?? $payFallback[$loop->index % count($payFallback)];
                                $rowPct = $payTotal > 0 ? round((abs((float) $row->total) / $payTotal) * 100) : 0;
                            @endphp
                            <div class="flex items-center justify-between gap-3 rounded-2xl border border-line bg-cream px-4 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $fill }};"></span>
                                    <div>
                                        <div class="font-mono text-[11px] font-bold uppercase tracking-[0.1em] text-ink">{{ ucfirst($row->method) }}</div>
                                        <div class="mt-1 font-mono text-[10px] font-medium tracking-[0.04em] text-ink-soft">{{ __('admin.shift_count') }} {{ $row->count }} · {{ $rowPct }}%</div>
                                    </div>
                                </div>
                                <div class="font-display text-lg font-bold text-forest"><x-price :amount="$row->total" :shop="$shop" /></div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-line px-4 py-8 text-center font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.shift_no_payments') }}</div>
                @endif
            </div>
        </section>

        {{-- ===== TOP PRODUCTS ===== --}}
        <section class="surface-card overflow-hidden transition-opacity duration-300" wire:loading.class="opacity-60">
            <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
                <div class="flex items-center gap-2.5">
                    <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                    <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.shift_top_products') }}</h2>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-start">
                    <thead>
                        <tr class="border-b border-line font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-ink-soft">
                            <th class="px-[22px] py-3 text-start">{{ __('admin.product') }}</th>
                            <th class="px-[22px] py-3 text-start">{{ __('admin.qty') }}</th>
                            <th class="px-[22px] py-3 text-end">{{ __('admin.revenue') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topProducts as $product)
                            @php $qtyPct = max(4, round(($product->qty / $tpMaxQty) * 100)); @endphp
                            <tr class="border-b border-line transition-colors hover:bg-cream">
                                <td class="px-[22px] py-3.5 text-sm font-semibold text-ink">{{ $product->product_name_snapshot_en }}</td>
                                <td class="px-[22px] py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="h-2 w-[70px] overflow-hidden rounded-full bg-mist">
                                            <div class="h-full rounded-full" style="width: {{ $qtyPct }}%; background: {{ $loop->first ? 'var(--bite-lime)' : 'var(--bite-green)' }};"></div>
                                        </div>
                                        <span class="font-mono text-[13px] font-semibold text-ink">{{ $product->qty }}</span>
                                    </div>
                                </td>
                                <td class="px-[22px] py-3.5 text-end font-display text-sm font-bold text-forest"><x-price :amount="$product->revenue" :shop="$shop" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-[22px] py-10 text-center font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.shift_no_products_sold') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    {{-- ===== ORDERS BY HOUR ===== --}}
    <section class="surface-card overflow-hidden transition-opacity duration-300" wire:loading.class="opacity-60">
        <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
            <div class="flex items-center gap-2.5">
                <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.orders_by_hour') }}</h2>
            </div>
            @if($peakHour && $peakHour['count'] > 0)
                <span class="tag">{{ __('admin.shift_peak', ['hour' => $peakHour['hour'], 'count' => $peakHour['count']]) }}</span>
            @endif
        </div>
        <div class="p-[22px]">
            @if($activeHours->isEmpty())
                <div class="py-8 text-center font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.shift_no_orders_date') }}</div>
            @else
                <div class="space-y-1.5">
                    @foreach($ordersByHour as $hourData)
                        @if($hourData['count'] > 0)
                            @php $isPeak = $peakHour && $hourData['hour'] === $peakHour['hour']; @endphp
                            <div class="flex items-center gap-3">
                                <span class="w-14 shrink-0 text-end font-mono text-[10px] font-semibold text-ink-soft">{{ $hourData['hour'] }}</span>
                                <div class="flex-1">
                                    <div class="h-6 rounded-md" style="width: {{ max(2, round(($hourData['count'] / $hourMax) * 100)) }}%; background: {{ $isPeak ? 'var(--bite-lime)' : 'var(--bite-green)' }};"></div>
                                </div>
                                <span class="w-8 shrink-0 font-mono text-[11px] font-bold text-forest">{{ $hourData['count'] }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- Report Footer (visible on print) --}}
    <div class="hidden print-show">
        <div style="text-align: center; margin-top: 24px; padding-top: 12px; border-top: 1px solid #c3c7cb; font-size: 10px; color: #4f5661;">
            <p>{{ __('admin.shift_generated', ['datetime' => now()->format('d/m/Y H:i')]) }}</p>
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
