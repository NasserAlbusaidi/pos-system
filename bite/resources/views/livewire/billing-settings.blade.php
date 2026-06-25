<div class="space-y-[18px] fade-rise">
    <x-slot:header>{{ __('admin.billing_subscription') }}</x-slot:header>

    {{-- Checkout callback notices --}}
    @if($checkoutStatus === 'success')
        <div class="flex items-center gap-3 rounded-2xl border p-4" style="border-color: rgb(var(--signal) / 0.5); background-color: rgb(var(--signal) / 0.08);">
            <svg class="h-5 w-5 shrink-0" style="color: rgb(var(--signal));" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            <p class="text-sm font-medium" style="color: rgb(var(--signal));">{{ __('admin.billing_activated') }}</p>
        </div>
    @elseif($checkoutStatus === 'cancelled')
        <div class="flex items-center gap-3 rounded-2xl border border-line p-4 bg-cream">
            <svg class="h-5 w-5 shrink-0 text-ink-soft" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            <p class="text-sm text-ink-soft">{{ __('admin.billing_checkout_cancelled') }}</p>
        </div>
    @endif

    {{-- Billing notice from middleware --}}
    @if(session('billing_notice'))
        <div class="flex items-center gap-3 rounded-2xl border p-4" style="border-color: rgb(var(--alert) / 0.5); background-color: rgb(var(--alert) / 0.08);">
            <svg class="h-5 w-5 shrink-0" style="color: rgb(var(--alert));" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
            <p class="text-sm font-medium" style="color: rgb(var(--alert));">{{ session('billing_notice') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-[18px] xl:grid-cols-3">
        {{-- Left column: Main billing content --}}
        <div class="space-y-[18px] xl:col-span-2">

            {{-- Current Plan --}}
            <section class="surface-card">
                <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
                    <div class="flex items-center gap-2.5">
                        <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                        <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.billing_current_plan') }}</h2>
                    </div>
                    <span class="tag">{{ __('admin.billing_status_details') }}</span>
                </div>

                <div class="space-y-4 p-[22px]">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="font-display text-[34px] font-bold leading-none text-forest">
                            {{ $plans[$currentPlan]['name'] ?? 'Free' }}
                        </span>

                        @php
                            $statusColor = match($statusLabel) {
                                'active' => '--signal',
                                'trialing' => '--crema',
                                'cancelled' => '--ink-soft',
                                'past_due', 'incomplete' => '--alert',
                                'expired' => '--alert',
                                default => '--ink-soft',
                            };
                        @endphp
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 font-mono text-[9px] font-bold uppercase tracking-[0.16em]"
                              style="background-color: rgb(var({{ $statusColor }}) / 0.12); color: rgb(var({{ $statusColor }})); border: 1px solid rgb(var({{ $statusColor }}) / 0.25);">
                            <span class="inline-block h-1.5 w-1.5 rounded-full" style="background-color: rgb(var({{ $statusColor }}));"></span>
                            {{ str_replace('_', ' ', ucfirst($statusLabel)) }}
                        </span>
                    </div>

                    <div class="flex items-baseline gap-2">
                        @if(($plans[$currentPlan]['price'] ?? 0) > 0)
                            <span class="font-display text-[22px] font-bold leading-none text-pine">{{ $plans[$currentPlan]['price'] }}</span>
                            <span class="font-mono text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-soft">{{ __('admin.billing_omr_month') }}</span>
                        @else
                            <span class="font-display text-[22px] font-bold leading-none text-pine">{{ __('admin.billing_free') }}</span>
                        @endif
                    </div>

                    @if($isOnTrial)
                        <div class="flex items-start gap-3 rounded-2xl border p-4" style="border-color: rgb(var(--crema) / 0.3); background-color: rgb(var(--crema) / 0.06);">
                            <svg class="mt-0.5 h-5 w-5 shrink-0" style="color: rgb(var(--crema));" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <div>
                                <p class="text-sm font-bold" style="color: rgb(var(--crema));">{{ __('admin.billing_trial_remaining', ['days' => $trialDaysRemaining, 'label' => $trialDaysRemaining === 1 ? __('admin.billing_trial_day') : __('admin.billing_trial_days')]) }}</p>
                                <p class="mt-1 text-xs text-ink-soft">{{ __('admin.billing_upgrade_hint') }}</p>
                            </div>
                        </div>
                    @endif

                    @if($renewalDate)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-ink-soft">
                                {{ $statusLabel === 'cancelled' ? __('admin.billing_access_until') : ($isOnTrial ? __('admin.billing_trial_ends') : __('admin.billing_next_renewal')) }}
                            </span>
                            <span class="font-mono font-bold text-ink">{{ $renewalDate->format('M j, Y') }}</span>
                        </div>
                    @endif

                    @if($currentPlan === 'pro' && $statusLabel === 'active')
                        <p class="text-sm text-ink-soft">
                            {{ __('admin.billing_pro_active_desc') }}
                        </p>
                    @endif
                </div>
            </section>

            {{-- Plan Comparison --}}
            <section class="surface-card">
                <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
                    <div class="flex items-center gap-2.5">
                        <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                        <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.billing_plans') }}</h2>
                    </div>
                    <span class="tag">{{ __('admin.billing_compare') }}</span>
                </div>

                <div class="p-[22px]">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @foreach($plans as $planKey => $plan)
                            @php
                                $isCurrentPlan = $currentPlan === $planKey;
                                $isPro = $planKey === 'pro';
                            @endphp
                            <div class="relative flex flex-col gap-4 rounded-2xl p-5 transition-all duration-200 {{ $isCurrentPlan ? '' : 'hover:shadow-md' }}"
                                 style="border: {{ $isCurrentPlan ? '2px solid var(--bite-lime)' : '1px solid rgb(var(--line))' }};
                                        background-color: {{ $isCurrentPlan ? '#fff' : 'var(--bite-cream)' }};">

                                @if($isCurrentPlan)
                                    <span class="absolute end-3 top-3 inline-flex items-center rounded-full px-2 py-0.5 font-mono text-[8px] font-bold uppercase tracking-[0.16em]"
                                          style="background-color: var(--bite-lime); color: var(--bite-forest);">
                                        {{ __('admin.billing_current') }}
                                    </span>
                                @endif

                                <div>
                                    <h3 class="font-display text-lg font-bold text-forest">{{ $plan['name'] }}</h3>
                                    <div class="mt-1 flex items-baseline gap-1">
                                        @if($plan['price'] > 0)
                                            <span class="font-display text-[28px] font-bold leading-none text-forest">{{ $plan['price'] }}</span>
                                            <span class="font-mono text-xs text-ink-soft">{{ __('admin.billing_omr_month') }}</span>
                                        @else
                                            <span class="font-display text-[28px] font-bold leading-none text-forest">{{ __('admin.billing_free') }}</span>
                                        @endif
                                    </div>
                                </div>

                                <ul class="space-y-2">
                                    @foreach($plan['features'] as $feature)
                                        <li class="flex items-center gap-2 text-sm">
                                            <svg class="h-4 w-4 shrink-0" style="color: var(--bite-green);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span class="text-ink">{{ $feature }}</span>
                                        </li>
                                    @endforeach
                                </ul>

                                <div class="space-y-2">
                                    @if($plan['staff_limit'])
                                        <p class="font-mono text-[10px] text-ink-soft">{{ __('admin.billing_staff_limit', ['count' => $plan['staff_limit']]) }}</p>
                                    @else
                                        <p class="font-mono text-[10px] text-ink-soft">{{ __('admin.billing_staff_unlimited') }}</p>
                                    @endif

                                    @if($plan['product_limit'])
                                        <p class="font-mono text-[10px] text-ink-soft">{{ __('admin.billing_products_limit', ['count' => $plan['product_limit']]) }}</p>
                                    @else
                                        <p class="font-mono text-[10px] text-ink-soft">{{ __('admin.billing_products_unlimited') }}</p>
                                    @endif
                                </div>

                                <div class="mt-auto pt-2">
                                    @if($isCurrentPlan)
                                        <button disabled class="btn-secondary w-full cursor-not-allowed opacity-50">
                                            {{ __('admin.billing_current_plan_btn') }}
                                        </button>
                                    @elseif($isPro)
                                        <button wire:click="subscribe('pro')" class="btn-primary w-full" style="background: var(--bite-forest); border-color: var(--bite-forest);">
                                            <span wire:loading.remove wire:target="subscribe('pro')">{{ __('admin.billing_upgrade_pro') }}</span>
                                            <span wire:loading wire:target="subscribe('pro')" class="inline-flex items-center gap-2">
                                                <span class="loading-spinner"></span>
                                                {{ __('admin.billing_redirecting') }}
                                            </span>
                                        </button>
                                    @else
                                        <button wire:click="subscribe('free')" class="btn-secondary w-full">
                                            <span wire:loading.remove wire:target="subscribe('free')">{{ __('admin.billing_switch_free') }}</span>
                                            <span wire:loading wire:target="subscribe('free')" class="inline-flex items-center gap-2">
                                                <span class="loading-spinner"></span>
                                                {{ __('admin.billing_processing') }}
                                            </span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- Invoices --}}
            @if(count($invoices) > 0)
                <section class="surface-card">
                    <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
                        <div class="flex items-center gap-2.5">
                            <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                            <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.billing_invoices') }}</h2>
                        </div>
                        <span class="tag">{{ __('admin.billing_history') }}</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-start">
                            <thead>
                                <tr class="border-b border-line bg-cream font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">
                                    <th class="px-[22px] py-3 text-start">{{ __('admin.billing_date') }}</th>
                                    <th class="px-[22px] py-3 text-start">{{ __('admin.billing_amount') }}</th>
                                    <th class="px-[22px] py-3 text-start">{{ __('admin.billing_status') }}</th>
                                    <th class="px-[22px] py-3 text-end">{{ __('admin.billing_invoice') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach($invoices as $invoice)
                                    <tr class="group transition-colors hover:bg-cream">
                                        <td class="px-[22px] py-3 font-mono text-[13px] text-ink">{{ $invoice->date()->format('M j, Y') }}</td>
                                        <td class="px-[22px] py-3 font-display text-sm font-bold text-forest">{{ $invoice->total() }}</td>
                                        <td class="px-[22px] py-3">
                                            @if($invoice->paid)
                                                <span class="inline-flex items-center gap-1 font-mono text-[10px] font-semibold uppercase tracking-[0.14em]" style="color: rgb(var(--signal));">
                                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                    {{ __('admin.billing_paid') }}
                                                </span>
                                            @else
                                                <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em]" style="color: rgb(var(--alert));">
                                                    {{ __('admin.billing_unpaid') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-[22px] py-3 text-end">
                                            <a href="{{ $invoice->invoicePdf() }}" target="_blank" rel="noopener" class="btn-secondary !px-3 !py-1.5 !text-[9px]">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                                PDF
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            {{-- Cancel / Resume Subscription --}}
            @if($subscription)
                <section class="surface-card">
                    <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
                        <div class="flex items-center gap-2.5">
                            <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                            <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.billing_manage') }}</h2>
                        </div>
                        <span class="tag">{{ __('admin.billing_cancel_resume') }}</span>
                    </div>

                    <div class="p-[22px]">
                        @if($subscription->canceled() && $subscription->onGracePeriod())
                            <div class="space-y-4">
                                <p class="text-sm text-ink-soft">
                                    {{ __('admin.billing_cancelled_grace', ['date' => $subscription->ends_at->format('M j, Y')]) }}
                                </p>
                                <button wire:click="resumeSubscription" class="btn-primary" style="background: var(--bite-forest); border-color: var(--bite-forest);">
                                    <span wire:loading.remove wire:target="resumeSubscription">{{ __('admin.billing_resume') }}</span>
                                    <span wire:loading wire:target="resumeSubscription" class="inline-flex items-center gap-2">
                                        <span class="loading-spinner"></span>
                                        {{ __('admin.billing_resuming') }}
                                    </span>
                                </button>
                            </div>
                        @elseif(! $subscription->canceled())
                            <div class="space-y-4">
                                <p class="text-sm text-ink-soft">
                                    {{ __('admin.billing_cancel_info') }}
                                </p>

                                @if($showCancelModal)
                                    <div class="space-y-4 rounded-2xl border p-4" style="border-color: rgb(var(--alert) / 0.3); background-color: rgb(var(--alert) / 0.04);">
                                        <p class="text-sm font-bold" style="color: rgb(var(--alert));">{{ __('admin.billing_cancel_confirm') }}</p>
                                        <p class="text-xs text-ink-soft">
                                            {{ __('admin.billing_cancel_details') }}
                                        </p>
                                        <div class="flex items-center gap-3">
                                            <button wire:click="cancelSubscription" class="btn-danger">
                                                <span wire:loading.remove wire:target="cancelSubscription">{{ __('admin.billing_yes_cancel') }}</span>
                                                <span wire:loading wire:target="cancelSubscription" class="inline-flex items-center gap-2">
                                                    <span class="loading-spinner"></span>
                                                    {{ __('admin.billing_cancelling') }}
                                                </span>
                                            </button>
                                            <button wire:click="$set('showCancelModal', false)" class="btn-secondary">
                                                {{ __('admin.billing_keep_plan') }}
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    <button wire:click="$set('showCancelModal', true)" class="btn-secondary" style="color: rgb(var(--alert)); border-color: rgb(var(--alert) / 0.4);">
                                        {{ __('admin.billing_cancel_subscription') }}
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>
                </section>
            @endif
        </div>

        {{-- Right column: Payment method and quick info --}}
        <div class="xl:col-span-1">
            <div class="sticky top-24 space-y-[18px]">

                {{-- Payment Method --}}
                <section class="surface-card">
                    <div class="flex items-center justify-between border-b border-line px-[22px] py-4">
                        <div class="flex items-center gap-2.5">
                            <span class="h-[18px] w-1 rounded-sm" style="background: var(--bite-lime);"></span>
                            <h2 class="font-display text-[18px] font-bold leading-none text-forest">{{ __('admin.billing_payment_method') }}</h2>
                        </div>
                        <span class="tag">{{ __('admin.billing_card_on_file') }}</span>
                    </div>

                    <div class="space-y-4 p-[22px]">
                        @if($pmLastFour)
                            <div class="flex items-center gap-3">
                                {{-- Card brand icon --}}
                                <div class="flex h-10 w-14 items-center justify-center rounded-lg border border-line bg-cream">
                                    @if(strtolower($pmBrand ?? '') === 'visa')
                                        <span class="font-mono text-xs font-black uppercase tracking-wider" style="color: #1A1F71;">VISA</span>
                                    @elseif(strtolower($pmBrand ?? '') === 'mastercard')
                                        <span class="font-mono text-xs font-black uppercase tracking-wider" style="color: #EB001B;">MC</span>
                                    @else
                                        <svg class="h-5 w-5 text-ink-soft" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                                    @endif
                                </div>
                                <div>
                                    <p class="font-mono text-sm font-bold text-ink">
                                        {{ __('admin.billing_ending_in', ['brand' => ucfirst($pmBrand ?? 'Card'), 'last4' => $pmLastFour]) }}
                                    </p>
                                </div>
                            </div>
                        @else
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-14 items-center justify-center rounded-lg border border-dashed" style="border-color: rgb(var(--line));">
                                    <svg class="h-5 w-5" style="color: rgb(var(--ink-soft) / 0.5);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                                </div>
                                <p class="text-sm text-ink-soft">{{ __('admin.billing_no_payment_method') }}</p>
                            </div>
                        @endif

                        @if($shop->hasStripeId())
                            <button wire:click="redirectToPortal" class="btn-secondary w-full">
                                <span wire:loading.remove wire:target="redirectToPortal">
                                    {{ $pmLastFour ? __('admin.billing_update_payment') : __('admin.billing_add_payment') }}
                                </span>
                                <span wire:loading wire:target="redirectToPortal" class="inline-flex items-center gap-2">
                                    <span class="loading-spinner"></span>
                                    {{ __('admin.billing_redirecting') }}
                                </span>
                            </button>
                        @endif
                    </div>
                </section>

                {{-- Quick Info --}}
                <section class="surface-card p-[22px]">
                    <p class="section-headline">{{ __('admin.billing_summary') }}</p>
                    <div class="mt-3 space-y-2.5">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-ink-soft">{{ __('admin.billing_plan') }}</span>
                            <span class="font-mono font-bold text-ink">{{ $plans[$currentPlan]['name'] ?? 'Free' }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-ink-soft">{{ __('admin.billing_status') }}</span>
                            <span class="font-mono font-bold text-ink">{{ ucfirst(str_replace('_', ' ', $statusLabel)) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-ink-soft">{{ __('admin.billing_monthly_cost') }}</span>
                            <span class="font-mono font-bold text-ink">
                                @if(($plans[$currentPlan]['price'] ?? 0) > 0)
                                    {{ $plans[$currentPlan]['price'] }} {{ __('admin.billing_omr_month') }}
                                @else
                                    {{ __('admin.billing_free') }}
                                @endif
                            </span>
                        </div>
                        @if($isOnTrial)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-ink-soft">{{ __('admin.billing_trial_remaining_label') }}</span>
                                <span class="font-mono font-bold" style="color: rgb(var(--crema));">{{ __('admin.billing_days_remaining', ['count' => $trialDaysRemaining]) }}</span>
                            </div>
                        @endif
                    </div>
                </section>

                {{-- Help --}}
                <section class="surface-card p-[22px]">
                    <p class="section-headline">{{ __('admin.billing_need_help') }}</p>
                    <p class="mt-2 text-sm leading-relaxed text-ink-soft">
                        {{ __('admin.billing_help_text') }}
                        <a href="mailto:support@bitpos.app" class="font-mono font-bold text-ink underline decoration-crema underline-offset-2">support@bitpos.app</a>
                    </p>
                </section>
            </div>
        </div>
    </div>
</div>
