<div class="relative flex min-h-full flex-col overflow-x-hidden bg-transparent">
    <header class="sticky top-0 z-50 border-b border-line/80 bg-panel/85 px-4 py-4 backdrop-blur-xl sm:px-6">
        <div class="mx-auto flex w-full max-w-6xl items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg border border-line bg-ink text-panel font-display text-xl font-black">B</div>
                <div>
                    <h1 class="font-display text-2xl font-extrabold leading-none text-ink">{{ $shop->name }}</h1>
                    <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.18em] text-ink-soft">Guest Ordering</p>
                </div>
            </div>
            <span class="tag">Mobile Checkout</span>
        </div>
    </header>

    <main class="mx-auto w-full max-w-6xl flex-1 space-y-10 px-4 py-6 pb-32 sm:px-6">
        @if (session()->has('message'))
            <div class="rounded-xl border border-signal/35 bg-signal/10 px-4 py-3 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-signal">
                {{ session('message') }}
            </div>
        @endif

        <section class="surface-card p-5 sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="section-headline">Guest Experience</p>
                    <h2 class="mt-1 text-3xl font-extrabold leading-none text-ink">Build Your Order</h2>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" onclick="window.biteFavorites.load({{ $shop->id }})" class="btn-secondary !px-3 !py-2">
                        Load Favorite
                    </button>
                    <button type="button" wire:click="saveFavorite" class="btn-primary !px-3 !py-2">
                        Save Favorite
                    </button>
                </div>
            </div>
        </section>

        @forelse($categories as $category)
            <section class="space-y-4">
                <div class="flex items-center gap-3">
                    <h3 class="font-display text-2xl font-extrabold leading-none text-ink">{{ $category->name }}</h3>
                    <div class="h-px flex-1 bg-line"></div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    @foreach($category->products as $product)
                        <article class="surface-card flex flex-col justify-between p-5">
                            @if($product->is_on_sale)
                                <div class="absolute right-4 top-4 rounded-full border border-crema/50 bg-crema/10 px-2.5 py-1 font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-crema">
                                    Flash Sale
                                </div>
                            @endif

                            <div>
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h4 class="text-xl font-bold uppercase tracking-tight text-ink">{{ $product->name }}</h4>
                                        <p class="mt-2 text-sm leading-relaxed text-ink-soft">{{ $product->description }}</p>
                                    </div>
                                    <div class="text-right">
                                        @if($product->is_on_sale)
                                            <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft line-through">{{ formatPrice($product->price, $shop) }}</p>
                                            <p class="font-display text-2xl font-extrabold leading-none text-crema">{{ formatPrice($product->final_price, $shop) }}</p>
                                        @else
                                            <p class="font-display text-2xl font-extrabold leading-none text-ink">{{ formatPrice($product->price, $shop) }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <button wire:click="addToCart({{ $product->id }})" class="btn-primary mt-5 w-full justify-center">
                                Add to Order
                            </button>
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <section class="surface-card border-dashed p-14 text-center">
                <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.2em] text-ink-soft">No items available</p>
            </section>
        @endforelse
    </main>

    @if(count($cart) > 0)
        <div class="fixed bottom-0 left-0 right-0 z-[60] p-4 sm:p-6">
            <div class="mx-auto w-full max-w-6xl">
                <button wire:click="toggleReview" class="surface-card flex w-full items-center justify-between gap-3 border-ink bg-ink px-5 py-4 text-panel transition-transform duration-200 hover:-translate-y-0.5">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center rounded-full border border-panel/20 bg-panel/15 px-2.5 py-1 font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-panel/90">Ready</span>
                        <span class="font-display text-2xl font-bold leading-none">Review Order</span>
                    </div>
                    <span class="font-display text-3xl font-extrabold leading-none">{{ formatPrice($this->total, $shop) }}</span>
                </button>
            </div>
        </div>
    @endif

    @if($showReviewModal)
        <div class="fixed inset-0 z-[100] flex items-end justify-center bg-ink/75 p-0 backdrop-blur-sm sm:items-center sm:p-6">
            <div class="surface-card h-full w-full max-w-2xl overflow-hidden sm:h-auto sm:max-h-[90vh]">
                <div class="border-b border-line bg-muted/35 px-6 py-5 sm:px-8">
                    <h3 class="font-display text-3xl font-extrabold leading-none text-ink">Your Order</h3>
                    <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Review items before sending to kitchen</p>
                </div>

                <div class="flex-1 space-y-6 overflow-y-auto p-6 sm:p-8">
                    <section class="space-y-3">
                        <p class="section-headline">Items</p>
                        <div class="divide-y divide-line rounded-xl border border-line bg-panel">
                            @foreach($cart as $key => $item)
                                <div class="flex items-start justify-between gap-3 px-4 py-3">
                                    <div class="flex items-start gap-3">
                                        <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-md border border-line bg-muted font-mono text-[10px] font-bold uppercase">{{ $item['quantity'] }}x</span>
                                        <div>
                                            <p class="text-sm font-semibold uppercase tracking-tight text-ink">{{ $item['name'] }}</p>
                                            @if(!empty($item['selectedModifiers']))
                                                <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">+ {{ count($item['selectedModifiers']) }} options</p>
                                            @endif
                                        </div>
                                    </div>
                                    <p class="font-mono text-xs font-bold uppercase text-ink">{{ formatPrice($item['price'] * $item['quantity'], $shop) }}</p>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="grid grid-cols-3 gap-3">
                        <div class="rounded-lg border border-line bg-panel px-3 py-2">
                            <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.14em] text-ink-soft">Subtotal</p>
                            <p class="mt-1 font-mono text-xs font-bold uppercase text-ink">{{ formatPrice($this->subtotal, $shop) }}</p>
                        </div>
                        <div class="rounded-lg border border-line bg-panel px-3 py-2">
                            <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.14em] text-ink-soft">Tax</p>
                            <p class="mt-1 font-mono text-xs font-bold uppercase text-ink">{{ formatPrice($this->tax, $shop) }}</p>
                        </div>
                        <div class="rounded-lg border border-line bg-panel px-3 py-2">
                            <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.14em] text-ink-soft">Total</p>
                            <p class="mt-1 font-mono text-xs font-bold uppercase text-ink">{{ formatPrice($this->total, $shop) }}</p>
                        </div>
                    </section>

                    <section class="space-y-2">
                        <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Loyalty Phone (Optional)</label>
                        <input type="tel" wire:model="loyaltyPhone" class="field w-full font-mono text-sm font-semibold" placeholder="e.g. 5551234567">
                        @if($loyaltyError)
                            <div class="rounded-lg border border-alert/35 bg-alert/10 px-3 py-2 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-alert">
                                {{ $loyaltyError }}
                            </div>
                        @endif
                    </section>
                </div>

                <div class="grid grid-cols-2 gap-3 border-t border-line bg-muted/20 p-6 sm:p-8">
                    <button wire:click="toggleReview" class="btn-secondary w-full justify-center">Cancel</button>
                    <button wire:click="submitOrder" wire:confirm="Send order to kitchen?" class="btn-primary w-full justify-center">
                        Place Order
                    </button>
                </div>
            </div>
        </div>
    @endif

    @once
        <script>
            document.addEventListener('livewire:initialized', () => {
                window.addEventListener('favorite:save', (event) => {
                    const items = event.detail.items || [];
                    const shop = event.detail.shop || 'default';
                    const key = `bite_favorite_${shop}`;
                    localStorage.setItem(key, JSON.stringify(items));
                });

                window.biteFavorites = window.biteFavorites || {};
                window.biteFavorites.load = (shop) => {
                    const key = `bite_favorite_${shop}`;
                    let items = [];
                    try {
                        const raw = localStorage.getItem(key);
                        items = raw ? JSON.parse(raw) : [];
                    } catch (error) {
                        items = [];
                    }

                    if (window.Livewire) {
                        window.Livewire.dispatch('favorite:apply', { items });
                    }
                };
            });
        </script>
    @endonce

    @if($showModifierModal && $customizingProduct)
        <div class="fixed inset-0 z-[100] flex items-end justify-center bg-ink/75 p-0 backdrop-blur-sm sm:items-center sm:p-6">
            <div class="surface-card w-full max-w-xl overflow-hidden border-t sm:border">
                <div class="flex items-center justify-between border-b border-line bg-muted/35 px-6 py-5 sm:px-8">
                    <div>
                        <h3 class="font-display text-3xl font-extrabold leading-none text-ink">{{ $customizingProduct->name }}</h3>
                        <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Price: {{ formatPrice($this->customizingProductPrice, $shop) }}</p>
                    </div>
                    <button wire:click="$set('showModifierModal', false)" class="rounded-md border border-line bg-panel p-2 text-ink-soft hover:border-ink hover:text-ink">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                @if($modifierError)
                    <div class="px-6 pt-5 sm:px-8">
                        <div class="rounded-lg border border-alert/35 bg-alert/10 px-3 py-2 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-alert">
                            {{ $modifierError }}
                        </div>
                    </div>
                @endif

                <div class="max-h-[60vh] space-y-8 overflow-y-auto p-6 sm:p-8">
                    @foreach($customizingProduct->modifierGroups as $group)
                        <section class="space-y-3">
                            <div class="flex items-end justify-between border-b border-line pb-2">
                                <h4 class="font-mono text-[11px] font-semibold uppercase tracking-[0.18em] text-ink-soft">{{ $group->name }}</h4>
                                <span class="font-mono text-[9px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ $group->min_selection > 0 ? 'Required' : 'Optional' }}</span>
                            </div>

                            <div class="space-y-2">
                                @foreach($group->options as $option)
                                    <label class="flex cursor-pointer items-center justify-between rounded-lg border border-line bg-panel px-3 py-3 transition-colors duration-200 hover:border-ink-soft has-[:checked]:border-crema has-[:checked]:bg-crema/5">
                                        <span class="flex items-center gap-3">
                                            <input
                                                type="{{ $group->max_selection == 1 ? 'radio' : 'checkbox' }}"
                                                value="{{ $option->id }}"
                                                wire:model.live="selectedModifiers.{{ $group->id }}"
                                                name="group_{{ $group->id }}"
                                                class="h-4 w-4 cursor-pointer border-line text-crema focus:ring-0"
                                            >
                                            <span class="text-sm font-semibold uppercase tracking-tight text-ink">{{ $option->name }}</span>
                                        </span>
                                        @if($option->price_adjustment > 0)
                                            <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-crema">+{{ formatPrice($option->price_adjustment, $shop) }}</span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>

                <div class="grid grid-cols-2 gap-3 border-t border-line bg-muted/20 p-6 sm:p-8">
                    <button wire:click="$set('showModifierModal', false)" class="btn-secondary w-full justify-center">Cancel</button>
                    <button wire:click="addToCart({{ $customizingProduct->id }})" class="btn-primary w-full justify-center">
                        Add for {{ formatPrice($this->customizingProductPrice, $shop) }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
