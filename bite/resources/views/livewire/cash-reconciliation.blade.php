<div class="space-y-[18px] fade-rise">
    <x-slot:header>Cash Reconciliation</x-slot:header>

    {{-- Page Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.2em] text-signal">{{ __('admin.end_of_day') }}</p>
            <h2 class="mt-1 font-display text-[21px] font-bold leading-none text-forest">{{ __('admin.cash_reconciliation') }}</h2>
        </div>
        <div class="flex items-center gap-2">
            <span class="tag">{{ today()->format('D, M j, Y') }}</span>
        </div>
    </div>

    {{-- Shift Summary Cards --}}
    <div class="grid gap-[14px] sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-2xl border border-line bg-cream p-[18px]">
            <div class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.total_orders') }}</div>
            <div class="mt-3 font-display text-[32px] font-bold leading-none text-forest">{{ $shiftSummary['total_orders'] }}</div>
        </div>
        <div class="rounded-2xl border border-line bg-cream p-[18px]">
            <div class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.total_revenue') }}</div>
            <div class="mt-3 font-display text-[32px] font-bold leading-none text-olive"><x-price :amount="$shiftSummary['total_revenue']" :shop="$shop" /></div>
        </div>
        <div class="rounded-2xl border border-line bg-cream p-[18px]">
            <div class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.cash_payments') }}</div>
            <div class="mt-3 font-display text-[32px] font-bold leading-none text-forest"><x-price :amount="$shiftSummary['cash_total']" :shop="$shop" /></div>
        </div>
        <div class="rounded-2xl border border-line bg-cream p-[18px]">
            <div class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.card_payments') }}</div>
            <div class="mt-3 font-display text-[32px] font-bold leading-none text-olive"><x-price :amount="$shiftSummary['card_total']" :shop="$shop" /></div>
        </div>
        <div class="rounded-2xl border border-line bg-cream p-[18px]">
            <div class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.voucher_payments') }}</div>
            <div class="mt-3 font-display text-[32px] font-bold leading-none text-signal"><x-price :amount="$shiftSummary['voucher_total']" :shop="$shop" /></div>
        </div>
    </div>

    @if(!$showResult)
        {{-- Reconciliation Form --}}
        <section class="surface-card">
            <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
                <div class="flex items-center gap-2.5">
                    <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                    <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.cash_count') }}</h2>
                </div>
                <span class="tag">{{ __('admin.end_of_shift') }}</span>
            </div>
            <div class="space-y-5 p-[22px]">
                <div class="grid gap-5 sm:grid-cols-2">
                    {{-- Expected Cash (read-only) --}}
                    <div>
                        <label class="mb-2 block font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">
                            {{ __('admin.expected_cash_in_drawer') }}
                        </label>
                        <div class="field cursor-not-allowed bg-mist font-mono text-lg font-bold text-forest">
                            {{ formatPrice($expectedCash, $shop) }}
                        </div>
                    </div>

                    {{-- Actual Cash Input --}}
                    <div>
                        <label for="actualCash" class="mb-2 block font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">
                            {{ __('admin.actual_cash_counted') }}
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
                            <p class="mt-1 font-mono text-[10px] font-semibold text-alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label for="notes" class="mb-2 block font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">
                        {{ __('admin.notes_optional') }}
                    </label>
                    <textarea
                        id="notes"
                        wire:model="notes"
                        rows="3"
                        placeholder="{{ __('admin.cash_count_notes_placeholder') }}"
                        class="field font-mono text-xs"
                    ></textarea>
                    @error('notes')
                        <p class="mt-1 font-mono text-[10px] font-semibold text-alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit Button --}}
                <div class="flex justify-end">
                    <button wire:click="reconcile" class="btn-primary" style="background: var(--bite-forest); border-color: var(--bite-forest);">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                        {{ __('admin.reconcile') }}
                    </button>
                </div>
            </div>
        </section>
    @else
        {{-- Reconciliation Result --}}
        <section class="surface-card">
            <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
                <div class="flex items-center gap-2.5">
                    <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                    <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.reconciliation_result') }}</h2>
                </div>
                @if(abs($difference) <= 0.01)
                    <span class="tag" style="background-color: rgba(22, 163, 74, 0.1); color: rgb(22, 163, 74);">{{ __('admin.balanced') }}</span>
                @else
                    <span class="tag" style="background-color: rgb(var(--alert) / 0.1); color: rgb(var(--alert));">{{ __('admin.discrepancy') }}</span>
                @endif
            </div>
            <div class="p-[22px]">
                <div class="grid gap-4 sm:grid-cols-3">
                    {{-- Expected --}}
                    <div class="rounded-2xl border border-line bg-cream p-[18px] text-center">
                        <div class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.expected') }}</div>
                        <div class="mt-3 font-display text-[28px] font-bold leading-none text-forest">
                            <x-price :amount="$expectedCash" :shop="$shop" />
                        </div>
                    </div>

                    {{-- Actual --}}
                    <div class="rounded-2xl border border-line bg-cream p-[18px] text-center">
                        <div class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.actual') }}</div>
                        <div class="mt-3 font-display text-[28px] font-bold leading-none text-forest">
                            <x-price :amount="$actualCash" :shop="$shop" />
                        </div>
                    </div>

                    {{-- Difference --}}
                    <div class="rounded-2xl border bg-cream p-[18px] text-center" style="{{ abs($difference) <= 0.01 ? 'border-color: rgba(22, 163, 74, 0.35);' : 'border-color: rgb(var(--alert) / 0.35);' }}">
                        <div class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.difference') }}</div>
                        <div class="mt-3 font-display text-[28px] font-bold leading-none" style="{{ abs($difference) <= 0.01 ? 'color: rgb(22, 163, 74);' : 'color: rgb(var(--alert));' }}">
                            {{ $difference >= 0 ? '+' : '' }}{{ formatPrice($difference, $shop) }}
                        </div>
                    </div>
                </div>

                @if($notes)
                    <div class="mt-4 rounded-2xl border border-line bg-mist p-4">
                        <div class="mb-1 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.notes') }}</div>
                        <p class="font-mono text-xs text-ink">{{ $notes }}</p>
                    </div>
                @endif

                {{-- Actions --}}
                <div class="mt-5 flex items-center justify-end gap-3">
                    <button wire:click="resetReconciliation" class="btn-secondary">
                        {{ __('admin.recount') }}
                    </button>
                    <button wire:click="closeShift" class="btn-primary" style="background: var(--bite-forest); border-color: var(--bite-forest);">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        {{ __('admin.close_shift') }}
                    </button>
                </div>
            </div>
        </section>
    @endif
</div>
