<div class="space-y-[18px] fade-rise" wire:poll.30s>
    <x-slot:header>{{ __('admin.reports') }}</x-slot:header>

    @php
        $revMax = max(1, collect($revenueSeries)->max('total'));
        $revAvg = collect($revenueSeries)->avg('total') ?? 0;
        $revAvgPct = $revMax > 0 ? round(($revAvg / $revMax) * 100) : 0;
        $hourMax = max(1, collect($ordersByHour)->max('count'));
        $peakHour = collect($ordersByHour)->sortByDesc('count')->first();
        $peakHourKey = ($peakHour && $peakHour['count'] > 0) ? $peakHour['hour'] : null;
        $tpMaxQty = max(1, (int) collect($topProducts)->max('qty'));
        $payTotal = max(0.001, (float) collect($paymentSummary)->sum('total'));
        $payColors = ['card' => 'var(--bite-lime)', 'cash' => 'var(--bite-green)'];
        $payFallback = ['var(--bite-olive)', 'var(--bite-pine)', 'var(--bite-lime-300)', 'var(--bite-lime-200)'];
        $swatches = [
            'linear-gradient(135deg, var(--bite-lime-300), var(--bite-green))',
            'linear-gradient(135deg, var(--bite-olive), var(--bite-pine))',
            'linear-gradient(135deg, var(--bite-lime-200), var(--bite-olive))',
            'linear-gradient(135deg, var(--bite-green), var(--bite-forest))',
            'linear-gradient(135deg, var(--bite-lime), var(--bite-olive))',
        ];
    @endphp

    {{-- ===== PERFORMANCE SUMMARY ===== --}}
    <section class="surface-card transition-opacity duration-300" wire:loading.class="opacity-60">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-[22px] py-4">
            <div class="flex items-center gap-2.5">
                <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.reports_performance_summary') }}</h2>
            </div>
            <div class="flex items-center gap-2.5">
                <span class="tag">{{ __('admin.reports_last_days', ['days' => $rangeDays]) }}</span>
                <a href="{{ route('admin.reports.export') }}" class="btn-secondary !px-4 !py-2">{{ __('admin.export_csv', ['days' => $rangeDays]) }}</a>
            </div>
        </div>
        <div class="grid grid-cols-1 gap-3.5 p-[22px] sm:grid-cols-3">
            <div class="rounded-2xl border border-line bg-cream p-4 sm:p-[18px]">
                <div class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.revenue') }}</div>
                <div class="mt-3 font-display text-[28px] font-bold leading-none text-forest sm:text-[32px]"><x-price :amount="$totalRevenue" :shop="$shop" /></div>
            </div>
            <div class="rounded-2xl border border-line bg-cream p-4 sm:p-[18px]">
                <div class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.reports_orders_short') }}</div>
                <div class="mt-3 font-display text-[28px] font-bold leading-none text-signal sm:text-[32px]">{{ number_format($totalOrders) }}</div>
            </div>
            <div class="rounded-2xl border border-line bg-cream p-4 sm:p-[18px]">
                <div class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.reports_avg_order') }}</div>
                <div class="mt-3 font-display text-[28px] font-bold leading-none text-olive sm:text-[32px]"><x-price :amount="$avgOrder" :shop="$shop" /></div>
            </div>
        </div>
    </section>

    {{-- ===== REVENUE TREND ===== --}}
    <section class="surface-card transition-opacity duration-300" wire:loading.class="opacity-60">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-[22px] py-4">
            <div class="flex items-center gap-2.5">
                <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.reports_revenue_trend') }}</h2>
            </div>
            <div class="flex items-center gap-3.5">
                <span class="hidden items-center gap-1.5 font-mono text-[9px] font-semibold uppercase tracking-[0.12em] text-ink-soft sm:inline-flex">
                    <span class="inline-block w-[18px] border-t-[1.5px] border-dashed" style="border-color: color-mix(in srgb, var(--bite-pine) 55%, transparent);"></span>
                    {{ __('admin.reports_daily_avg') }} {{ formatPrice($revAvg, $shop) }}
                </span>
                <span class="tag">{{ __('admin.reports_days_pill', ['days' => $rangeDays]) }}</span>
            </div>
        </div>
        <div class="px-[22px] pb-[18px] pt-5">
            <div class="relative flex items-end justify-between gap-[3px]" style="height: 180px;">
                <div class="pointer-events-none absolute inset-x-0" style="bottom: {{ $revAvgPct }}%; border-top: 1.5px dashed color-mix(in srgb, var(--bite-pine) 45%, transparent);"></div>
                @foreach($revenueSeries as $point)
                    @php $pct = max(2, round(($point['total'] / $revMax) * 100)); @endphp
                    <div class="flex-1 rounded-t-[6px]"
                         style="height: {{ $pct }}%; min-height: 2px; background: {{ $loop->last ? 'var(--bite-lime)' : 'var(--bite-green)' }}; transform-origin: bottom; animation: barRise 600ms cubic-bezier(0.22,1,0.36,1) both;"
                         title="{{ \Illuminate\Support\Carbon::parse($point['day'])->format('D, M j') }} — {{ formatPrice($point['total'], $shop) }}"></div>
                @endforeach
            </div>
            <div class="mt-3 flex items-center justify-between font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">
                <span>{{ \Illuminate\Support\Carbon::parse($revenueSeries->first()['day'])->format('M j') }}</span>
                <span class="text-forest">{{ __('admin.today') }}</span>
            </div>
        </div>
    </section>

    {{-- ===== ORDERS BY HOUR + PAYMENT MIX ===== --}}
    <div class="grid grid-cols-1 gap-[18px] xl:grid-cols-2">
        {{-- Orders by hour — pure CSS bars; peak hour highlighted lime --}}
        <section class="surface-card transition-opacity duration-300" wire:loading.class="opacity-60">
            <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
                <div class="flex items-center gap-2.5">
                    <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                    <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.orders_by_hour') }}</h2>
                </div>
                @if($peakHourKey !== null)
                    <span class="tag">{{ __('admin.reports_peak', ['hour' => $peakHourKey . ':00']) }}</span>
                @endif
            </div>
            <div class="px-[22px] pb-5 pt-5">
                <div class="flex items-end gap-[3px]" style="height: 150px;">
                    @foreach($ordersByHour as $slot)
                        @php
                            $pct = max(2, round(($slot['count'] / $hourMax) * 100));
                            $isPeak = $peakHourKey !== null && $slot['hour'] === $peakHourKey;
                        @endphp
                        <div class="flex h-full flex-1 flex-col items-center justify-end gap-1.5">
                            <div class="w-full rounded-t-[4px]"
                                 style="height: {{ $pct }}%; min-height: 2px; background: {{ $isPeak ? 'var(--bite-lime)' : 'var(--bite-green)' }}; transform-origin: bottom; animation: barRise 600ms cubic-bezier(0.22,1,0.36,1) both;"
                                 title="{{ $slot['hour'] }}:00 — {{ __('admin.order_count', ['count' => $slot['count']]) }}"></div>
                            <span class="font-mono text-[8px] font-semibold uppercase tracking-tight text-ink-soft">{{ (int) $slot['hour'] % 6 === 0 ? $slot['hour'] : '' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Payment mix --}}
        <section class="surface-card transition-opacity duration-300" wire:loading.class="opacity-60">
            <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
                <div class="flex items-center gap-2.5">
                    <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                    <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.reports_payment_mix') }}</h2>
                </div>
                <span class="tag">{{ __('admin.order_count', ['count' => $totalOrders]) }}</span>
            </div>
            <div class="p-[22px]">
                @if($paymentSummary->isNotEmpty())
                    <div class="flex h-3.5 overflow-hidden rounded-full bg-mist">
                        @foreach($paymentSummary as $row)
                            @php $fill = $payColors[strtolower($row->payment_method)] ?? $payFallback[$loop->index % count($payFallback)]; @endphp
                            @if($row->total > 0)
                                <div style="width: {{ round(($row->total / $payTotal) * 100, 1) }}%; background: {{ $fill }};" title="{{ strtoupper($row->payment_method) }}"></div>
                            @endif
                        @endforeach
                    </div>
                    <div class="mt-5 flex flex-col gap-3">
                        @foreach($paymentSummary as $row)
                            @php $fill = $payColors[strtolower($row->payment_method)] ?? $payFallback[$loop->index % count($payFallback)]; @endphp
                            <div class="flex items-center justify-between gap-3 rounded-2xl border border-line bg-cream px-4 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <span class="h-2.5 w-2.5 rounded-[3px]" style="background: {{ $fill }};"></span>
                                    <div>
                                        <div class="font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-ink-soft">{{ strtoupper($row->payment_method) }}</div>
                                        <div class="mt-1 text-[13px] font-medium text-ink">{{ __('admin.order_count', ['count' => $row->orders]) }}</div>
                                    </div>
                                </div>
                                <div class="font-display text-lg font-bold text-forest"><x-price :amount="$row->total" :shop="$shop" /></div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-line px-4 py-8 text-center font-mono text-[10px] uppercase tracking-[0.18em] text-ink-soft">{{ __('admin.no_payments_yet') }}</div>
                @endif
            </div>
        </section>
    </div>

    {{-- ===== TOP PRODUCTS ===== --}}
    <section class="surface-card overflow-hidden transition-opacity duration-300" wire:loading.class="opacity-60">
        <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
            <div class="flex items-center gap-2.5">
                <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.top_products', ['days' => $rangeDays]) }}</h2>
            </div>
            <span class="tag">{{ __('admin.reports_by_revenue') }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-start">
                <thead>
                    <tr class="border-b border-line font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-ink-soft">
                        <th class="px-[22px] py-3 text-start">{{ __('admin.reports_rank') }}</th>
                        <th class="px-[22px] py-3 text-start">{{ __('admin.product') }}</th>
                        <th class="px-[22px] py-3 text-start">{{ __('admin.qty') }}</th>
                        <th class="px-[22px] py-3 text-end">{{ __('admin.revenue') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topProducts as $product)
                        @php $qtyPct = max(4, round(($product->qty / $tpMaxQty) * 100)); @endphp
                        <tr class="border-b border-line transition-colors hover:bg-cream">
                            <td class="px-[22px] py-3.5 font-mono text-[13px] font-bold text-ink-soft">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</td>
                            <td class="px-[22px] py-3.5">
                                <div class="flex items-center gap-3">
                                    <span class="h-[34px] w-[34px] shrink-0 rounded-[9px]" style="background: {{ $swatches[$loop->index % count($swatches)] }};"></span>
                                    <span class="text-sm font-semibold text-ink">{{ $product->translated('product_name_snapshot') }}</span>
                                </div>
                            </td>
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
                        <tr><td colspan="4" class="px-[22px] py-10 text-center font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.no_sales_yet') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
