<div class="h-full space-y-6 fade-rise" wire:poll.5s
     x-data="{
         audioCtx: null,
         playChime() {
             if (!this.audioCtx) this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
             const ctx = this.audioCtx;
             const osc = ctx.createOscillator();
             const gain = ctx.createGain();
             osc.connect(gain);
             gain.connect(ctx.destination);
             osc.frequency.setValueAtTime(660, ctx.currentTime);
             osc.frequency.setValueAtTime(880, ctx.currentTime + 0.1);
             osc.frequency.setValueAtTime(1100, ctx.currentTime + 0.2);
             gain.gain.setValueAtTime(0.3, ctx.currentTime);
             gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.5);
             osc.start(ctx.currentTime);
             osc.stop(ctx.currentTime + 0.5);
         }
     }"
     x-on:pos-new-order.window="playChime()"
>
    <!-- POS Scoped Micro-Animations & Glow Styles -->
    <style>
        @keyframes pulse-soft {
            0%, 100% { transform: scale(1); opacity: 1; box-shadow: 0 0 0 0 rgba(122, 199, 12, 0.4); }
            50% { transform: scale(1.05); opacity: 0.95; box-shadow: 0 0 12px 4px rgba(122, 199, 12, 0.2); }
        }
        .pos-status-pulse {
            animation: pulse-soft 2s infinite ease-in-out;
        }
        .hover-lift {
            transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.25s ease, border-color 0.25s ease;
        }
        .hover-lift:hover {
            transform: translateY(-2px);
        }
        .pos-card-glow-ready {
            box-shadow: 0 10px 30px -10px rgb(var(--crema) / 0.08), 0 1px 3px rgb(var(--crema) / 0.05);
        }
        .pos-card-glow-ready:hover {
            box-shadow: 0 16px 40px -12px rgb(var(--crema) / 0.16), 0 2px 8px rgb(var(--crema) / 0.10);
        }
        .pos-card-glow-unpaid {
            box-shadow: 0 10px 30px -10px rgb(var(--alert) / 0.06), 0 1px 3px rgb(var(--alert) / 0.04);
        }
        .pos-card-glow-unpaid:hover {
            box-shadow: 0 16px 40px -12px rgb(var(--alert) / 0.14), 0 2px 8px rgb(var(--alert) / 0.08);
        }
        .pos-card-glow-default {
            box-shadow: 0 10px 30px -10px rgb(var(--ink) / 0.04), 0 1px 3px rgb(var(--ink) / 0.03);
        }
        .pos-card-glow-default:hover {
            box-shadow: 0 16px 40px -12px rgb(var(--ink) / 0.08), 0 2px 8px rgb(var(--ink) / 0.05);
        }
    </style>

    <x-slot:header>{{ __('admin.pos_register') }}</x-slot:header>

    <div class="grid h-full gap-6 lg:grid-cols-4">
        <!-- Main Panel: Tickets Queue -->
        <section class="space-y-5 lg:col-span-3">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between pb-2">
                <div>
                    <p class="section-headline tracking-[0.2em] text-[10px] text-ink-soft">{{ __('admin.active_tickets') }}</p>
                    <h2 class="mt-1 text-3xl font-extrabold leading-none text-ink font-display tracking-tight">{{ __('admin.front_counter_queue') }}</h2>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div wire:loading class="flex items-center me-1">
                        <span class="loading-spinner text-ink-soft" style="width: 16px; height: 16px; border-width: 1.5px;"></span>
                    </div>
                    <span class="tag bg-panel border-line text-ink-soft font-mono text-[9px] font-bold px-3 py-1.5 rounded-full shadow-sm">{{ __('admin.refresh_interval', ['seconds' => '5s']) }}</span>
                    <span class="tag bg-panel border-line text-ink-soft font-mono text-[9px] font-bold px-3 py-1.5 rounded-full shadow-sm">{{ __('admin.open_count', ['count' => count($orders)]) }}</span>
                    <button wire:click="openNewOrder" class="btn-primary !px-5 !py-2.5 bg-gradient-to-r from-forest to-pine text-panel shadow-sm shadow-forest/10 hover:shadow-md hover:shadow-forest/15 hover:-translate-y-0.5 active:translate-y-0 active:scale-95 transition-all duration-200">+ {{ __('admin.new_sale') }}</button>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3 transition-opacity duration-300" wire:loading.class="opacity-60">
                @forelse($orders as $order)
                    @php
                        $statusTone = match ($order->status) {
                            'ready' => 'border-crema/30 bg-crema/10 text-pine',
                            'unpaid' => 'border-alert/30 bg-alert/10 text-alert',
                            default => 'border-line bg-muted text-ink-soft',
                        };
                        
                        $cardBorderClass = match ($order->status) {
                            'ready' => 'border-s-[5px] border-s-lime border-line hover:border-lime/30 pos-card-glow-ready',
                            'unpaid' => 'border-s-[5px] border-s-alert border-line hover:border-alert/35 pos-card-glow-unpaid',
                            default => 'border-s-[5px] border-s-forest/40 border-line hover:border-forest/40 pos-card-glow-default',
                        };

                        $minutesElapsed = now()->diffInMinutes($order->created_at);
                        $urgencyClass = match(true) {
                            $minutesElapsed >= 10 => 'text-alert font-bold bg-alert/5 border border-alert/20 px-2 py-0.5 rounded-md',
                            $minutesElapsed >= 5  => 'text-pine font-bold bg-crema/10 border border-crema/20 px-2 py-0.5 rounded-md',
                            default               => 'text-ink-soft',
                        };
                    @endphp
                    <article class="surface-card flex flex-col hover-lift transition-all duration-300 {{ $cardBorderClass }}">
                        <span class="sr-only">ID_{{ $order->id }}</span>
                        <header class="flex items-start justify-between border-b border-line bg-mist/10 px-5 py-4">
                            <div>
                                <p class="font-mono text-[9px] font-bold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.order_number', ['id' => $order->id]) }}</p>
                                <p class="mt-1 font-display text-2xl font-extrabold leading-none text-forest"><x-price :amount="$order->total_amount" :shop="$shop" /></p>
                            </div>
                            <div class="flex items-start gap-2">
                                <button
                                    onclick="window.open('/receipt/{{ $order->id }}', '_blank', 'width=380,height=700')"
                                    class="rounded-xl border border-line bg-panel p-2.5 text-ink-soft hover:border-ink hover:text-ink transition-colors shadow-sm active:scale-95"
                                    title="{{ __('admin.print_receipt') }}"
                                >
                                    <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                </button>
                                <div class="text-right">
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 font-mono text-[9px] font-bold uppercase tracking-[0.12em] {{ $statusTone }}">
                                        {{ __('admin.status_' . $order->status) }}
                                    </span>
                                    <p class="mt-2 font-mono text-[9px] font-bold uppercase tracking-[0.14em] text-ink-soft">{{ $order->created_at->format('H:i') }}</p>
                                    <p class="mt-0.5 font-mono text-[9px] font-bold uppercase tracking-[0.12em] {{ $urgencyClass }}">{{ $order->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        </header>

                        <div class="flex-1 space-y-4.5 p-5">
                            <div>
                                <p class="font-mono text-[9px] font-bold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.channel') }}</p>
                                <p class="mt-0.5 text-xs font-semibold text-ink">{{ __('admin.guest_counter_order') }}</p>
                            </div>

                            @if($order->items->isNotEmpty())
                                <div>
                                    <p class="font-mono text-[9px] font-bold uppercase tracking-[0.16em] text-ink-soft mb-1.5">{{ __('admin.items') }}</p>
                                    <div class="space-y-1.5">
                                        @foreach($order->items->take(3) as $item)
                                            <div class="flex items-start justify-between py-1 border-b border-line/30 last:border-0 font-mono text-xs">
                                                <div class="min-w-0">
                                                    <span class="text-ink font-medium truncate block">{{ $item->translated('product_name_snapshot') }}</span>
                                                    @if(filled($item->note))
                                                        <span class="pos-item-note text-[10px] italic text-alert/90 ps-3 block mt-0.5">↳ {{ $item->note }}</span>
                                                    @endif
                                                </div>
                                                <span class="text-pine bg-lime-100/50 px-1.5 py-0.5 rounded text-[10px] font-bold flex-shrink-0 ms-2">{{ $item->quantity }}x</span>
                                            </div>
                                        @endforeach
                                        @if($order->items->count() > 3)
                                            <p class="font-mono text-[10px] font-semibold text-ink-soft/80 mt-1.5 ps-1">+ {{ __('admin.more_items', ['count' => $order->items->count() - 3]) }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            @if(filled($order->order_note))
                                <div class="pos-order-note rounded-xl border border-crema/25 bg-crema/5 px-3.5 py-2.5 shadow-sm">
                                    <p class="font-mono text-[9px] font-bold uppercase tracking-[0.16em] text-pine">{{ __('admin.order_note') }}</p>
                                    <p class="mt-1 font-mono text-[11px] text-ink leading-normal">{{ $order->order_note }}</p>
                                </div>
                            @endif

                            {{-- Smart Upsell Suggestions --}}
                            @if($order->items->isNotEmpty())
                                @php
                                    $orderUpsells = collect();
                                    $orderProductIds = $order->items->pluck('product_id')->filter()->unique()->all();
                                    foreach ($orderProductIds as $pid) {
                                        if (isset($upsellSuggestions[$pid])) {
                                            foreach ($upsellSuggestions[$pid] as $suggestion) {
                                                if (! in_array($suggestion['id'], $orderProductIds, true) && ! $orderUpsells->contains('id', $suggestion['id'])) {
                                                    $orderUpsells->push($suggestion);
                                                }
                                            }
                                        }
                                    }
                                    $orderUpsells = $orderUpsells->take(3);
                                @endphp
                                @if($orderUpsells->isNotEmpty())
                                    <div class="rounded-xl border border-line bg-mist/20 px-3.5 py-2.5 shadow-sm">
                                        <p class="font-mono text-[9px] font-bold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.customers_also_order') }}</p>
                                        <p class="mt-1 font-mono text-[10px] leading-relaxed">
                                            @foreach($orderUpsells as $index => $upsell)
                                                <span class="text-forest font-semibold hover:underline cursor-help">{{ $upsell['name'] }}</span>@if(! $loop->last)<span class="text-ink-soft/40">, </span>@endif
                                            @endforeach
                                        </p>
                                    </div>
                                @endif
                            @endif

                            @if($order->payments->isNotEmpty())
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="rounded-xl border border-line bg-panel px-3.5 py-2.5 shadow-sm">
                                        <p class="font-mono text-[9px] font-bold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.paid') }}</p>
                                        <p class="mt-1 font-mono text-xs font-bold text-pine uppercase"><x-price :amount="$order->paid_total" :shop="$shop" /></p>
                                    </div>
                                    <div class="rounded-xl border border-line bg-panel px-3.5 py-2.5 shadow-sm">
                                        <p class="font-mono text-[9px] font-bold uppercase tracking-[0.14em] text-ink-soft">{{ __('admin.due') }}</p>
                                        <p class="mt-1 font-mono text-xs font-bold text-alert uppercase"><x-price :amount="$order->balance_due" :shop="$shop" /></p>
                                    </div>
                                </div>
                            @endif

                            <div class="space-y-2 pt-1 border-t border-line/45">
                                @if($order->status === 'unpaid')
                                    <div class="grid grid-cols-2 gap-2">
                                        <button wire:click="markAsPaid({{ $order->id }}, 'cash')"
                                                wire:loading.attr="disabled"
                                                wire:loading.class="opacity-50 cursor-wait"
                                                wire:target="markAsPaid({{ $order->id }}, 'cash')"
                                                class="btn-primary !px-3 !py-2 justify-center shadow-sm hover:scale-[1.02] active:scale-[0.98] transition-all">
                                            <span wire:loading.remove wire:target="markAsPaid({{ $order->id }}, 'cash')">{{ __('admin.cash') }}</span>
                                            <span wire:loading wire:target="markAsPaid({{ $order->id }}, 'cash')" class="loading-spinner"></span>
                                        </button>
                                        <button wire:click="markAsPaid({{ $order->id }}, 'card')"
                                                wire:loading.attr="disabled"
                                                wire:loading.class="opacity-50 cursor-wait"
                                                wire:target="markAsPaid({{ $order->id }}, 'card')"
                                                class="btn-primary !px-3 !py-2 justify-center shadow-sm hover:scale-[1.02] active:scale-[0.98] transition-all">
                                            <span wire:loading.remove wire:target="markAsPaid({{ $order->id }}, 'card')">{{ __('admin.card') }}</span>
                                            <span wire:loading wire:target="markAsPaid({{ $order->id }}, 'card')" class="loading-spinner"></span>
                                        </button>
                                    </div>
                                    <button wire:click="openSplit({{ $order->id }})" class="btn-secondary w-full justify-center !py-2 shadow-sm hover:scale-[1.02] active:scale-[0.98] transition-all">
                                        {{ __('admin.split_items') }}
                                    </button>
                                    <button wire:click="openPayment({{ $order->id }})" class="w-full text-center font-mono text-[9px] font-bold uppercase tracking-[0.14em] text-ink-soft hover:text-ink hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200">
                                        {{ __('admin.split_payment') }}&hellip;
                                    </button>
                                @elseif($order->status === 'ready')
                                    <button wire:click="markAsDelivered({{ $order->id }})"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="opacity-50 cursor-wait"
                                            wire:target="markAsDelivered({{ $order->id }})"
                                            class="btn-primary w-full justify-center !bg-signal !border-signal !py-2.5 shadow-sm shadow-signal/15 hover:shadow-signal/25 hover:scale-[1.02] active:scale-[0.98] transition-all">
                                        <span wire:loading.remove wire:target="markAsDelivered({{ $order->id }})">{{ __('admin.mark_delivered') }}</span>
                                        <span wire:loading wire:target="markAsDelivered({{ $order->id }})" class="loading-spinner"></span>
                                    </button>
                                @endif

                                <button
                                    wire:click="cancelOrder({{ $order->id }})"
                                    wire:confirm="{{ __('admin.cancel_order_confirm', ['id' => $order->id]) }}"
                                    wire:loading.attr="disabled"
                                    wire:target="cancelOrder({{ $order->id }})"
                                    class="w-full text-center font-mono text-[9px] font-bold uppercase tracking-[0.14em] text-alert hover:text-alert/80 transition-colors mt-2"
                                >
                                    <span wire:loading.remove wire:target="cancelOrder({{ $order->id }})">{{ __('admin.cancel_order') }}</span>
                                    <span wire:loading wire:target="cancelOrder({{ $order->id }})" class="loading-spinner" style="width: 10px; height: 10px; border-width: 1px;"></span>
                                </button>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="col-span-full py-6">
                        <div class="surface-card border-dashed p-16 text-center border-line/60 bg-mist/5 rounded-2xl">
                            <svg class="mx-auto h-10 w-10 text-ink-soft/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                            <p class="mt-4 font-mono text-[10px] font-bold uppercase tracking-[0.2em] text-ink-soft/80">{{ __('admin.no_active_orders') }}</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </section>

        <!-- Sidebar Panel: Actions and Status -->
        <aside class="space-y-5 lg:col-span-1 transition-opacity duration-300" wire:loading.class="opacity-60">
            <!-- Metrics Card -->
            <section class="surface-card relative overflow-hidden bg-gradient-to-br from-forest via-forest to-pine text-panel border-0 shadow-lg shadow-forest/10 p-1">
                <!-- Ambient glow decorations -->
                <div class="absolute -right-16 -top-16 h-36 w-36 rounded-full bg-lime/10 blur-2xl pointer-events-none"></div>
                <div class="absolute -left-16 -bottom-16 h-36 w-36 rounded-full bg-signal/15 blur-2xl pointer-events-none"></div>
                
                <div class="relative z-10 border-b border-panel/10 px-5 py-5">
                    <p class="font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-panel/60">{{ __('admin.today') }}</p>
                    <h3 class="mt-2 font-display text-4xl font-extrabold leading-none text-lime tracking-tight"><x-price :amount="$salesToday" :shop="$shop" /></h3>
                </div>

                <div class="relative z-10 grid grid-cols-2 gap-3 p-5">
                    <div class="rounded-xl border border-panel/10 bg-panel/5 backdrop-blur-sm px-3.5 py-3.5 transition-all hover:bg-panel/10 duration-200">
                        <p class="font-mono text-[9px] font-bold uppercase tracking-[0.16em] text-panel/55">{{ __('admin.orders') }}</p>
                        <p class="mt-1 font-display text-2xl font-bold text-panel">{{ $ordersToday }}</p>
                    </div>
                    <div class="rounded-xl border border-lime/30 bg-lime/10 backdrop-blur-sm px-3.5 py-3.5 transition-all hover:bg-lime/15 duration-200 flex flex-col justify-between">
                        <p class="font-mono text-[9px] font-bold uppercase tracking-[0.16em] text-lime-200/90">{{ __('admin.pos_status') }}</p>
                        <div class="mt-1 flex items-center gap-1.5 font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-panel">
                            <span class="inline-block h-2 w-2 rounded-full bg-lime animate-pulse pos-status-pulse"></span>
                            <span>{{ __('admin.pos_online') }}</span>
                        </div>
                    </div>
                    <div class="rounded-xl border border-panel/10 bg-panel/5 backdrop-blur-sm px-3.5 py-3.5 transition-all hover:bg-panel/10 duration-200">
                        <p class="font-mono text-[9px] font-bold uppercase tracking-[0.16em] text-panel/55">{{ __('admin.pos_unpaid') }}</p>
                        <p class="mt-1 font-display text-2xl font-bold text-panel">{{ $unpaidCount }}</p>
                    </div>
                    <div class="rounded-xl border border-panel/10 bg-panel/5 backdrop-blur-sm px-3.5 py-3.5 transition-all hover:bg-panel/10 duration-200">
                        <p class="font-mono text-[9px] font-bold uppercase tracking-[0.16em] text-panel/55">{{ __('admin.pos_ready') }}</p>
                        <p class="mt-1 font-display text-2xl font-bold text-panel">{{ $readyCount }}</p>
                    </div>
                </div>
            </section>

            <!-- Actions Panel -->
            <section class="surface-card space-y-3 p-5 shadow-sm">
                <p class="section-headline tracking-[0.2em]">{{ __('admin.system_actions') }}</p>
                <button
                    x-on:click="$dispatch('confirm-action', {
                        title: '{{ __('admin.clear_old_orders') }}',
                        message: '{{ __('admin.clear_old_orders_desc') }}',
                        action: 'clearOldOrders',
                        componentId: $wire.id,
                        destructive: false,
                    })"
                    class="btn-secondary w-full justify-center border-forest/30 text-forest hover:bg-forest/5 hover:scale-[1.01] active:scale-[0.99] transition-all"
                >
                    {{ __('admin.clear_old_orders') }}
                </button>
                <button
                    x-on:click="$dispatch('confirm-action', {
                        title: '{{ __('admin.system_reset') }}',
                        message: '{{ __('admin.system_reset_desc') }}',
                        action: 'systemReset',
                        componentId: $wire.id,
                        destructive: true,
                    })"
                    class="btn-danger w-full justify-center hover:scale-[1.01] active:scale-[0.99] shadow-sm transition-all"
                >
                    {{ __('admin.system_reset') }}
                </button>
            </section>

            {{-- Auto-86: Menu Status Panel (admin/manager only) --}}
            @if(in_array(Auth::user()->role, ['admin', 'manager'], true))
                <section class="surface-card overflow-hidden shadow-sm" x-data="{ open: false }">
                    <button x-on:click="open = !open" class="flex w-full items-center justify-between px-5 py-4 text-left transition-colors hover:bg-mist/20">
                        <div>
                            <p class="section-headline tracking-[0.2em]">{{ __('admin.menu_status') }}</p>
                            @php
                                $totalProducts = $menuCategories->sum(fn ($cat) => $cat->products->count());
                                $unavailableCount = $menuCategories->sum(fn ($cat) => $cat->products->where('is_available', false)->count());
                            @endphp
                            @if($unavailableCount > 0)
                                <p class="mt-1 font-mono text-[9px] font-bold uppercase tracking-[0.14em] text-alert">{{ $unavailableCount }} {{ __('admin.items_86d') }}</p>
                            @endif
                        </div>
                        <svg class="h-4 w-4 text-ink-soft transition-transform duration-200" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </button>

                    <div x-show="open" x-collapse>
                        <div class="max-h-[50vh] space-y-4.5 overflow-y-auto border-t border-line bg-mist/5 px-5 py-4">
                            @foreach($menuCategories as $category)
                                @if($category->products->isNotEmpty())
                                    <div>
                                        <p class="font-mono text-[9px] font-bold uppercase tracking-[0.16em] text-ink-soft border-b border-line/30 pb-1">{{ $category->name_en }}</p>
                                        <div class="mt-2 space-y-1">
                                            @foreach($category->products as $product)
                                                <div class="flex items-center justify-between gap-2 rounded-xl px-2.5 py-1.5 transition-colors border border-transparent {{ ! $product->is_available ? 'bg-alert/5 border-alert/20' : 'hover:bg-panel border-transparent' }}">
                                                    <span class="min-w-0 truncate font-mono text-xs {{ ! $product->is_available ? 'text-ink-soft/40 line-through font-semibold' : 'text-ink' }}">{{ $product->name_en }}</span>
                                                    <button
                                                        wire:click="toggle86({{ $product->id }})"
                                                        wire:loading.attr="disabled"
                                                        wire:target="toggle86({{ $product->id }})"
                                                        class="flex-shrink-0 rounded-full border px-3 py-1 font-mono text-[9px] font-bold uppercase tracking-[0.12em] transition-all duration-200 {{ ! $product->is_available ? 'border-alert/50 bg-alert/15 text-alert hover:bg-alert/25' : 'border-line text-ink-soft/50 hover:border-ink hover:text-ink-soft bg-panel shadow-sm' }}"
                                                        title="{{ ! $product->is_available ? __('admin.restore_item') : __('admin.mark_86') }}"
                                                    >
                                                        <span wire:loading.remove wire:target="toggle86({{ $product->id }})">86</span>
                                                        <span wire:loading wire:target="toggle86({{ $product->id }})" class="loading-spinner" style="width: 10px; height: 10px; border-width: 1px;"></span>
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif
        </aside>
    </div>

    <!-- Modals Sections with Glassmorphism Overlays -->

    {{-- Split Order Modal --}}
    @if($splitOrder)
        <div class="fixed inset-0 z-[120] flex items-end justify-center bg-ink/65 backdrop-blur-md p-0 sm:items-center sm:p-6 transition-all duration-300">
            <div class="surface-card flex w-full max-w-2xl flex-col overflow-hidden sm:rounded-2xl border border-line bg-panel shadow-2xl">
                <div class="flex items-center justify-between border-b border-line bg-mist/10 px-5 py-4">
                    <div>
                        <h3 class="font-display text-2xl font-extrabold leading-none text-ink">{{ __('admin.split_order', ['id' => $splitOrder->id]) }}</h3>
                        <p class="mt-1 font-mono text-[9px] font-bold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.split_order_desc') }}</p>
                    </div>
                    <button wire:click="closeSplit" class="rounded-xl border border-line bg-panel p-2.5 text-ink-soft hover:border-ink hover:text-ink shadow-sm hover:scale-[1.02] active:scale-[0.98] transition-all">
                        <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                @if($splitError)
                    <div class="px-5 pt-5">
                        <div class="rounded-xl border border-alert/35 bg-alert/10 px-4 py-3 font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-alert shadow-sm animate-pulse">
                            {{ $splitError }}
                        </div>
                    </div>
                @endif

                <div class="max-h-[60vh] space-y-3 overflow-y-auto p-5 bg-mist/5">
                    @foreach($splitOrder->items as $item)
                        <div class="rounded-xl border border-line bg-panel px-4 py-3.5 shadow-sm">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-tight text-ink">{{ $item->translated('product_name_snapshot') }}</p>
                                    <p class="mt-1.5 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft"><span class="text-pine font-bold">{{ __('admin.qty') }}: {{ $item->quantity }}</span> <span class="text-ink-soft/40 px-1">|</span> <x-price :amount="$item->price_snapshot" :shop="$shop" /></p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <label class="font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.split_qty') }}</label>
                                    <input type="number" min="0" max="{{ $item->quantity }}" wire:model.live="splitQuantities.{{ $item->id }}" class="field w-full text-center font-mono text-xs font-bold uppercase sm:w-24 border-line focus:border-lime focus:ring-lime/20 rounded-xl">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex gap-3 border-t border-line bg-mist/10 p-5">
                    <button wire:click="closeSplit" class="btn-secondary flex-1 justify-center !py-2.5">{{ __('admin.cancel') }}</button>
                    <button wire:click="applySplit" class="btn-primary flex-1 justify-center bg-gradient-to-r from-forest to-pine text-panel !py-2.5 shadow-md shadow-forest/10 hover:scale-[1.01] active:scale-[0.99] transition-all">{{ __('admin.create_split') }}</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Split Payment Modal --}}
    @if($paymentOrder)
        <div class="fixed inset-0 z-[120] flex items-end justify-center bg-ink/65 backdrop-blur-md p-0 sm:items-center sm:p-6 transition-all duration-300">
            <div class="surface-card flex w-full max-w-2xl flex-col overflow-hidden sm:rounded-2xl border border-line bg-panel shadow-2xl">
                <div class="flex items-center justify-between border-b border-line bg-mist/10 px-5 py-4">
                    <div>
                        <h3 class="font-display text-2xl font-extrabold leading-none text-ink">{{ __('admin.payments_for_order', ['id' => $paymentOrder->id]) }}</h3>
                        <p class="mt-1.5 font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.balance_due') }} <span class="text-alert font-bold"><x-price :amount="$paymentOrder->balance_due" :shop="$shop" /></span></p>
                    </div>
                    <button wire:click="closePayment" class="rounded-xl border border-line bg-panel p-2.5 text-ink-soft hover:border-ink hover:text-ink shadow-sm hover:scale-[1.02] active:scale-[0.98] transition-all">
                        <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                @if($paymentError)
                    <div class="px-5 pt-5">
                        <div class="rounded-xl border border-alert/35 bg-alert/10 px-4 py-3 font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-alert shadow-sm animate-pulse">
                            {{ $paymentError }}
                        </div>
                    </div>
                @endif

                <div class="space-y-5 p-5 bg-mist/5">
                    <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3">
                        <div class="rounded-xl border border-line bg-panel p-4 shadow-sm">
                            <label class="font-mono text-[9px] font-bold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.guests') }}</label>
                            <div class="mt-2.5 flex items-center gap-2">
                                <input type="number" min="1" wire:model="splitGuestCount" class="field w-full text-center font-mono text-xs font-bold uppercase sm:w-20 rounded-xl focus:border-lime focus:ring-lime/20">
                                <button wire:click="splitByGuests" class="btn-secondary !px-4.5 !py-2 hover:scale-[1.02] active:scale-[0.98] shadow-sm transition-all">{{ __('admin.split') }}</button>
                            </div>
                        </div>
                        <div class="rounded-xl border border-line bg-panel p-4 shadow-sm">
                            <label class="font-mono text-[9px] font-bold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.amount') }}</label>
                            <div class="mt-2.5 flex items-center gap-2">
                                <input type="number" min="0" step="0.01" wire:model="splitAmount" class="field w-full text-center font-mono text-xs font-bold uppercase sm:w-24 rounded-xl focus:border-lime focus:ring-lime/20">
                                <button wire:click="splitByAmount" class="btn-secondary !px-4.5 !py-2 hover:scale-[1.02] active:scale-[0.98] shadow-sm transition-all">{{ __('admin.split') }}</button>
                            </div>
                        </div>
                        <div class="rounded-xl border border-line bg-panel p-4 shadow-sm sm:col-span-2 md:col-span-1">
                            <label class="font-mono text-[9px] font-bold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.rows') }}</label>
                            <div class="mt-2.5">
                                <button wire:click="addPaymentRow" class="btn-secondary w-full justify-center !px-4 !py-2 bg-mist/20 border-forest/20 text-forest hover:bg-mist/35 hover:scale-[1.02] active:scale-[0.98] shadow-sm transition-all">{{ __('admin.add_row') }}</button>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2.5">
                        @foreach($paymentRows as $index => $row)
                            <div class="grid items-center gap-3 rounded-xl border border-line bg-panel p-3.5 shadow-sm sm:grid-cols-[auto_auto_1fr]">
                                <input type="number" min="0" step="0.01" wire:model.live="paymentRows.{{ $index }}.amount" class="field w-full text-center font-mono text-xs font-bold uppercase sm:w-28 rounded-xl focus:border-lime focus:ring-lime/20 border-line">
                                <select wire:model.live="paymentRows.{{ $index }}.method" class="field w-full font-mono text-xs font-bold uppercase sm:w-36 rounded-xl focus:border-lime focus:ring-lime/20 border-line">
                                    <option value="cash">{{ __('admin.cash') }}</option>
                                    <option value="card">{{ __('admin.card') }}</option>
                                    <option value="voucher">{{ __('admin.voucher') }}</option>
                                </select>
                                <button wire:click="removePaymentRow({{ $index }})" class="btn-secondary w-full justify-center !border-alert/30 !bg-alert/10 !text-alert hover:!bg-alert/15 sm:w-auto hover:scale-[1.02] active:scale-[0.98] transition-all py-2 rounded-full">
                                    {{ __('admin.remove') }}
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex gap-3 border-t border-line bg-mist/10 p-5">
                    <button wire:click="closePayment" class="btn-secondary flex-1 justify-center !py-2.5">{{ __('admin.cancel') }}</button>
                    <button wire:click="applyPayments" class="btn-primary flex-1 justify-center bg-gradient-to-r from-forest to-pine text-panel !py-2.5 shadow-md shadow-forest/10 hover:scale-[1.01] active:scale-[0.99] transition-all">{{ __('admin.apply_payments') }}</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Manager Override PIN Modal --}}
    @if($showManagerModal)
        <div class="fixed inset-0 z-[130] flex items-end justify-center bg-ink/65 backdrop-blur-md p-0 sm:items-center sm:p-6 transition-all duration-300">
            <div class="surface-card w-full max-w-md overflow-hidden sm:rounded-2xl border border-line bg-panel shadow-2xl">
                <div class="border-b border-line bg-mist/10 px-5 py-4">
                    <h3 class="font-display text-2xl font-extrabold leading-none text-ink">{{ __('admin.manager_override') }}</h3>
                    <p class="mt-1.5 font-mono text-[9px] font-bold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.manager_override_desc') }}</p>
                </div>

                <div class="space-y-4 p-5">
                    <input type="password" maxlength="4" wire:model="managerPin" class="field w-full text-center font-mono text-base font-bold uppercase tracking-[0.55em] rounded-xl focus:border-lime focus:ring-lime/20 border-line py-3.5 bg-mist/5" placeholder="{{ __('admin.pin') }}">
                    @if($managerError)
                        <div class="rounded-xl border border-alert/35 bg-alert/10 px-4 py-3 font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-alert shadow-sm animate-pulse">
                            {{ $managerError }}
                        </div>
                    @endif
                </div>

                <div class="flex gap-3 border-t border-line bg-mist/10 p-5">
                    <button wire:click="cancelManagerOverride" class="btn-secondary flex-1 justify-center !py-2.5">{{ __('admin.cancel') }}</button>
                    <button wire:click="confirmManagerOverride" class="btn-primary flex-1 justify-center bg-gradient-to-r from-forest to-pine text-panel !py-2.5 shadow-md shadow-forest/10 hover:scale-[1.01] active:scale-[0.99] transition-all">{{ __('admin.confirm') }}</button>
                </div>
            </div>
        </div>
    @endif

    {{-- New Order / Cart Modal --}}
    @if($showNewOrder)
        <div class="fixed inset-0 z-[125] flex items-end justify-center bg-ink/65 backdrop-blur-md p-0 sm:items-center sm:p-6 transition-all duration-300">
            <div class="surface-card flex h-[92vh] w-full max-w-5xl flex-col overflow-hidden sm:rounded-2xl border border-line bg-panel shadow-2xl">
                <div class="flex items-center justify-between border-b border-line bg-mist/10 px-6 py-4.5">
                    <div>
                        <h3 class="font-display text-2xl font-extrabold leading-none text-ink">{{ __('admin.new_sale') }}</h3>
                        <p class="mt-1.5 font-mono text-[9px] font-bold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.new_sale_desc') }}</p>
                    </div>
                    <button wire:click="closeNewOrder" class="rounded-xl border border-line bg-panel p-2.5 text-ink-soft hover:border-ink hover:text-ink shadow-sm hover:scale-[1.02] active:scale-[0.98] transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="grid min-h-0 flex-1 md:grid-cols-[1.45fr_1fr]">
                    {{-- Product picker --}}
                    <div class="min-h-0 overflow-y-auto border-b border-line p-5 md:border-b-0 md:border-e bg-mist/5">
                        <p class="section-headline tracking-[0.2em] mb-4.5">{{ __('admin.add_items') }}</p>
                        <div class="space-y-6">
                            @foreach($menuCategories as $category)
                                @if($category->products->isNotEmpty())
                                    <div class="space-y-2.5">
                                        <p class="font-mono text-[9px] font-bold uppercase tracking-[0.2em] text-ink-soft/90 border-b border-line/30 pb-1.5">{{ $category->name_en }}</p>
                                        <div class="grid grid-cols-2 gap-2.5 lg:grid-cols-3">
                                            @foreach($category->products as $product)
                                                <button
                                                    wire:click="addToCart({{ $product->id }})"
                                                    @disabled(! $product->is_available)
                                                    class="flex flex-col items-start gap-1 rounded-xl border border-line bg-panel p-3 text-start transition-all hover:border-forest/40 hover:bg-mist/10 hover:shadow-sm disabled:cursor-not-allowed disabled:bg-alert/5 disabled:border-alert/20 disabled:opacity-40 active:scale-95 duration-200">
                                                    <span class="text-xs font-bold text-ink leading-tight">{{ $product->name_en }}</span>
                                                    <span class="font-mono text-[11px] text-pine font-semibold"><x-price :amount="$product->final_price" :shop="$shop" /></span>
                                                    @unless($product->is_available)
                                                        <span class="mt-1 inline-flex items-center rounded-full border border-alert/50 bg-alert/10 px-1.5 py-0.5 font-mono text-[8px] font-bold uppercase tracking-[0.12em] text-alert">86'D</span>
                                                    @endunless
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                 @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- Cart details --}}
                    <div class="flex min-h-0 flex-col bg-panel">
                        <div class="border-b border-line p-5 bg-mist/5">
                            <label class="font-mono text-[9px] font-bold uppercase tracking-[0.16em] text-ink-soft">{{ __('admin.customer_name_optional') }}</label>
                            <input type="text" wire:model="newOrderName" maxlength="255" placeholder="{{ __('admin.walk_in') }}" class="field mt-2 w-full text-xs font-semibold rounded-xl border-line focus:border-lime focus:ring-lime/20 py-2.5 shadow-sm">
                        </div>

                        @php $cartTotal = collect($posCart)->sum(fn ($r) => $r['price'] * $r['quantity']); @endphp
                        <div class="min-h-0 flex-1 overflow-y-auto p-5 space-y-2.5">
                            @forelse($posCart as $row)
                                <div class="flex items-center gap-3 rounded-xl border border-line bg-panel p-3.5 shadow-sm hover:border-line/60 transition-colors">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-xs font-bold text-ink">{{ $row['name'] }}</p>
                                        <p class="font-mono text-[10px] text-pine font-semibold mt-0.5"><x-price :amount="$row['price']" :shop="$shop" /></p>
                                    </div>
                                    <div class="flex items-center gap-1.5 bg-mist/20 p-1 rounded-xl border border-line/40">
                                        <button wire:click="decrementCartItem({{ $row['id'] }})" class="h-7 w-7 rounded-lg border border-line/50 bg-panel font-mono text-sm font-bold text-ink hover:border-ink hover:bg-mist/10 shadow-sm active:scale-95 transition-all">&minus;</button>
                                        <span class="w-6 text-center font-mono text-xs font-bold text-ink">{{ $row['quantity'] }}</span>
                                        <button wire:click="addToCart({{ $row['id'] }})" class="h-7 w-7 rounded-lg border border-line/50 bg-panel font-mono text-sm font-bold text-ink hover:border-ink hover:bg-mist/10 shadow-sm active:scale-95 transition-all">+</button>
                                    </div>
                                    <button wire:click="removeCartItem({{ $row['id'] }})" class="p-1 rounded-lg hover:bg-alert/10 text-ink-soft/50 hover:text-alert transition-all flex items-center justify-center" title="{{ __('admin.remove') }}">
                                        <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            @empty
                                <div class="flex flex-col items-center justify-center py-16 px-4 text-center border-2 border-dashed border-line/60 rounded-xl bg-mist/5 mt-4">
                                    <svg class="h-8 w-8 text-ink-soft/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                                    <p class="mt-3.5 font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-ink-soft/80">{{ __('admin.cart_empty') }}</p>
                                </div>
                            @endforelse
                        </div>

                        @if($newOrderError)
                            <div class="px-5">
                                <div class="rounded-xl border border-alert/35 bg-alert/10 px-4 py-3 font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-alert shadow-sm animate-pulse">{{ $newOrderError }}</div>
                            </div>
                        @endif

                        <div class="border-t border-line bg-mist/10 p-5 shadow-[0_-4px_20px_rgba(0,0,0,0.02)]">
                            <div class="mb-4 flex items-center justify-between">
                                <span class="font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-ink-soft">{{ __('admin.total') }}</span>
                                <span class="font-display text-3xl font-extrabold text-forest"><x-price :amount="$cartTotal" :shop="$shop" /></span>
                            </div>
                            <div class="grid grid-cols-2 gap-2.5">
                                <button wire:click="chargeNewOrder('cash')" wire:target="chargeNewOrder('cash')" wire:loading.attr="disabled" @disabled(empty($posCart)) class="btn-primary justify-center bg-gradient-to-r from-forest to-pine text-panel !py-3 shadow-md shadow-forest/10 hover:scale-[1.01] active:scale-[0.99] transition-all disabled:opacity-40 disabled:scale-100 disabled:shadow-none">
                                    <span wire:loading.remove wire:target="chargeNewOrder('cash')">{{ __('admin.charge_cash') }}</span>
                                    <span wire:loading wire:target="chargeNewOrder('cash')" class="loading-spinner"></span>
                                </button>
                                <button wire:click="chargeNewOrder('card')" wire:target="chargeNewOrder('card')" wire:loading.attr="disabled" @disabled(empty($posCart)) class="btn-secondary justify-center !py-3 hover:scale-[1.01] active:scale-[0.99] transition-all disabled:opacity-40 disabled:scale-100">
                                    <span wire:loading.remove wire:target="chargeNewOrder('card')">{{ __('admin.charge_card') }}</span>
                                    <span wire:loading wire:target="chargeNewOrder('card')" class="loading-spinner"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
