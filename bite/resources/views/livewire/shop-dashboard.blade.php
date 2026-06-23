<div wire:poll.10s>
    {{-- The dashboard opts out of the layout's default header (layouts.admin) via
         this `chromeless` slot, then draws its own single sticky header below — so
         the notification bell stays inside this component where wire: directives
         bind (a slot hoisted to the shared layout would be outside the component
         root and its wire:click would not fire). --}}
    <x-slot:chromeless></x-slot:chromeless>

    {{-- ===== HEADER (single sticky bar — matches the v2 design) ===== --}}
    <header class="sticky top-0 z-40 border-b border-line bg-cream/85 backdrop-blur-xl">
        <div class="mx-auto flex min-h-16 w-full max-w-[1600px] flex-wrap items-center justify-between gap-x-4 gap-y-2 px-4 py-2.5 sm:px-6 xl:px-8">
            <div class="fade-rise">
                <p class="font-mono text-[11px] font-semibold uppercase tracking-[0.16em] text-[color:var(--bite-green)]">{{ __('admin.bite_operations') }}</p>
                <h1 class="mt-0.5 font-display text-xl font-bold leading-none text-forest">{{ __('admin.operations_dashboard') }}</h1>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2 sm:gap-2.5">
                <span wire:loading class="loading-spinner text-ink-soft" style="width: 14px; height: 14px; border-width: 1.5px;"></span>
                <span class="hidden items-center gap-2 rounded-full border px-3 py-1.5 font-mono text-[10px] font-bold uppercase tracking-[0.14em] sm:inline-flex"
                      style="border-color: color-mix(in srgb, var(--bite-green) 35%, transparent); background: var(--bite-lime-100); color: var(--bite-pine);">
                    <span class="h-1.5 w-1.5 rounded-full" style="background: var(--bite-green); animation: pulseDot 1.8s ease-in-out infinite;"></span>
                    {{ __('admin.live_data') }}
                </span>
                <span class="hidden items-center gap-2 rounded-full border border-line bg-white px-3 py-1.5 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft md:inline-flex">
                    {{ now()->format('D, M j · H:i') }}
                </span>

                {{-- Notification bell --}}
        <div class="relative">
            <button wire:click="toggleNotifications" class="relative flex h-10 w-10 items-center justify-center rounded-xl border border-line bg-white text-forest transition-colors duration-150 hover:border-forest">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                @if($unreadCount > 0)
                    <span class="absolute -end-1 -top-1 flex h-[18px] min-w-[18px] items-center justify-center rounded-full border-2 border-cream px-1 font-mono text-[10px] font-bold" style="background: var(--bite-lime); color: var(--bite-forest);">{{ $unreadCount }}</span>
                @endif
            </button>

            @if($showNotifications)
                <div class="absolute end-0 top-full z-50 mt-2 w-[calc(100vw-2rem)] overflow-hidden rounded-2xl border border-line bg-white shadow-xl sm:w-96" style="animation: fade-rise 180ms cubic-bezier(0.23,1,0.32,1) both;">
                    <div class="flex items-center justify-between border-b border-line px-4 py-3">
                        <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.notifications') }}</p>
                        @if($notifications->isNotEmpty())
                            <button wire:click="clearAllNotifications" class="font-mono text-[9px] font-semibold uppercase tracking-[0.14em] text-ink-soft transition-colors hover:text-alert">{{ __('admin.clear_all') }}</button>
                        @endif
                    </div>
                    <div class="max-h-72 divide-y divide-line overflow-y-auto">
                        @forelse($notifications as $notification)
                            <div class="px-4 py-3 {{ $notification->read_at ? 'opacity-60' : '' }}">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-semibold text-ink">Order #{{ $notification->data['order_id'] ?? '—' }}</p>
                                        <p class="mt-0.5 font-mono text-[10px] text-ink-soft">{{ $notification->data['item_count'] ?? 0 }} {{ __('admin.items') }} &middot; @if(isset($notification->data['total']))<x-price :amount="$notification->data['total']" :shop="$shop" />@else — @endif</p>
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

                <a href="{{ route('pos.dashboard') }}" wire:navigate class="inline-flex items-center gap-2 rounded-full px-3.5 py-2.5 font-mono text-[11px] font-bold uppercase tracking-[0.14em] text-white transition-all duration-150 hover:brightness-110" style="background: var(--bite-forest);">
                    <svg class="h-[15px] w-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                    <span class="hidden sm:inline">{{ __('admin.open_pos') }}</span>
                </a>
            </div>
        </div>
    </header>

    {{-- ===== CONTENT ===== --}}
    <div class="mx-auto w-full max-w-[1600px] space-y-[18px] p-4 fade-rise sm:p-6 xl:p-8">

        {{-- ===== PULSE HERO ===== --}}
    <section class="surface-card transition-opacity duration-300" wire:loading.class="opacity-60">
        {{-- decorative blob (logical inset → mirrors correctly in RTL) --}}
        <div class="pointer-events-none absolute hidden h-[340px] w-[380px] sm:block"
             style="inset-block-start: -120px; inset-inline-end: -80px; background: radial-gradient(circle at 40% 40%, var(--bite-lime-100), color-mix(in srgb, var(--bite-lime-100) 30%, transparent)); border-radius: 42% 58% 60% 40% / 48% 42% 58% 52%; animation: blobDrift 16s ease-in-out infinite;"></div>

        <div class="relative z-[1] grid gap-7 p-6 sm:p-[28px] lg:grid-cols-[1.05fr_1fr] lg:gap-[30px]">
            {{-- LEFT: revenue + goal --}}
            <div class="flex flex-col lg:border-e lg:border-line lg:pe-[30px]">
                <div class="flex items-center gap-2.5">
                    <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.2em]" style="color: var(--bite-green);">{{ __('admin.daily_snapshot') }}</span>
                </div>

                <div class="mt-4 font-mono text-[12px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.todays_revenue') }}</div>
                <div class="mt-2 flex flex-wrap items-baseline gap-x-3.5 gap-y-2">
                    <div class="font-display font-bold leading-[0.95] tracking-tight text-forest text-[40px] sm:text-[56px]"><x-price :amount="$dailyRevenue" :shop="$shop" /></div>
                    @if($revenueDelta !== null)
                        @php $deltaUp = $revenueDelta >= 0; @endphp
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1.5 font-mono text-[12px] font-bold"
                              style="{{ $deltaUp ? 'background: var(--bite-lime-100); color: var(--bite-pine);' : 'background: var(--bite-mist); color: var(--bite-ash);' }}">
                            <svg class="h-[13px] w-[13px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                @if($deltaUp)<path d="M7 17 17 7M9 7h8v8"/>@else<path d="M7 7l10 10M9 17h8V9"/>@endif
                            </svg>
                            {{ ($deltaUp ? '+' : '') . $revenueDelta }}%
                        </span>
                    @endif
                </div>
                @if($yesterdayRevenue > 0)
                    <div class="mt-2 font-mono text-[11px] text-ink-soft">{{ __('admin.revenue_vs_yesterday', ['value' => formatPrice($yesterdayRevenue, $shop)]) }}</div>
                @endif

                {{-- Daily goal --}}
                <div class="mt-auto pt-6" x-data="{ editing: false, goalInput: {{ $dailyGoal > 0 ? $dailyGoal : 100 }} }" x-on:keydown.escape="editing = false">
                    @if($dailyGoal > 0)
                        @php
                            $goalPercent = min(round(($dailyRevenue / $dailyGoal) * 100), 999);
                            $goalBarColor = $goalPercent >= 100 ? 'var(--bite-green)' : ($goalPercent >= 50 ? 'var(--bite-lime)' : 'var(--bite-olive)');
                        @endphp
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-2.5">
                                <span class="font-mono text-[11px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.daily_goal') }}</span>
                                @if($goalPercent >= 100)
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 font-mono text-[9px] font-bold uppercase tracking-[0.1em]" style="background: var(--bite-lime); color: var(--bite-forest);">{{ __('admin.daily_goal_reached') }}</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2.5">
                                <p class="whitespace-nowrap text-[12px] text-ink-soft">
                                    <span class="font-bold text-forest"><x-price :amount="$dailyRevenue" :shop="$shop" /></span>
                                    <span class="mx-1">{{ __('admin.daily_goal_of') }}</span>
                                    <x-price :amount="$dailyGoal" :shop="$shop" />
                                </p>
                                <template x-if="!editing">
                                    <button x-on:click="editing = true; $nextTick(() => $refs.goalField.focus())" class="btn-secondary !px-3 !py-1 !text-[10px]">{{ __('admin.daily_goal_set') }}</button>
                                </template>
                            </div>
                        </div>
                        <div class="mt-2.5 h-2.5 w-full overflow-hidden rounded-full bg-mist">
                            <div class="h-full rounded-full transition-all duration-700 ease-out" style="width: {{ min($goalPercent, 100) }}%; background: {{ $goalBarColor }};"></div>
                        </div>
                        <p class="mt-2 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.daily_goal_progress', ['percent' => $goalPercent]) }}</p>
                        <template x-if="editing">
                            <div class="mt-3 flex items-center gap-2">
                                <input x-ref="goalField" x-model.number="goalInput" type="number" step="0.001" min="0" class="field h-9 w-36 font-mono text-sm" />
                                <button x-on:click="$wire.setDailyGoal(goalInput); editing = false" class="btn-primary !px-3 !py-1.5 !text-[10px]">{{ __('admin.daily_goal_update') }}</button>
                                <button x-on:click="editing = false" class="btn-secondary !px-3 !py-1.5 !text-[10px]">{{ __('admin.daily_goal_cancel') }}</button>
                            </div>
                        </template>
                    @else
                        <template x-if="!editing">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="font-mono text-[11px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.daily_goal') }}</p>
                                    <p class="mt-1 text-sm text-ink-soft">{{ __('admin.daily_goal_set_prompt') }}</p>
                                </div>
                                <button x-on:click="editing = true; $nextTick(() => $refs.goalField.focus())" class="btn-secondary !px-3 !py-2">{{ __('admin.daily_goal_set') }}</button>
                            </div>
                        </template>
                        <template x-if="editing">
                            <div class="flex items-center gap-3">
                                <p class="font-mono text-[11px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.daily_goal') }}</p>
                                <input x-ref="goalField" x-model.number="goalInput" type="number" step="0.001" min="0.001" class="field h-9 w-36 font-mono text-sm" placeholder="100.000" />
                                <button x-on:click="$wire.setDailyGoal(goalInput); editing = false" class="btn-primary !px-3 !py-1.5 !text-[10px]">{{ __('admin.daily_goal_update') }}</button>
                                <button x-on:click="editing = false" class="btn-secondary !px-3 !py-1.5 !text-[10px]">{{ __('admin.daily_goal_cancel') }}</button>
                            </div>
                        </template>
                    @endif
                </div>
            </div>

            {{-- RIGHT: KPI cluster --}}
            <div class="grid grid-cols-2 gap-3.5 self-center">
                <div class="rounded-2xl border border-line bg-cream p-4 sm:p-[18px]">
                    <div class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.orders_today') }}</div>
                    <div class="mt-3 font-display text-[32px] font-bold leading-none text-signal">{{ $ordersTodayCount }}</div>
                </div>
                <div class="rounded-2xl border border-line bg-cream p-4 sm:p-[18px]">
                    <div class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.active_orders') }}</div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="font-display text-[32px] font-bold leading-none text-olive">{{ $activeOrdersCount }}</span>
                        <span class="font-mono text-[9px] font-bold uppercase tracking-[0.1em] text-ink-soft">{{ __('admin.in_flight') }}</span>
                    </div>
                </div>
                <div class="rounded-2xl border border-line bg-cream p-4 sm:p-[18px]">
                    <div class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.items_sold_today') }}</div>
                    <div class="mt-3 font-display text-[32px] font-bold leading-none text-forest">{{ $itemsSoldToday }}</div>
                </div>
                <div class="rounded-2xl border border-line bg-cream p-4 sm:p-[18px]">
                    <div class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.avg_order_value') }}</div>
                    <div class="mt-3 font-display text-[24px] font-bold leading-none text-forest sm:text-[28px]"><x-price :amount="$avgOrderValue" :shop="$shop" /></div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== TWO-COLUMN WORKING AREA ===== --}}
    <div class="grid gap-[18px] xl:grid-cols-[1.62fr_1fr] xl:items-start">

        {{-- LEFT COLUMN --}}
        <div class="flex flex-col gap-[18px]">

            {{-- Weekly revenue --}}
            @php
                $weekMax = max(1, collect($weeklyRevenue)->max('total'));
                $weekSum = collect($weeklyRevenue)->sum('total');
                $weekAvg = count($weeklyRevenue) > 0 ? $weekSum / count($weeklyRevenue) : 0;
                $avgPct = round(($weekAvg / $weekMax) * 100);
            @endphp
            <section class="surface-card transition-opacity duration-300" wire:loading.class="opacity-60">
                <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
                    <div class="flex items-center gap-2.5">
                        <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                        <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.weekly_revenue') }}</h2>
                    </div>
                    <span class="hidden font-mono text-[11px] text-ink-soft sm:inline">{{ __('admin.seven_day_total') }} <b class="text-forest">{{ formatPrice($weekSum, $shop) }}</b></span>
                </div>
                <div class="px-6 pb-[18px] pt-4">
                    {{-- avg legend caption (kept out of the plot so it never overlaps a bar) --}}
                    <div class="mb-2.5 flex items-center justify-end gap-1.5">
                        <span class="inline-block w-3.5 border-t-[1.5px] border-dashed" style="border-color: color-mix(in srgb, var(--bite-pine) 55%, transparent);"></span>
                        <span class="font-mono text-[9px] font-semibold uppercase tracking-[0.1em]" style="color: var(--bite-pine);">{{ __('admin.avg_abbr') }} {{ formatPrice($weekAvg, $shop) }}</span>
                    </div>
                    <div class="relative flex items-end justify-between gap-3" style="height: 190px;">
                        {{-- avg reference line (label lives in the caption above) --}}
                        <div class="pointer-events-none absolute inset-x-0" style="bottom: {{ $avgPct }}%; border-top: 1.5px dashed color-mix(in srgb, var(--bite-pine) 45%, transparent);"></div>
                        @foreach($weeklyRevenue as $d)
                            @php
                                $pct = max(4, round(($d['total'] / $weekMax) * 100));
                                $isToday = $loop->last;
                            @endphp
                            <div class="relative flex h-full flex-1 flex-col items-center justify-end gap-2">
                                <span class="hidden text-center font-mono text-[9px] font-semibold sm:block {{ $isToday ? 'text-forest' : 'text-ink-soft' }}">{{ formatPrice($d['total'], $shop) }}</span>
                                <div class="w-full max-w-[38px] rounded-t-[8px]"
                                     style="height: {{ $pct }}%; min-height: 4px; background: {{ $isToday ? 'var(--bite-lime)' : 'var(--bite-green)' }}; transform-origin: bottom; animation: barRise 600ms cubic-bezier(0.22,1,0.36,1) both;"
                                     title="{{ \Illuminate\Support\Carbon::parse($d['day'])->format('D, M j') }} — {{ formatPrice($d['total'], $shop) }}"></div>
                                <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.06em] {{ $isToday ? 'text-forest' : 'text-ink-soft' }}">{{ \Illuminate\Support\Carbon::parse($d['day'])->format('D') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- Top products --}}
            <section class="surface-card transition-opacity duration-300" wire:loading.class="opacity-60">
                <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
                    <div class="flex items-center gap-2.5">
                        <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                        <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.top_products', ['days' => 7]) }}</h2>
                    </div>
                    <span class="tag">{{ __('admin.last_7_days') }}</span>
                </div>
                <div class="py-2">
                    @php
                        $swatches = [
                            'linear-gradient(135deg,#B7C40D,#7AC70C)',
                            'linear-gradient(135deg,#7AC70C,#37B34A)',
                            'linear-gradient(135deg,#98D641,#B7C40D)',
                            'linear-gradient(135deg,#0B6B2E,#37B34A)',
                            'linear-gradient(135deg,#37B34A,#0B6B2E)',
                        ];
                        $tpMax = max(1, collect($topProducts)->max('qty') ?? 0);
                    @endphp
                    @forelse($topProducts as $product)
                        @php $barPct = max(4, round(($product->qty / $tpMax) * 100)); @endphp
                        <div class="grid grid-cols-[22px_minmax(0,1fr)_120px_auto] items-center gap-3.5 px-[22px] py-2.5 transition-colors hover:bg-cream sm:grid-cols-[22px_minmax(0,1fr)_150px_auto]">
                            <span class="text-center font-mono text-[11px] font-bold text-ink-soft">{{ $loop->iteration }}</span>
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="h-[30px] w-[30px] shrink-0 rounded-[9px]" style="background: {{ $swatches[$loop->index % count($swatches)] }};"></span>
                                <span class="truncate text-sm font-semibold text-ink">{{ $product->product_name_snapshot_en }}</span>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <div class="h-[7px] flex-1 overflow-hidden rounded-full bg-mist">
                                    <div class="h-full rounded-full" style="width: {{ $barPct }}%; background: {{ $loop->first ? 'var(--bite-lime)' : 'var(--bite-green)' }};"></div>
                                </div>
                                <span class="min-w-[34px] text-end font-display text-[13px] font-bold text-forest">{{ $product->qty }}</span>
                            </div>
                            <span class="whitespace-nowrap text-end font-display text-[13px] font-bold text-forest"><x-price :amount="$product->revenue" :shop="$shop" /></span>
                        </div>
                    @empty
                        <div class="px-[22px] py-6 text-center font-mono text-[10px] uppercase tracking-[0.2em] text-ink-soft">{{ __('admin.no_sales_yet_short') }}</div>
                    @endforelse
                </div>
            </section>

            {{-- Revenue heatmap --}}
            <section class="surface-card transition-opacity duration-300" wire:loading.class="opacity-60"
                x-data="revenueHeatmap({{ Js::from($revenueHeatmap) }}, '{{ $shop->currency_symbol ?? 'OMR' }}')"
            >
                <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
                    <div class="flex items-center gap-2.5">
                        <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                        <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.revenue_heatmap') }}</h2>
                    </div>
                    <span class="tag">{{ __('admin.revenue_heatmap_desc') }}</span>
                </div>

                <div class="p-5">
                    <template x-if="hasData">
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse" style="min-width: 640px;">
                                <thead>
                                    <tr>
                                        <th class="w-14 pb-2"></th>
                                        <template x-for="h in hours" :key="h">
                                            <th class="pb-2 text-center font-mono text-[9px] font-semibold uppercase tracking-[0.1em] text-ink-soft" x-text="h.toString().padStart(2, '0')"></th>
                                        </template>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(dayLabel, dayIdx) in dayLabels" :key="dayIdx">
                                        <tr>
                                            <td class="pe-3 py-0.5 text-end font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft" x-text="dayLabel"></td>
                                            <template x-for="h in hours" :key="dayIdx + '-' + h">
                                                <td class="p-0.5">
                                                    <div
                                                        class="relative mx-auto aspect-square w-full max-w-[28px] rounded-[4px] transition-opacity"
                                                        :style="getCellStyle(dayIdx, h)"
                                                        :title="getCellTooltip(dayIdx, h)"
                                                        x-on:mouseenter="activeCell = { dow: dayIdx, hour: h }"
                                                        x-on:mouseleave="activeCell = null"
                                                    >
                                                        <div
                                                            x-show="activeCell && activeCell.dow === dayIdx && activeCell.hour === h"
                                                            x-cloak
                                                            class="pointer-events-none absolute -top-9 left-1/2 z-10 -translate-x-1/2 whitespace-nowrap rounded-md px-2 py-1 font-mono text-[10px] font-bold text-white shadow-lg"
                                                            style="background: var(--bite-forest);"
                                                            x-text="getCellTooltip(dayIdx, h)"
                                                        ></div>
                                                    </div>
                                                </td>
                                            </template>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>

                            {{-- Legend --}}
                            <div class="mt-4 flex items-center justify-end gap-2">
                                <span class="font-mono text-[9px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.no_sales_yet_short') }}</span>
                                <div class="flex gap-0.5">
                                    <template x-for="opacity in [0.10, 0.30, 0.55, 0.80, 1]" :key="opacity">
                                        <div class="h-[13px] w-[13px] rounded-[3px]" :style="'background: rgb(122 199 12 / ' + opacity + ');'"></div>
                                    </template>
                                </div>
                                <span class="font-mono text-[9px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.revenue') }}</span>
                            </div>
                        </div>
                    </template>

                    <template x-if="!hasData">
                        <div class="rounded-2xl border border-dashed border-line px-4 py-8 text-center">
                            <p class="font-mono text-[10px] uppercase tracking-[0.18em] text-ink-soft">{{ __('admin.heatmap_no_data') }}</p>
                        </div>
                    </template>
                </div>
            </section>

        </div>

        {{-- RIGHT COLUMN --}}
        <div class="flex flex-col gap-[18px]">

            {{-- Order pipeline --}}
            @php
                $pipelineOrder = ['unpaid', 'paid', 'preparing', 'ready', 'completed', 'cancelled'];
                $pipelineFill = [
                    'unpaid' => 'var(--bite-line)',
                    'paid' => 'var(--bite-lime-200)',
                    'preparing' => 'var(--bite-lime-300)',
                    'ready' => 'var(--bite-lime)',
                    'completed' => 'var(--bite-green)',
                    'cancelled' => 'var(--bite-ash)',
                ];
                $pipeTotal = collect($pipelineOrder)->sum(fn ($s) => $ordersByStatus[$s] ?? 0);
            @endphp
            <section class="surface-card transition-opacity duration-300" wire:loading.class="opacity-60">
                <div class="flex items-center justify-between border-b border-line px-5 py-4">
                    <div class="flex items-center gap-2.5">
                        <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                        <h2 class="font-display text-[17px] font-bold leading-none text-forest">{{ __('admin.order_pipeline') }}</h2>
                    </div>
                    <span class="font-mono text-[11px] font-semibold text-ink-soft">{{ __('admin.orders_count_today', ['count' => $pipeTotal]) }}</span>
                </div>
                <div class="p-5">
                    <div class="flex h-3 overflow-hidden rounded-full bg-mist">
                        @foreach($pipelineOrder as $status)
                            @php $count = $ordersByStatus[$status] ?? 0; @endphp
                            @if($count > 0)
                                <div style="width: {{ round(($count / max(1, $pipeTotal)) * 100, 1) }}%; background: {{ $pipelineFill[$status] }};" title="{{ __('admin.status_' . $status) }}"></div>
                            @endif
                        @endforeach
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2.5">
                        @foreach($pipelineOrder as $status)
                            <div class="flex items-center gap-2">
                                <span class="h-[9px] w-[9px] shrink-0 rounded-[3px]" style="background: {{ $pipelineFill[$status] }};"></span>
                                <span class="flex-1 text-[12.5px] text-ink">{{ __('admin.status_' . $status) }}</span>
                                <span class="font-display text-[13px] font-bold text-forest">{{ $ordersByStatus[$status] ?? 0 }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- Payments today --}}
            @php
                $payTotal = collect($paymentSummary)->sum('total');
                $payColors = ['card' => 'var(--bite-green)', 'cash' => 'var(--bite-lime-300)'];
                $payFallback = ['var(--bite-olive)', 'var(--bite-pine)', 'var(--bite-lime)', 'var(--bite-lime-200)'];
            @endphp
            <section class="surface-card transition-opacity duration-300" wire:loading.class="opacity-60">
                <div class="flex items-center justify-between border-b border-line px-5 py-4">
                    <div class="flex items-center gap-2.5">
                        <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                        <h2 class="font-display text-[17px] font-bold leading-none text-forest">{{ __('admin.payments_today') }}</h2>
                    </div>
                </div>
                <div class="p-5">
                    @if(!empty($paymentSummary))
                        <div class="flex h-3 overflow-hidden rounded-full bg-mist">
                            @foreach($paymentSummary as $method => $summary)
                                @php $fill = $payColors[strtolower($method)] ?? $payFallback[$loop->index % count($payFallback)]; @endphp
                                @if($summary['total'] > 0)
                                    <div style="width: {{ round(($summary['total'] / max(0.001, $payTotal)) * 100, 1) }}%; background: {{ $fill }};" title="{{ strtoupper($method) }}"></div>
                                @endif
                            @endforeach
                        </div>
                        <div class="mt-4 flex flex-col gap-2.5">
                            @foreach($paymentSummary as $method => $summary)
                                @php $fill = $payColors[strtolower($method)] ?? $payFallback[$loop->index % count($payFallback)]; @endphp
                                <div class="flex items-center justify-between rounded-2xl border border-line px-3.5 py-3">
                                    <div class="flex items-center gap-2.5">
                                        <span class="h-[9px] w-[9px] rounded-[3px]" style="background: {{ $fill }};"></span>
                                        <div>
                                            <div class="font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-ink-soft">{{ strtoupper($method) }}</div>
                                            <div class="mt-0.5 text-[12.5px] text-ink">{{ __('admin.payment_orders_count', ['count' => $summary['orders']]) }}</div>
                                        </div>
                                    </div>
                                    <div class="font-display text-base font-bold text-forest"><x-price :amount="$summary['total']" :shop="$shop" /></div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-2xl border border-dashed border-line px-4 py-6 text-center font-mono text-[10px] uppercase tracking-[0.18em] text-ink-soft">{{ __('admin.no_payments_yet_short') }}</div>
                    @endif
                </div>
            </section>

            {{-- Guest menu QR --}}
            <section class="surface-card" x-data="{ copied: false }">
                <div class="flex items-center justify-between border-b border-line px-5 py-4">
                    <div class="flex items-center gap-2.5">
                        <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                        <h2 class="font-display text-[17px] font-bold leading-none text-forest">{{ __('admin.guest_menu_qr') }}</h2>
                    </div>
                </div>
                <div class="flex items-center gap-[18px] p-5">
                    <div class="shrink-0 rounded-2xl border border-line bg-white p-2.5">
                        <img
                            src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&margin=0&color=004225&data={{ urlencode(url('/menu/' . $shop->slug)) }}"
                            alt="QR code for guest menu"
                            width="120"
                            height="120"
                            class="block rounded-md"
                            loading="lazy"
                        />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="break-all font-mono text-[10px] font-semibold uppercase leading-relaxed tracking-[0.1em] text-ink-soft">{{ url('/menu/' . $shop->slug) }}</p>
                        <button type="button" class="btn-secondary mt-3 w-full !py-2.5"
                            x-on:click="navigator.clipboard.writeText('{{ url('/menu/' . $shop->slug) }}'); copied = true; setTimeout(() => copied = false, 2000);">
                            <span x-show="!copied">{{ __('admin.copy_link') }}</span>
                            <span x-show="copied" x-cloak>{{ __('admin.copied') }}</span>
                        </button>
                    </div>
                </div>
            </section>

        </div>
    </div>

    {{-- ===== RECENT ACTIVITY ===== --}}
    <section class="surface-card transition-opacity duration-300" wire:loading.class="opacity-60">
        <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
            <div class="flex items-center gap-2.5">
                <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.recent_activity') }}</h2>
            </div>
            <a href="{{ route('pos.dashboard') }}" class="btn-secondary !px-4 !py-2" wire:navigate>{{ __('admin.open_pos') }}</a>
        </div>

        <div class="overflow-x-auto">
        <table class="w-full border-collapse text-start">
            <thead>
                <tr class="border-b border-line font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-ink-soft">
                    <th class="whitespace-nowrap px-[22px] py-3 text-start">{{ __('admin.order_id') }}</th>
                    <th class="whitespace-nowrap px-[22px] py-3 text-start">{{ __('admin.customer') }}</th>
                    <th class="whitespace-nowrap px-[22px] py-3 text-end">{{ __('admin.total') }}</th>
                    <th class="whitespace-nowrap px-[22px] py-3 text-start">{{ __('admin.status') }}</th>
                    <th class="whitespace-nowrap px-[22px] py-3 text-end">{{ __('admin.time') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentOrders as $order)
                    @php
                        $badge = match ($order->status) {
                            'completed' => 'background: var(--bite-lime); color: var(--bite-forest);',
                            'ready', 'preparing', 'paid' => 'background: var(--bite-lime-100); color: var(--bite-pine);',
                            'cancelled' => 'background: var(--bite-mist); color: var(--bite-ash);',
                            default => 'background: var(--bite-mist); color: var(--bite-ash);',
                        };
                    @endphp
                    <tr class="border-b border-line transition-colors hover:bg-cream">
                        <td class="px-[22px] py-3.5 font-mono text-xs font-bold uppercase tracking-[0.06em] text-ink-soft">#{{ $order->id }}</td>
                        <td class="px-[22px] py-3.5 text-sm text-ink">{{ $order->customer_name ?: __('admin.source_counter') }}</td>
                        <td class="px-[22px] py-3.5 text-end font-display text-sm font-bold text-forest"><x-price :amount="$order->total_amount" :shop="$shop" /></td>
                        <td class="px-[22px] py-3.5">
                            <span class="inline-flex rounded-full px-3 py-1.5 font-mono text-[11px] font-bold uppercase tracking-[0.1em]" style="{{ $badge }}">{{ __('admin.status_' . $order->status) }}</span>
                        </td>
                        <td class="px-[22px] py-3.5 text-end font-mono text-xs font-semibold tracking-[0.04em] text-ink-soft">{{ $order->created_at->format('H:i:s') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-[22px] py-6 text-center font-mono text-[10px] uppercase tracking-[0.2em] text-ink-soft">{{ __('admin.no_recent_orders') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </section>
    </div>
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

    // Revenue heatmap Alpine component — lime intensity ramp (Bite brand).
    Alpine.data('revenueHeatmap', (rawData, currency) => ({
        grid: {},
        maxRevenue: 0,
        hasData: false,
        activeCell: null,
        hours: Array.from({ length: 18 }, (_, i) => i + 6), // 6am - 23pm
        dayLabels: [
            @json(__('admin.heatmap_day_sun')),
            @json(__('admin.heatmap_day_mon')),
            @json(__('admin.heatmap_day_tue')),
            @json(__('admin.heatmap_day_wed')),
            @json(__('admin.heatmap_day_thu')),
            @json(__('admin.heatmap_day_fri')),
            @json(__('admin.heatmap_day_sat')),
        ],

        init() {
            const grid = {};
            let max = 0;
            for (const entry of rawData) {
                const dow = entry.dow;
                const hour = entry.hour;
                const total = parseFloat(entry.total) || 0;
                if (!grid[dow]) grid[dow] = {};
                grid[dow][hour] = total;
                if (total > max) max = total;
            }
            this.grid = grid;
            this.maxRevenue = max;
            this.hasData = rawData.length > 0;
        },

        getRevenue(dow, hour) {
            return (this.grid[dow] && this.grid[dow][hour]) ? this.grid[dow][hour] : 0;
        },

        getCellStyle(dow, hour) {
            const rev = this.getRevenue(dow, hour);
            if (rev === 0 || this.maxRevenue === 0) {
                return 'background: rgb(122 199 12 / 0.08);';
            }
            const ratio = rev / this.maxRevenue;
            const opacity = 0.15 + (ratio * 0.85);
            return `background: rgb(122 199 12 / ${opacity.toFixed(2)});`;
        },

        getCellTooltip(dow, hour) {
            const rev = this.getRevenue(dow, hour);
            const hourStr = hour.toString().padStart(2, '0') + ':00';
            return `${this.dayLabels[dow]} ${hourStr} — ${currency} ${rev.toFixed(3)}`;
        },
    }));
</script>
@endscript
