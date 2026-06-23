<div class="space-y-[18px] fade-rise">
    <x-slot:header>Menu Engineering</x-slot:header>

    {{-- Date Range Selector --}}
    <div class="flex flex-wrap items-center gap-3">
        <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.menu_eng_analysis_period') }}</span>
        @foreach([7, 14, 30, 90] as $days)
            <button
                wire:click="$set('rangeDays', {{ $days }})"
                @class([
                    'tag cursor-pointer transition-colors',
                    '!border-crema !bg-crema !text-panel' => $rangeDays === $days,
                ])
            >
                {{ $days }} {{ __('admin.menu_eng_days') }}
            </button>
        @endforeach
    </div>

    {{-- Summary KPI Cards --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        {{-- Stars --}}
        <div class="rounded-2xl border border-line bg-cream p-[18px]">
            <div class="flex items-center justify-between">
                <div class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.menu_eng_stars') }}</div>
                <span class="h-2.5 w-2.5 rounded-sm" style="background: var(--bite-lime);" aria-hidden="true"></span>
            </div>
            <div class="mt-3 font-display text-[32px] font-bold leading-none text-forest">{{ $counts['star'] }}</div>
        </div>

        {{-- Cash Cows --}}
        <div class="rounded-2xl border border-line bg-cream p-[18px]">
            <div class="flex items-center justify-between">
                <div class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.menu_eng_cash_cows') }}</div>
                <span class="h-2.5 w-2.5 rounded-sm" style="background: var(--bite-lime-300);" aria-hidden="true"></span>
            </div>
            <div class="mt-3 font-display text-[32px] font-bold leading-none text-forest">{{ $counts['cash_cow'] }}</div>
        </div>

        {{-- Puzzles --}}
        <div class="rounded-2xl border border-line bg-cream p-[18px]">
            <div class="flex items-center justify-between">
                <div class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.menu_eng_puzzles') }}</div>
                <span class="h-2.5 w-2.5 rounded-sm" style="background: var(--bite-olive);" aria-hidden="true"></span>
            </div>
            <div class="mt-3 font-display text-[32px] font-bold leading-none text-forest">{{ $counts['puzzle'] }}</div>
        </div>

        {{-- Dogs --}}
        <div class="rounded-2xl border border-line bg-cream p-[18px]">
            <div class="flex items-center justify-between">
                <div class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.menu_eng_dogs') }}</div>
                <span class="h-2.5 w-2.5 rounded-sm" style="background: var(--bite-ash);" aria-hidden="true"></span>
            </div>
            <div class="mt-3 font-display text-[32px] font-bold leading-none text-forest">{{ $counts['dog'] }}</div>
        </div>
    </div>

    {{-- Products Table --}}
    <section class="surface-card">
        <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
            <div class="flex items-center gap-2.5">
                <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.menu_eng_matrix') }}</h2>
            </div>
            @if($products->isNotEmpty())
                <span class="tag">{{ $products->count() }} {{ __('admin.menu_eng_items') }} &middot; {{ $rangeDays }} {{ __('admin.menu_eng_days') }}</span>
            @else
                <span class="tag">{{ $rangeDays }} {{ __('admin.menu_eng_days') }}</span>
            @endif
        </div>

        {{-- Thresholds / Averages --}}
        @if($products->isNotEmpty())
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 border-b border-line bg-cream px-[22px] py-3">
                <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.menu_eng_thresholds') }}</span>
                <span class="font-mono text-xs text-ink">
                    {{ __('admin.menu_eng_avg_qty') }}: <strong class="text-forest">{{ $avgQuantity }}</strong>
                </span>
                <span class="font-mono text-xs text-ink">
                    {{ __('admin.menu_eng_avg_revenue') }}: <strong class="text-forest"><x-price :amount="$avgRevenue" :shop="$shop" /></strong>
                </span>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-start">
                <thead>
                    <tr class="border-b border-line font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">
                        <th class="px-[22px] py-3 text-start">{{ __('admin.menu_eng_col_product') }}</th>
                        <th class="px-[22px] py-3 text-start">{{ __('admin.menu_eng_col_category') }}</th>
                        <th class="px-[22px] py-3 text-end">{{ __('admin.menu_eng_col_qty') }}</th>
                        <th class="px-[22px] py-3 text-end">{{ __('admin.menu_eng_col_revenue') }}</th>
                        <th class="px-[22px] py-3 text-start">{{ __('admin.menu_eng_col_classification') }}</th>
                        <th class="px-[22px] py-3 text-start">{{ __('admin.menu_eng_col_suggestion') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr class="border-b border-line transition-colors hover:bg-cream" wire:key="me-{{ $product->id }}">
                            <td class="px-[22px] py-3 text-sm font-semibold tracking-tight text-ink">
                                {{ $product->name_en }}
                            </td>
                            <td class="px-[22px] py-3 font-mono text-xs text-ink-soft">
                                {{ $product->category_name }}
                            </td>
                            <td class="px-[22px] py-3 text-end font-mono text-xs font-bold text-ink">
                                {{ $product->total_quantity }}
                            </td>
                            <td class="px-[22px] py-3 text-end font-display text-sm font-bold text-forest">
                                <x-price :amount="$product->total_revenue" :shop="$shop" />
                            </td>
                            <td class="px-[22px] py-3">
                                @switch($product->classification)
                                    @case('star')
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 font-mono text-[10px] font-bold uppercase tracking-[0.1em] text-forest" style="background: var(--bite-lime);">
                                            {{ __('admin.menu_eng_class_star') }}
                                        </span>
                                        @break
                                    @case('cash_cow')
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 font-mono text-[10px] font-bold uppercase tracking-[0.1em] text-forest" style="background: var(--bite-lime-100);">
                                            {{ __('admin.menu_eng_class_cash_cow') }}
                                        </span>
                                        @break
                                    @case('puzzle')
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 font-mono text-[10px] font-bold uppercase tracking-[0.1em] text-forest" style="background: color-mix(in srgb, var(--bite-olive) 20%, #fff);">
                                            {{ __('admin.menu_eng_class_puzzle') }}
                                        </span>
                                        @break
                                    @case('dog')
                                        <span class="inline-flex items-center rounded-full bg-mist px-2.5 py-1 font-mono text-[10px] font-bold uppercase tracking-[0.1em] text-ink-soft">
                                            {{ __('admin.menu_eng_class_dog') }}
                                        </span>
                                        @break
                                @endswitch
                            </td>
                            <td class="max-w-xs px-[22px] py-3 text-xs text-ink-soft">
                                {{ $product->suggestion }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-[22px] py-12">
                                <div class="rounded-2xl border border-dashed border-line px-4 py-8 text-center font-mono text-[10px] uppercase tracking-[0.18em] text-ink-soft">
                                    {{ __('admin.menu_eng_empty') }}
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
