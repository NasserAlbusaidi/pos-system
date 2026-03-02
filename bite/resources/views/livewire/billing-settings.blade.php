<div class="space-y-6 fade-rise">
    <x-slot:header>Billing & Subscription</x-slot:header>

    {{-- Checkout callback notices --}}
    @if($checkoutStatus === 'success')
        <div class="rounded-xl border p-4 flex items-center gap-3" style="border-color: rgb(var(--signal) / 0.5); background-color: rgb(var(--signal) / 0.08);">
            <svg class="w-5 h-5 shrink-0" style="color: rgb(var(--signal));" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            <p class="text-sm font-medium" style="color: rgb(var(--signal));">Subscription activated successfully. Welcome to Pro!</p>
        </div>
    @elseif($checkoutStatus === 'cancelled')
        <div class="rounded-xl border p-4 flex items-center gap-3" style="border-color: rgb(var(--line)); background-color: rgb(var(--panel-muted) / 0.3);">
            <svg class="w-5 h-5 shrink-0" style="color: rgb(var(--ink-soft));" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            <p class="text-sm" style="color: rgb(var(--ink-soft));">Checkout was cancelled. You can try again when you are ready.</p>
        </div>
    @endif

    {{-- Billing notice from middleware --}}
    @if(session('billing_notice'))
        <div class="rounded-xl border p-4 flex items-center gap-3" style="border-color: rgb(var(--alert) / 0.5); background-color: rgb(var(--alert) / 0.08);">
            <svg class="w-5 h-5 shrink-0" style="color: rgb(var(--alert));" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
            <p class="text-sm font-medium" style="color: rgb(var(--alert));">{{ session('billing_notice') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Left column: Main billing content --}}
        <div class="xl:col-span-2 space-y-6">

            {{-- Current Plan --}}
            <section class="surface-card">
                <div class="border-b border-line bg-muted/30 px-5 py-4">
                    <h2 class="font-display text-2xl font-extrabold leading-none text-ink">Current Plan</h2>
                    <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Subscription status and details</p>
                </div>

                <div class="p-5 space-y-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="font-display text-xl font-extrabold text-ink">
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

                    @if($isOnTrial)
                        <div class="rounded-xl border p-4 flex items-start gap-3" style="border-color: rgb(var(--crema) / 0.3); background-color: rgb(var(--crema) / 0.06);">
                            <svg class="w-5 h-5 shrink-0 mt-0.5" style="color: rgb(var(--crema));" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <div>
                                <p class="text-sm font-bold" style="color: rgb(var(--crema));">{{ $trialDaysRemaining }} {{ $trialDaysRemaining === 1 ? 'day' : 'days' }} remaining in your free trial</p>
                                <p class="text-xs mt-1" style="color: rgb(var(--ink-soft));">Upgrade to Pro to keep all features after your trial ends.</p>
                            </div>
                        </div>
                    @endif

                    @if($renewalDate)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-ink-soft">
                                {{ $statusLabel === 'cancelled' ? 'Access until' : ($isOnTrial ? 'Trial ends' : 'Next renewal') }}
                            </span>
                            <span class="font-mono font-bold text-ink">{{ $renewalDate->format('M j, Y') }}</span>
                        </div>
                    @endif

                    @if($currentPlan === 'pro' && $statusLabel === 'active')
                        <p class="text-sm text-ink-soft">
                            Your Pro subscription is active. You have access to all features including unlimited staff, unlimited products, reports, and priority support.
                        </p>
                    @endif
                </div>
            </section>

            {{-- Plan Comparison --}}
            <section class="surface-card">
                <div class="border-b border-line bg-muted/30 px-5 py-4">
                    <h2 class="font-display text-2xl font-extrabold leading-none text-ink">Plans</h2>
                    <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Compare Free and Pro</p>
                </div>

                <div class="p-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($plans as $planKey => $plan)
                            @php
                                $isCurrentPlan = $currentPlan === $planKey;
                                $isPro = $planKey === 'pro';
                            @endphp
                            <div class="rounded-xl border p-5 space-y-4 relative transition-all duration-200 {{ $isCurrentPlan ? '' : 'hover:shadow-md' }}"
                                 style="border-color: {{ $isCurrentPlan ? 'rgb(var(--crema))' : 'rgb(var(--line))' }};
                                        background-color: {{ $isCurrentPlan ? 'rgb(var(--crema) / 0.04)' : 'rgb(var(--panel))' }};">

                                @if($isCurrentPlan)
                                    <span class="absolute top-3 right-3 inline-flex items-center rounded-full px-2 py-0.5 font-mono text-[8px] font-bold uppercase tracking-[0.16em]"
                                          style="background-color: rgb(var(--crema)); color: rgb(var(--panel));">
                                        Current
                                    </span>
                                @endif

                                <div>
                                    <h3 class="font-display text-lg font-extrabold text-ink">{{ $plan['name'] }}</h3>
                                    <div class="mt-1 flex items-baseline gap-1">
                                        @if($plan['price'] > 0)
                                            <span class="font-display text-3xl font-extrabold text-ink">{{ $plan['price'] }}</span>
                                            <span class="font-mono text-xs text-ink-soft">OMR / mo</span>
                                        @else
                                            <span class="font-display text-3xl font-extrabold text-ink">Free</span>
                                        @endif
                                    </div>
                                </div>

                                <ul class="space-y-2">
                                    @foreach($plan['features'] as $feature)
                                        <li class="flex items-center gap-2 text-sm">
                                            <svg class="w-4 h-4 shrink-0" style="color: rgb(var(--signal));" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span class="text-ink">{{ $feature }}</span>
                                        </li>
                                    @endforeach
                                </ul>

                                <div class="space-y-2">
                                    @if($plan['staff_limit'])
                                        <p class="font-mono text-[10px] text-ink-soft">Staff: {{ $plan['staff_limit'] }} member{{ $plan['staff_limit'] > 1 ? 's' : '' }}</p>
                                    @else
                                        <p class="font-mono text-[10px] text-ink-soft">Staff: Unlimited</p>
                                    @endif

                                    @if($plan['product_limit'])
                                        <p class="font-mono text-[10px] text-ink-soft">Products: up to {{ $plan['product_limit'] }}</p>
                                    @else
                                        <p class="font-mono text-[10px] text-ink-soft">Products: Unlimited</p>
                                    @endif
                                </div>

                                <div class="pt-2">
                                    @if($isCurrentPlan)
                                        <button disabled class="btn-secondary w-full opacity-50 cursor-not-allowed">
                                            Current Plan
                                        </button>
                                    @elseif($isPro)
                                        <button wire:click="subscribe('pro')" class="btn-primary w-full">
                                            <span wire:loading.remove wire:target="subscribe('pro')">Upgrade to Pro</span>
                                            <span wire:loading wire:target="subscribe('pro')" class="inline-flex items-center gap-2">
                                                <span class="loading-spinner"></span>
                                                Redirecting...
                                            </span>
                                        </button>
                                    @else
                                        <button wire:click="subscribe('free')" class="btn-secondary w-full">
                                            <span wire:loading.remove wire:target="subscribe('free')">Switch to Free</span>
                                            <span wire:loading wire:target="subscribe('free')" class="inline-flex items-center gap-2">
                                                <span class="loading-spinner"></span>
                                                Processing...
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
                    <div class="border-b border-line bg-muted/30 px-5 py-4">
                        <h2 class="font-display text-2xl font-extrabold leading-none text-ink">Invoices</h2>
                        <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Billing history</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-left">
                            <thead>
                                <tr class="border-b border-line bg-panel font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">
                                    <th class="px-5 py-3">Date</th>
                                    <th class="px-5 py-3">Amount</th>
                                    <th class="px-5 py-3">Status</th>
                                    <th class="px-5 py-3 text-right">Invoice</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach($invoices as $invoice)
                                    <tr class="group transition-colors hover:bg-muted/30">
                                        <td class="px-5 py-3 text-sm text-ink">{{ $invoice->date()->format('M j, Y') }}</td>
                                        <td class="px-5 py-3 text-sm font-mono font-bold text-ink">{{ $invoice->total() }}</td>
                                        <td class="px-5 py-3">
                                            @if($invoice->paid)
                                                <span class="inline-flex items-center gap-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em]" style="color: rgb(var(--signal));">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                    Paid
                                                </span>
                                            @else
                                                <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em]" style="color: rgb(var(--alert));">
                                                    Unpaid
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3 text-right">
                                            <a href="{{ $invoice->invoicePdf() }}" target="_blank" rel="noopener" class="btn-secondary !py-1.5 !px-3 !text-[9px]">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
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
                    <div class="border-b border-line bg-muted/30 px-5 py-4">
                        <h2 class="font-display text-2xl font-extrabold leading-none text-ink">Manage Subscription</h2>
                        <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Cancel or resume your plan</p>
                    </div>

                    <div class="p-5">
                        @if($subscription->cancelled() && $subscription->onGracePeriod())
                            <div class="space-y-4">
                                <p class="text-sm text-ink-soft">
                                    Your subscription has been cancelled but you still have access until <strong class="text-ink font-mono">{{ $subscription->ends_at->format('M j, Y') }}</strong>.
                                    You can resume your subscription to keep your Pro features.
                                </p>
                                <button wire:click="resumeSubscription" class="btn-primary">
                                    <span wire:loading.remove wire:target="resumeSubscription">Resume Subscription</span>
                                    <span wire:loading wire:target="resumeSubscription" class="inline-flex items-center gap-2">
                                        <span class="loading-spinner"></span>
                                        Resuming...
                                    </span>
                                </button>
                            </div>
                        @elseif(! $subscription->cancelled())
                            <div class="space-y-4">
                                <p class="text-sm text-ink-soft">
                                    If you cancel, your subscription will remain active until the end of your current billing period. You will not be charged again.
                                </p>

                                @if($showCancelModal)
                                    <div class="rounded-xl border p-4 space-y-4" style="border-color: rgb(var(--alert) / 0.3); background-color: rgb(var(--alert) / 0.04);">
                                        <p class="text-sm font-bold" style="color: rgb(var(--alert));">Are you sure you want to cancel?</p>
                                        <p class="text-xs text-ink-soft">
                                            You will lose access to Pro features (unlimited staff, products, reports) at the end of your billing period.
                                            Your data will not be deleted.
                                        </p>
                                        <div class="flex items-center gap-3">
                                            <button wire:click="cancelSubscription" class="btn-danger">
                                                <span wire:loading.remove wire:target="cancelSubscription">Yes, Cancel Subscription</span>
                                                <span wire:loading wire:target="cancelSubscription" class="inline-flex items-center gap-2">
                                                    <span class="loading-spinner"></span>
                                                    Cancelling...
                                                </span>
                                            </button>
                                            <button wire:click="$set('showCancelModal', false)" class="btn-secondary">
                                                Keep My Plan
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    <button wire:click="$set('showCancelModal', true)" class="btn-secondary" style="color: rgb(var(--alert)); border-color: rgb(var(--alert) / 0.4);">
                                        Cancel Subscription
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
            <div class="sticky top-24 space-y-6">

                {{-- Payment Method --}}
                <section class="surface-card">
                    <div class="border-b border-line bg-muted/30 px-5 py-4">
                        <h2 class="font-display text-lg font-extrabold leading-none text-ink">Payment Method</h2>
                        <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Card on file</p>
                    </div>

                    <div class="p-5 space-y-4">
                        @if($pmLastFour)
                            <div class="flex items-center gap-3">
                                {{-- Card brand icon --}}
                                <div class="flex h-10 w-14 items-center justify-center rounded-lg border border-line bg-panel">
                                    @if(strtolower($pmBrand ?? '') === 'visa')
                                        <span class="font-mono text-xs font-black uppercase tracking-wider" style="color: #1A1F71;">VISA</span>
                                    @elseif(strtolower($pmBrand ?? '') === 'mastercard')
                                        <span class="font-mono text-xs font-black uppercase tracking-wider" style="color: #EB001B;">MC</span>
                                    @else
                                        <svg class="w-5 h-5 text-ink-soft" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                                    @endif
                                </div>
                                <div>
                                    <p class="font-mono text-sm font-bold text-ink">
                                        {{ ucfirst($pmBrand ?? 'Card') }} ending in {{ $pmLastFour }}
                                    </p>
                                </div>
                            </div>
                        @else
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-14 items-center justify-center rounded-lg border border-dashed" style="border-color: rgb(var(--line));">
                                    <svg class="w-5 h-5" style="color: rgb(var(--ink-soft) / 0.5);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                                </div>
                                <p class="text-sm text-ink-soft">No payment method on file</p>
                            </div>
                        @endif

                        @if($shop->hasStripeId())
                            <button wire:click="redirectToPortal" class="btn-secondary w-full">
                                <span wire:loading.remove wire:target="redirectToPortal">
                                    {{ $pmLastFour ? 'Update Payment Method' : 'Add Payment Method' }}
                                </span>
                                <span wire:loading wire:target="redirectToPortal" class="inline-flex items-center gap-2">
                                    <span class="loading-spinner"></span>
                                    Redirecting...
                                </span>
                            </button>
                        @endif
                    </div>
                </section>

                {{-- Quick Info --}}
                <section class="surface-card p-5">
                    <p class="section-headline">Billing Summary</p>
                    <div class="mt-3 space-y-2.5">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-ink-soft">Plan</span>
                            <span class="font-mono font-bold text-ink">{{ $plans[$currentPlan]['name'] ?? 'Free' }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-ink-soft">Status</span>
                            <span class="font-mono font-bold text-ink">{{ ucfirst(str_replace('_', ' ', $statusLabel)) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-ink-soft">Monthly Cost</span>
                            <span class="font-mono font-bold text-ink">
                                @if(($plans[$currentPlan]['price'] ?? 0) > 0)
                                    {{ $plans[$currentPlan]['price'] }} OMR
                                @else
                                    Free
                                @endif
                            </span>
                        </div>
                        @if($isOnTrial)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-ink-soft">Trial Remaining</span>
                                <span class="font-mono font-bold" style="color: rgb(var(--crema));">{{ $trialDaysRemaining }} days</span>
                            </div>
                        @endif
                    </div>
                </section>

                {{-- Help --}}
                <section class="surface-card p-5">
                    <p class="section-headline">Need Help?</p>
                    <p class="mt-2 text-sm text-ink-soft leading-relaxed">
                        Questions about billing or need to change your plan? Contact us at
                        <a href="mailto:support@bitpos.app" class="font-mono font-bold text-ink underline underline-offset-2 decoration-crema">support@bitpos.app</a>
                    </p>
                </section>
            </div>
        </div>
    </div>
</div>
