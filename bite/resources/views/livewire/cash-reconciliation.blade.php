<div class="h-full space-y-6 fade-rise">
    <x-slot:header>Cash Reconciliation</x-slot:header>

    {{-- Page Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="section-headline">End of Day</p>
            <h2 class="mt-1 text-3xl font-extrabold leading-none text-ink">Cash Reconciliation</h2>
        </div>
        <div class="flex items-center gap-2">
            <span class="tag">{{ today()->format('D, M j, Y') }}</span>
        </div>
    </div>

    {{-- Shift Summary Cards --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="surface-card p-5">
            <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Total Orders</p>
            <p class="mt-2 font-display text-3xl font-extrabold leading-none text-ink">{{ $shiftSummary['total_orders'] }}</p>
        </div>
        <div class="surface-card p-5">
            <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Total Revenue</p>
            <p class="mt-2 font-display text-3xl font-extrabold leading-none text-ink"><x-price :amount="$shiftSummary['total_revenue']" :shop="$shop" /></p>
        </div>
        <div class="surface-card p-5">
            <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Cash Payments</p>
            <p class="mt-2 font-display text-3xl font-extrabold leading-none text-ink"><x-price :amount="$shiftSummary['cash_total']" :shop="$shop" /></p>
        </div>
        <div class="surface-card p-5">
            <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Card Payments</p>
            <p class="mt-2 font-display text-3xl font-extrabold leading-none text-ink"><x-price :amount="$shiftSummary['card_total']" :shop="$shop" /></p>
        </div>
    </div>

    @if(!$showResult)
        {{-- Reconciliation Form --}}
        <div class="surface-card overflow-hidden">
            <div class="border-b border-line bg-muted/30 px-5 py-4">
                <p class="section-headline">Cash Count</p>
            </div>
            <div class="p-5 space-y-5">
                <div class="grid gap-5 sm:grid-cols-2">
                    {{-- Expected Cash (read-only) --}}
                    <div>
                        <label class="block font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-ink-soft mb-2">
                            Expected Cash in Drawer
                        </label>
                        <div class="field bg-muted/30 font-mono text-lg font-bold text-ink cursor-not-allowed">
                            {{ formatPrice($expectedCash, $shop) }}
                        </div>
                    </div>

                    {{-- Actual Cash Input --}}
                    <div>
                        <label for="actualCash" class="block font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-ink-soft mb-2">
                            Actual Cash Counted
                        </label>
                        <input
                            type="number"
                            id="actualCash"
                            wire:model="actualCash"
                            step="0.001"
                            min="0"
                            placeholder="0.000"
                            class="field font-mono text-lg font-bold"
                        >
                        @error('actualCash')
                            <p class="mt-1 font-mono text-[10px] font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label for="notes" class="block font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-ink-soft mb-2">
                        Notes (Optional)
                    </label>
                    <textarea
                        id="notes"
                        wire:model="notes"
                        rows="3"
                        placeholder="Any observations about the cash count..."
                        class="field font-mono text-xs"
                    ></textarea>
                    @error('notes')
                        <p class="mt-1 font-mono text-[10px] font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit Button --}}
                <div class="flex justify-end">
                    <button wire:click="reconcile" class="btn-primary">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                        Reconcile
                    </button>
                </div>
            </div>
        </div>
    @else
        {{-- Reconciliation Result --}}
        <div class="surface-card overflow-hidden">
            <div class="border-b border-line bg-muted/30 px-5 py-4">
                <div class="flex items-center justify-between">
                    <p class="section-headline">Reconciliation Result</p>
                    @if(abs($difference) <= 0.01)
                        <span class="tag" style="background-color: rgba(22, 163, 74, 0.1); color: rgb(22, 163, 74);">Balanced</span>
                    @else
                        <span class="tag" style="background-color: rgba(220, 38, 38, 0.1); color: rgb(220, 38, 38);">Discrepancy</span>
                    @endif
                </div>
            </div>
            <div class="p-5">
                <div class="grid gap-4 sm:grid-cols-3">
                    {{-- Expected --}}
                    <div class="surface-card p-4 text-center">
                        <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Expected</p>
                        <p class="mt-2 font-display text-2xl font-extrabold leading-none text-ink">
                            <x-price :amount="$expectedCash" :shop="$shop" />
                        </p>
                    </div>

                    {{-- Actual --}}
                    <div class="surface-card p-4 text-center">
                        <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Actual</p>
                        <p class="mt-2 font-display text-2xl font-extrabold leading-none text-ink">
                            <x-price :amount="$actualCash" :shop="$shop" />
                        </p>
                    </div>

                    {{-- Difference --}}
                    <div class="surface-card p-4 text-center" style="{{ abs($difference) <= 0.01 ? 'border-color: rgba(22, 163, 74, 0.3);' : 'border-color: rgba(220, 38, 38, 0.3);' }}">
                        <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Difference</p>
                        <p class="mt-2 font-display text-2xl font-extrabold leading-none" style="{{ abs($difference) <= 0.01 ? 'color: rgb(22, 163, 74);' : 'color: rgb(220, 38, 38);' }}">
                            {{ $difference >= 0 ? '+' : '' }}{{ formatPrice($difference, $shop) }}
                        </p>
                    </div>
                </div>

                @if($notes)
                    <div class="mt-4 rounded-lg border border-line bg-muted/20 p-4">
                        <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-ink-soft mb-1">Notes</p>
                        <p class="font-mono text-xs text-ink">{{ $notes }}</p>
                    </div>
                @endif

                {{-- Actions --}}
                <div class="mt-5 flex items-center justify-end gap-3">
                    <button wire:click="resetReconciliation" class="btn-secondary">
                        Recount
                    </button>
                    <button wire:click="closeShift" class="btn-primary">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Close Shift
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
