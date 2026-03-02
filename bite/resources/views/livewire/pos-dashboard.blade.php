<div class="h-full space-y-6 fade-rise" wire:poll.5s>
    <x-slot:header>POS Register</x-slot:header>

    @if (session()->has('message'))
        <div class="rounded-xl border border-signal/35 bg-signal/10 px-4 py-3 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-signal">
            {{ session('message') }}
        </div>
    @endif

    <div class="grid h-full gap-6 xl:grid-cols-4">
        <section class="space-y-5 xl:col-span-3">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="section-headline">Active Tickets</p>
                    <h2 class="mt-1 text-3xl font-extrabold leading-none text-ink">Front Counter Queue</h2>
                </div>
                <div class="flex items-center gap-2">
                    <span class="tag">Refresh 5s</span>
                    <span class="tag">{{ count($orders) }} open</span>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
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
                                <p class="mt-1 font-display text-2xl font-extrabold leading-none text-ink">${{ number_format($order->total_amount, 2) }}</p>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-1 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] {{ $statusTone }}">
                                    {{ $order->status }}
                                </span>
                                <p class="mt-2 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ $order->created_at->format('H:i') }}</p>
                            </div>
                        </header>

                        <div class="flex-1 space-y-4 p-5">
                            <div>
                                <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Channel</p>
                                <p class="mt-1 text-sm font-medium text-ink">Guest Counter Order</p>
                            </div>

                            @if($order->payments->isNotEmpty())
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="rounded-lg border border-line bg-panel px-3 py-2">
                                        <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.14em] text-ink-soft">Paid</p>
                                        <p class="mt-1 font-mono text-xs font-bold uppercase">${{ number_format($order->paid_total, 2) }}</p>
                                    </div>
                                    <div class="rounded-lg border border-line bg-panel px-3 py-2">
                                        <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.14em] text-ink-soft">Due</p>
                                        <p class="mt-1 font-mono text-xs font-bold uppercase">${{ number_format($order->balance_due, 2) }}</p>
                                    </div>
                                </div>
                            @endif

                            <div class="space-y-2">
                                @if($order->status === 'unpaid')
                                    <button wire:click="openPayment({{ $order->id }})" class="btn-primary w-full justify-center">
                                        Take Payment
                                    </button>
                                    <button wire:click="openSplit({{ $order->id }})" class="btn-secondary w-full justify-center">
                                        Split Items
                                    </button>
                                @elseif($order->status === 'ready')
                                    <button wire:click="markAsDelivered({{ $order->id }})" class="btn-primary w-full justify-center !bg-signal !border-signal">
                                        Mark Delivered
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

        <aside class="space-y-5 xl:col-span-1">
            <section class="surface-card overflow-hidden border-panel/20 bg-ink text-panel">
                <div class="border-b border-panel/10 px-5 py-4">
                    <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.18em] text-panel/60">Today</p>
                    <h3 class="mt-2 font-display text-3xl font-extrabold leading-none">${{ number_format($salesToday, 2) }}</h3>
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
                <button wire:click="clearOldOrders" onclick="return confirm('Clear expired unpaid orders and ready orders older than 30 minutes?')" class="btn-secondary w-full justify-center">
                    Clear Old Orders
                </button>
                <button wire:click="systemReset" onclick="return confirm('Cancel all unpaid orders and complete all ready orders?')" class="btn-danger w-full justify-center">
                    System Reset
                </button>
            </section>
        </aside>
    </div>

    @if($splitOrder)
        <div class="fixed inset-0 z-[120] flex items-center justify-center bg-ink/75 p-4 backdrop-blur-sm sm:p-6">
            <div class="surface-card w-full max-w-2xl overflow-hidden">
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
                                    <p class="text-sm font-semibold uppercase tracking-tight text-ink">{{ $item->product_name_snapshot }}</p>
                                    <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">Qty: {{ $item->quantity }} | ${{ number_format($item->price_snapshot, 2) }}</p>
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
        <div class="fixed inset-0 z-[120] flex items-center justify-center bg-ink/75 p-4 backdrop-blur-sm sm:p-6">
            <div class="surface-card w-full max-w-2xl overflow-hidden">
                <div class="flex items-center justify-between border-b border-line bg-muted/30 px-5 py-4">
                    <div>
                        <h3 class="font-display text-2xl font-extrabold leading-none text-ink">Payments for Order #{{ $paymentOrder->id }}</h3>
                        <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Balance due: ${{ number_format($paymentOrder->balance_due, 2) }}</p>
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
                    <div class="grid gap-3 md:grid-cols-3">
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
                        <div class="rounded-lg border border-line bg-panel p-3">
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
        <div class="fixed inset-0 z-[130] flex items-center justify-center bg-ink/75 p-4 backdrop-blur-sm sm:p-6">
            <div class="surface-card w-full max-w-md overflow-hidden">
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
