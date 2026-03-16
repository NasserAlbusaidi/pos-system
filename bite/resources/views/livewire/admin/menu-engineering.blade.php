<div class="space-y-6 fade-rise">
    <x-slot:header>Menu Engineering</x-slot:header>

    {{-- Date Range Selector --}}
    <div class="flex flex-wrap items-center gap-3">
        <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Analysis Period</span>
        @foreach([7, 14, 30, 90] as $days)
            <button
                wire:click="$set('rangeDays', {{ $days }})"
                @class([
                    'tag cursor-pointer transition-colors',
                    '!border-crema !bg-crema !text-panel' => $rangeDays === $days,
                ])
            >
                {{ $days }} days
            </button>
        @endforeach
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        {{-- Stars --}}
        <article class="surface-card p-5">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 text-lg" aria-hidden="true">&#9733;</span>
                <div>
                    <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Stars</p>
                    <p class="font-display text-2xl font-extrabold leading-none text-ink">{{ $counts['star'] }}</p>
                </div>
            </div>
        </article>

        {{-- Cash Cows --}}
        <article class="surface-card p-5">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-blue-200 bg-blue-50 text-lg" aria-hidden="true">&#128004;</span>
                <div>
                    <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Cash Cows</p>
                    <p class="font-display text-2xl font-extrabold leading-none text-ink">{{ $counts['cash_cow'] }}</p>
                </div>
            </div>
        </article>

        {{-- Puzzles --}}
        <article class="surface-card p-5">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-orange-200 bg-orange-50 text-lg" aria-hidden="true">&#129513;</span>
                <div>
                    <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Puzzles</p>
                    <p class="font-display text-2xl font-extrabold leading-none text-ink">{{ $counts['puzzle'] }}</p>
                </div>
            </div>
        </article>

        {{-- Dogs --}}
        <article class="surface-card p-5">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-red-200 bg-red-50 text-lg" aria-hidden="true">&#128054;</span>
                <div>
                    <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Dogs</p>
                    <p class="font-display text-2xl font-extrabold leading-none text-ink">{{ $counts['dog'] }}</p>
                </div>
            </div>
        </article>
    </div>

    {{-- Averages Info Bar --}}
    @if($products->isNotEmpty())
        <div class="flex flex-wrap items-center gap-6 rounded-xl border border-line bg-panel px-5 py-3">
            <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Thresholds</span>
            <span class="font-mono text-xs text-ink">
                Avg Qty: <strong>{{ $avgQuantity }}</strong>
            </span>
            <span class="font-mono text-xs text-ink">
                Avg Revenue: <strong><x-price :amount="$avgRevenue" :shop="$shop" /></strong>
            </span>
        </div>
    @endif

    {{-- Products Table --}}
    <section class="surface-card overflow-hidden">
        <div class="border-b border-line bg-muted/35 px-5 py-4">
            <h2 class="font-display text-xl font-extrabold leading-none">Menu Engineering Matrix</h2>
            <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">
                Last {{ $rangeDays }} days &middot; {{ $products->count() }} products analyzed
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-line bg-panel font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">
                        <th class="px-5 py-4">Product</th>
                        <th class="px-5 py-4">Category</th>
                        <th class="px-5 py-4 text-right">Qty Sold</th>
                        <th class="px-5 py-4 text-right">Revenue</th>
                        <th class="px-5 py-4">Classification</th>
                        <th class="px-5 py-4">Suggestion</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/65">
                    @forelse($products as $product)
                        <tr class="hover:bg-muted/35 transition-colors">
                            <td class="px-5 py-4 text-sm font-semibold tracking-tight text-ink">
                                {{ $product->name_en }}
                            </td>
                            <td class="px-5 py-4 font-mono text-xs text-ink-soft">
                                {{ $product->category_name }}
                            </td>
                            <td class="px-5 py-4 text-right font-mono text-xs font-bold">
                                {{ $product->total_quantity }}
                            </td>
                            <td class="px-5 py-4 text-right font-mono text-xs font-bold">
                                <x-price :amount="$product->total_revenue" :shop="$shop" />
                            </td>
                            <td class="px-5 py-4">
                                @switch($product->classification)
                                    @case('star')
                                        <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-emerald-700">
                                            &#9733; Star
                                        </span>
                                        @break
                                    @case('cash_cow')
                                        <span class="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-blue-700">
                                            &#128004; Cash Cow
                                        </span>
                                        @break
                                    @case('puzzle')
                                        <span class="inline-flex items-center gap-1.5 rounded-full border border-orange-200 bg-orange-50 px-2.5 py-1 font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-orange-700">
                                            &#129513; Puzzle
                                        </span>
                                        @break
                                    @case('dog')
                                        <span class="inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-red-50 px-2.5 py-1 font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-red-700">
                                            &#128054; Dog
                                        </span>
                                        @break
                                @endswitch
                            </td>
                            <td class="px-5 py-4 text-xs text-ink-soft max-w-xs">
                                {{ $product->suggestion }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center">
                                <p class="font-display text-lg font-bold text-ink">No products found</p>
                                <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">
                                    Add products to your menu to see the engineering matrix.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
