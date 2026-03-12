<div class="h-full space-y-6 fade-rise" wire:poll.5s>
    <x-slot:header>POS Register</x-slot:header>

    <div class="grid h-full gap-6 lg:grid-cols-4">
        <section class="space-y-5 lg:col-span-3">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="section-headline">Active Tickets</p>
                    <h2 class="mt-1 text-3xl font-extrabold leading-none text-ink">Front Counter Queue</h2>
                </div>
                <div class="flex items-center gap-2">
                    <span wire:loading class="loading-spinner text-ink-soft" style="width: 14px; height: 14px; border-width: 1.5px;"></span>
                    <span class="tag">Refresh 5s</span>
                    <span class="tag">{{ count($orders) }} open</span>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3 transition-opacity duration-300" wire:loading.class="opacity-60">
                @forelse($orders as $order)
                    @php
                        $statusTone = match ($order->status) {
                            'ready' => 'border-crema/35 bg-crema/10 text-crema',
                            'unpaid' => 'border-alert/35 bg-alert/10 text-alert',
                            default => 'border-line bg-muted text-ink-soft',
                        };
                    @endphp
                    <article class="surface-card flex flex-col">
                        <span class="sr-only">ID_{{ $order->id }}</span>
                        <header class="flex items-start justify-between border-b border-line px-5 py-4">
                            <div>
                                <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Order #{{ $order->id }}</p>
                                <p class="mt-1 font-display text-2xl font-extrabold leading-none text-ink">{{ formatPrice($order->total_amount, $shop) }}</p>
                            </div>
                            <div class="flex items-start gap-2">
                                <button
                                    onclick="window.open('/receipt/{{ $order->id }}', '_blank', 'width=380,height=700')"
                                    class="rounded-md border border-line bg-panel p-2 text-ink-soft hover:border-ink hover:text-ink transition-colors print-hidden"
                                    title="Print Receipt"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                </button>
                            <div class="text-right">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-1 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] {{ $statusTone }}">
                                    {{ $order->status }}
                                </span>
                                <p class="mt-2 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ $order->created_at->format('H:i') }}</p>
                                @php
                                    $minutesElapsed = now()->diffInMinutes($order->created_at);
                                    $urgencyClass = match(true) {
                                        $minutesElapsed >= 10 => 'text-alert',
                                        $minutesElapsed >= 5  => 'text-crema',
                                        default               => 'text-ink-soft',
                                    };
                                @endphp
                                <p class="mt-0.5 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] {{ $urgencyClass }}">{{ $order->created_at->diffForHumans() }}</p>
                            </div>
                            </div>
                        </header>

                        <div class="flex-1 space-y-4 p-5">
                            <div>
                                <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Channel</p>
                                <p class="mt-1 text-sm font-medium text-ink">Guest Counter Order</p>
                            </div>

                            @if($order->items->isNotEmpty())
                                <div>
                                    <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Items</p>
                                    <div class="mt-1 space-y-0.5">
                                        @foreach($order->items->take(3) as $item)
                                            <p class="font-mono text-xs text-ink">{{ $item->quantity }}x {{ $item->product_name_snapshot_en }}</p>
                                        @endforeach
                                        @if($order->items->count() > 3)
                                            <p class="font-mono text-[10px] text-ink-soft">+{{ $order->items->count() - 3 }} more</p>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            @if($order->payments->isNotEmpty())
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="rounded-lg border border-line bg-panel px-3 py-2">
                                        <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.14em] text-ink-soft">Paid</p>
                                        <p class="mt-1 font-mono text-xs font-bold uppercase">{{ formatPrice($order->paid_total, $shop) }}</p>
                                    </div>
                                    <div class="rounded-lg border border-line bg-panel px-3 py-2">
                                        <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.14em] text-ink-soft">Due</p>
                                        <p class="mt-1 font-mono text-xs font-bold uppercase">{{ formatPrice($order->balance_due, $shop) }}</p>
                                    </div>
                                </div>
                            @endif

                            <div class="space-y-2">
                                @if($order->status === 'unpaid')
                                    <div class="grid grid-cols-2 gap-2">
                                        <button wire:click="markAsPaid({{ $order->id }}, 'cash')"
                                                wire:loading.attr="disabled"
                                                wire:loading.class="opacity-50 cursor-wait"
                                                wire:target="markAsPaid({{ $order->id }}, 'cash')"
                                                class="btn-primary justify-center">
                                            <span wire:loading.remove wire:target="markAsPaid({{ $order->id }}, 'cash')">Cash</span>
                                            <span wire:loading wire:target="markAsPaid({{ $order->id }}, 'cash')" class="loading-spinner"></span>
                                        </button>
                                        <button wire:click="markAsPaid({{ $order->id }}, 'card')"
                                                wire:loading.attr="disabled"
                                                wire:loading.class="opacity-50 cursor-wait"
                                                wire:target="markAsPaid({{ $order->id }}, 'card')"
                                                class="btn-primary justify-center">
                                            <span wire:loading.remove wire:target="markAsPaid({{ $order->id }}, 'card')">Card</span>
                                            <span wire:loading wire:target="markAsPaid({{ $order->id }}, 'card')" class="loading-spinner"></span>
                                        </button>
                                    </div>
                                    <button wire:click="openSplit({{ $order->id }})" class="btn-secondary w-full justify-center">
                                        Split Items
                                    </button>
                                    <button wire:click="openPayment({{ $order->id }})" class="w-full text-center font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft hover:text-ink transition-colors">
                                        Split Payment&hellip;
                                    </button>
                                @elseif($order->status === 'ready')
                                    <button wire:click="markAsDelivered({{ $order->id }})"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="opacity-50 cursor-wait"
                                            wire:target="markAsDelivered({{ $order->id }})"
                                            class="btn-primary w-full justify-center !bg-signal !border-signal">
                                        <span wire:loading.remove wire:target="markAsDelivered({{ $order->id }})">Mark Delivered</span>
                                        <span wire:loading wire:target="markAsDelivered({{ $order->id }})" class="loading-spinner"></span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="col-span-full">
                        <div class="surface-card border-dashed p-16 text-center">
                            <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.2em] text-ink-soft">No Active Orders</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </section>

        <aside class="space-y-5 lg:col-span-1 transition-opacity duration-300" wire:loading.class="opacity-60">
            <section class="surface-card overflow-hidden border-panel/20 bg-ink text-panel">
                <div class="border-b border-panel/10 px-5 py-4">
                    <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.18em] text-panel/60">Today</p>
                    <h3 class="mt-2 font-display text-3xl font-extrabold leading-none">{{ formatPrice($salesToday, $shop) }}</h3>
                </div>

                <div class="grid grid-cols-2 gap-3 p-5">
                    <div class="rounded-lg border border-panel/15 bg-panel/10 px-3 py-3">
                        <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-panel/55">Orders</p>
                        <p class="mt-1 font-display text-2xl font-bold">{{ $ordersToday }}</p>
                    </div>
                    <div class="rounded-lg border border-signal/40 bg-signal/15 px-3 py-3">
                        <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-panel/65">Status</p>
                        <p class="mt-1 font-mono text-xs font-bold uppercase tracking-[0.16em] text-panel">Online</p>
                    </div>
                    <div class="rounded-lg border border-panel/15 bg-panel/10 px-3 py-3">
                        <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-panel/55">Unpaid</p>
                        <p class="mt-1 font-display text-2xl font-bold">{{ $unpaidCount }}</p>
                    </div>
                    <div class="rounded-lg border border-panel/15 bg-panel/10 px-3 py-3">
                        <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-panel/55">Ready</p>
                        <p class="mt-1 font-display text-2xl font-bold">{{ $readyCount }}</p>
                    </div>
                </div>
            </section>

            <section class="surface-card space-y-3 p-5">
                <p class="section-headline">System Actions</p>
                <button
                    x-on:click="$dispatch('confirm-action', {
                        title: 'Clear Old Orders',
                        message: 'Clear expired unpaid orders and ready orders older than 30 minutes?',
                        action: 'clearOldOrders',
                        componentId: $wire.id,
                        destructive: false,
                    })"
                    class="btn-secondary w-full justify-center"
                >
                    Clear Old Orders
                </button>
                <button
                    x-on:click="$dispatch('confirm-action', {
                        title: 'System Reset',
                        message: 'Cancel all unpaid orders and complete all ready orders? This action cannot be undone.',
                        action: 'systemReset',
                        componentId: $wire.id,
                        destructive: true,
                    })"
                    class="btn-danger w-full justify-center"
                >
                    System Reset
                </button>
            </section>
        </aside>
    </div>

    @if($splitOrder)
        <div class="fixed inset-0 z-[120] flex items-end justify-center bg-ink/75 p-0 backdrop-blur-sm sm:items-center sm:p-6">
            <div class="surface-card flex w-full max-w-2xl flex-col overflow-hidden sm:rounded-xl">
                <div class="flex items-center justify-between border-b border-line bg-muted/30 px-5 py-4">
                    <div>
                        <h3 class="font-display text-2xl font-extrabold leading-none text-ink">Split Order #{{ $splitOrder->id }}</h3>
                        <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Move selected quantities into a new ticket</p>
                    </div>
                    <button wire:click="closeSplit" class="rounded-md border border-line bg-panel p-2 text-ink-soft hover:border-ink hover:text-ink">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                @if($splitError)
                    <div class="px-5 pt-5">
                        <div class="rounded-lg border border-alert/35 bg-alert/10 px-3 py-2 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-alert">
                            {{ $splitError }}
                        </div>
                    </div>
                @endif

                <div class="max-h-[60vh] space-y-3 overflow-y-auto p-5">
                    @foreach($splitOrder->items as $item)
                        <div class="rounded-xl border border-line bg-panel px-4 py-3">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm font-semibold uppercase tracking-tight text-ink">{{ $item->product_name_snapshot_en }}</p>
                                    <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">Qty: {{ $item->quantity }} | {{ formatPrice($item->price_snapshot, $shop) }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Split Qty</label>
                                    <input type="number" min="0" max="{{ $item->quantity }}" wire:model.live="splitQuantities.{{ $item->id }}" class="field w-24 text-center font-mono text-xs font-bold uppercase">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex gap-3 border-t border-line bg-muted/20 p-5">
                    <button wire:click="closeSplit" class="btn-secondary flex-1 justify-center">Cancel</button>
                    <button wire:click="applySplit" class="btn-primary flex-1 justify-center">Create Split</button>
                </div>
            </div>
        </div>
    @endif

    @if($paymentOrder)
        <div class="fixed inset-0 z-[120] flex items-end justify-center bg-ink/75 p-0 backdrop-blur-sm sm:items-center sm:p-6">
            <div class="surface-card flex w-full max-w-2xl flex-col overflow-hidden sm:rounded-xl">
                <div class="flex items-center justify-between border-b border-line bg-muted/30 px-5 py-4">
                    <div>
                        <h3 class="font-display text-2xl font-extrabold leading-none text-ink">Payments for Order #{{ $paymentOrder->id }}</h3>
                        <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Balance due: {{ formatPrice($paymentOrder->balance_due, $shop) }}</p>
                    </div>
                    <button wire:click="closePayment" class="rounded-md border border-line bg-panel p-2 text-ink-soft hover:border-ink hover:text-ink">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                @if($paymentError)
                    <div class="px-5 pt-5">
                        <div class="rounded-lg border border-alert/35 bg-alert/10 px-3 py-2 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-alert">
                            {{ $paymentError }}
                        </div>
                    </div>
                @endif

                <div class="space-y-5 p-5">
                    <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3">
                        <div class="rounded-lg border border-line bg-panel p-3">
                            <label class="font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Guests</label>
                            <div class="mt-2 flex items-center gap-2">
                                <input type="number" min="1" wire:model="splitGuestCount" class="field w-20 text-center font-mono text-xs font-bold uppercase">
                                <button wire:click="splitByGuests" class="btn-secondary !px-3 !py-2">Split</button>
                            </div>
                        </div>
                        <div class="rounded-lg border border-line bg-panel p-3">
                            <label class="font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Amount</label>
                            <div class="mt-2 flex items-center gap-2">
                                <input type="number" min="0" step="0.01" wire:model="splitAmount" class="field w-24 text-center font-mono text-xs font-bold uppercase">
                                <button wire:click="splitByAmount" class="btn-secondary !px-3 !py-2">Split</button>
                            </div>
                        </div>
                        <div class="rounded-lg border border-line bg-panel p-3 sm:col-span-2 md:col-span-1">
                            <label class="font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Rows</label>
                            <div class="mt-2">
                                <button wire:click="addPaymentRow" class="btn-secondary w-full justify-center !px-3 !py-2">Add Row</button>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        @foreach($paymentRows as $index => $row)
                            <div class="grid items-center gap-2 rounded-lg border border-line bg-panel p-3 sm:grid-cols-[auto_auto_1fr]">
                                <input type="number" min="0" step="0.01" wire:model.live="paymentRows.{{ $index }}.amount" class="field w-full text-center font-mono text-xs font-bold uppercase sm:w-28">
                                <select wire:model.live="paymentRows.{{ $index }}.method" class="field w-full font-mono text-xs font-semibold uppercase sm:w-36">
                                    <option value="cash">Cash</option>
                                    <option value="card">Card</option>
                                    <option value="voucher">Voucher</option>
                                </select>
                                <button wire:click="removePaymentRow({{ $index }})" class="btn-secondary w-full justify-center !border-alert/40 !bg-alert/10 !text-alert sm:w-auto">
                                    Remove
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex gap-3 border-t border-line bg-muted/20 p-5">
                    <button wire:click="closePayment" class="btn-secondary flex-1 justify-center">Cancel</button>
                    <button wire:click="applyPayments" class="btn-primary flex-1 justify-center">Apply Payments</button>
                </div>
            </div>
        </div>
    @endif

    @if($showManagerModal)
        <div class="fixed inset-0 z-[130] flex items-end justify-center bg-ink/75 p-0 backdrop-blur-sm sm:items-center sm:p-6">
            <div class="surface-card w-full max-w-md overflow-hidden sm:rounded-xl">
                <div class="border-b border-line bg-muted/30 px-5 py-4">
                    <h3 class="font-display text-2xl font-extrabold leading-none text-ink">Manager Override</h3>
                    <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Enter manager PIN to proceed</p>
                </div>

                <div class="space-y-3 p-5">
                    <input type="password" maxlength="4" wire:model="managerPin" class="field w-full text-center font-mono text-sm font-bold uppercase tracking-[0.45em]" placeholder="PIN">
                    @if($managerError)
                        <div class="rounded-lg border border-alert/35 bg-alert/10 px-3 py-2 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-alert">
                            {{ $managerError }}
                        </div>
                    @endif
                </div>

                <div class="flex gap-3 border-t border-line bg-muted/20 p-5">
                    <button wire:click="cancelManagerOverride" class="btn-secondary flex-1 justify-center">Cancel</button>
                    <button wire:click="confirmManagerOverride" class="btn-primary flex-1 justify-center">Confirm</button>
                </div>
            </div>
        </div>
    @endif
</div>
