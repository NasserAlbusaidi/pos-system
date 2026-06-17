<div class="guest-menu-bg guest-shell relative flex min-h-full flex-col overflow-x-hidden"
     data-guest-screen="{{ $screen }}"
     @if($this->isGroupMode) wire:poll.3s @endif>

    {{-- Language gate (mockup screen 1) — full-screen, blocks menu until a language is picked --}}
    @if($showLanguageGate)
        @include('livewire.partials.guest-gate')
    @endif

    {{-- Both screens render every time; the active one is shown via
         [data-guest-screen] + CSS (the menu wrapper uses display:contents so its
         flex children still fill the shell). Keeping both in the DOM means a
         Livewire re-render (addToCart) preserves the screen and the browse markup
         is present on first paint. Home is the landing; "See all" → showMenu(). --}}
    @include('livewire.partials.guest-home')

    {{-- Full-menu screen (prototype screen 3 / "order"). Green subheader + sticky
         category chips + grouped menu-row list. The Alpine scope lives on this
         wrapper (not <main>) so the search input in the subheader and the rows in
         the list share one `query`. --}}
    <div
        class="guest-screen--menu"
        x-data="{
            query: '',
            activeCategory: 'all',
            allNames: @js(array_values($searchNames)),
            get hasMatches() {
                const q = this.query.toLowerCase().trim();
                return q === '' || this.allNames.some(n => n.includes(q));
            },
        }"
    >
        @include('livewire.partials.guest-subheader')

    {{-- Group Mode Banner --}}
    @if($this->isGroupMode)
        <div class="border-b border-crema/20 bg-crema/5 px-4 py-3 sm:px-6">
            <div class="mx-auto flex w-full max-w-6xl items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-3 w-3 rounded-full" style="background-color: {{ $participantColors[$participantId] ?? '#E57373' }}"></span>
                    <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-crema">
                        {{ __('guest.group_banner', ['count' => count(array_unique(array_column($groupCartItems, 'participant_id')))]) }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button wire:click="toggleGroupShare" class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-crema underline underline-offset-2 transition-colors hover:text-crema/80" type="button">
                        {{ __('guest.share_link') }}
                    </button>
                    <button wire:click="leaveGroup" class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft underline underline-offset-2 transition-colors hover:text-alert" type="button">
                        {{ __('guest.leave_group') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <main class="guest-menu-screen mx-auto w-full max-w-6xl flex-1 px-4 pb-28 sm:px-6">
        {{-- Sticky category chips (prototype web-category-row). The search field
             lives in the green subheader above; both drive the wrapper's shared
             `query` / `activeCategory`, so filtering stays client-side. --}}
        @if($categories->isNotEmpty())
            <div class="guest-tabs" role="tablist">
                <button
                    type="button"
                    @click="activeCategory = 'all'"
                    class="guest-tab"
                    :class="{ 'guest-tab--on': activeCategory === 'all' }"
                >{{ __('guest.category_all') }}</button>
                @foreach($categories as $category)
                    <button
                        type="button"
                        @click="activeCategory = '{{ $category->id }}'"
                        class="guest-tab"
                        :class="{ 'guest-tab--on': activeCategory === '{{ $category->id }}' }"
                    >{{ $category->translated('name') }}</button>
                @endforeach
            </div>
        @endif

        {{-- Empty-search hint (shown only when search hides every item) --}}
        <p class="guest-noresults" x-show="query.trim() !== '' && !hasMatches" x-cloak>
            {{ __('guest.no_search_results') }}
        </p>

        {{-- Skeleton menu rows shown while language switches --}}
        <div wire:loading wire:target="switchLanguage" class="menu-list pt-2">
            @for($s = 0; $s < 2; $s++)
                <section class="menu-category-section">
                    <div class="skeleton h-5 w-32">&nbsp;</div>
                    <div class="menu-category-items">
                        @for($i = 0; $i < 3; $i++)
                            <article class="menu-row">
                                <span class="menu-row__img skeleton"></span>
                                <div class="menu-row__body">
                                    <div class="skeleton h-4 w-3/4">&nbsp;</div>
                                    <div class="skeleton h-3 w-1/2">&nbsp;</div>
                                </div>
                            </article>
                        @endfor
                    </div>
                </section>
            @endfor
        </div>

        {{-- Actual menu content hidden during language switch --}}
        <div wire:loading.remove wire:target="switchLanguage" class="menu-list">
            @forelse($categories as $category)
                @php
                    $categoryNames = $category->products
                        ->map(fn ($p) => $searchNames[$p->id] ?? '')
                        ->values()
                        ->all();
                @endphp
                <section
                    class="menu-category-section"
                    data-category-section="{{ $category->id }}"
                    x-data="{ names: @js($categoryNames) }"
                    x-show="(activeCategory === 'all' || activeCategory === '{{ $category->id }}')
                        && (query.trim() === '' || names.some(n => n.includes(query.toLowerCase().trim())))"
                >
                    <h2 class="menu-category-title">{{ $category->translated('name') }}</h2>

                    <div class="menu-category-items">
                        @foreach($category->products as $product)
                            @php
                                $rowTimePriced = $pricingRules->isNotEmpty()
                                    ? $product->getTimePriced($pricingRules)
                                    : null;
                                $rowHasDiscount = $rowTimePriced !== null && $rowTimePriced < $product->final_price;
                                $rowPrice = $rowHasDiscount ? $rowTimePriced : ($product->is_on_sale ? $product->final_price : $product->price);
                                $rowName = $searchNames[$product->id] ?? '';
                            @endphp
                            <article
                                class="menu-row {{ ! $product->is_available ? 'menu-row--sold-out' : '' }}"
                                data-name="{{ $rowName }}"
                                x-bind:hidden="query.trim() !== '' && !$el.dataset.name.includes(query.toLowerCase().trim())"
                                wire:key="product-{{ $product->id }}"
                            >
                                {{-- Tile opens the detail sheet (Phase 7e — keeps the per-item
                                     note reachable); disabled when sold out. The '+' keeps
                                     wire:click.stop as a quick-add. --}}
                                <button
                                    type="button"
                                    class="menu-row__open"
                                    @if($product->is_available)
                                        wire:click="openProductSheet({{ $product->id }})"
                                        aria-label="{{ __('guest.view_details_aria', ['name' => $product->translated('name')]) }}"
                                    @else
                                        disabled
                                    @endif
                                >
                                    @if(productImage($product, 'card'))
                                        <img src="{{ productImage($product, 'card') }}" alt="{{ $product->translated('name') }}" class="menu-row__img" loading="lazy">
                                    @else
                                        <span class="menu-row__img guest-home__imgph" aria-hidden="true"></span>
                                    @endif
                                    <div class="menu-row__body">
                                        <h3 class="menu-row__name">{{ $product->translated('name') }}</h3>
                                        @if($product->translated('description'))
                                            <p class="menu-row__desc">{{ Str::limit($product->translated('description'), 64) }}</p>
                                        @endif
                                        <strong class="menu-row__price">
                                            @if($rowHasDiscount || $product->is_on_sale)
                                                <span class="guest-home__strike"><x-price :amount="$product->price" :shop="$shop" /></span>
                                            @endif
                                            <x-price :amount="$rowPrice" :shop="$shop" />
                                        </strong>
                                    </div>
                                </button>
                                @if($product->is_available)
                                    <button type="button" wire:click.stop="addToCart({{ $product->id }})" class="menu-row__plus" aria-label="{{ __('guest.add_item_aria', ['name' => $product->translated('name')]) }}">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                                    </button>
                                @else
                                    <span class="menu-row__soldout">{{ __('guest.sold_out') }}</span>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </section>
            @empty
                <section class="guest-empty">
                    <p class="guest-empty__title">{{ __('guest.no_items_available') }}</p>
                    <p class="guest-empty__hint">{{ __('guest.no_items_hint') }}</p>
                </section>
            @endforelse
        </div>
    </main>
    </div>{{-- /guest-screen--menu --}}

    {{-- Sticky green cart CTA (prototype web-cart-cta) — shared across home + menu,
         replaces the two earlier dark bottom bars. Shown only when the cart is
         non-empty (solo or group). --}}
    @include('livewire.partials.guest-cart-cta')

    {{-- Cart / review + checkout sheet (mockup screens 5 & 6, #24). Re-skinned
         onto the guest design system. Pay-at-counter only — no online payment,
         no service-fee/VAT/voucher line (scope #29). The existing inc/dec/remove
         methods and the server-side submitOrder() validation are reused as-is.
         The order is created 'unpaid'; the guest never sets payment_method. --}}
    @if($showReviewModal)
        @php
            $cartIsEmpty = $this->isGroupMode ? empty($groupCartItems) : empty($cart);
        @endphp
        <div class="guest-sheet-backdrop">
            <div class="guest-sheet">
                <div class="guest-cart__head">
                    <h3 class="guest-cart__title">
                        @if($this->isGroupMode)
                            {{ __('guest.group_order') }}
                        @else
                            {{ __('guest.your_order') }}
                        @endif
                    </h3>
                    <p class="guest-cart__subtitle">{{ $shop->name }} · {{ __('guest.dine_in') }}</p>
                </div>

                <div class="guest-sheet__scroll">
                    <div class="guest-cart__body">
                        @if($cartIsEmpty)
                            {{-- Friendly empty state --}}
                            <div class="guest-cart__empty">
                                <svg class="guest-cart__empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8 2a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z"/></svg>
                                <p class="guest-cart__empty-title">{{ __('guest.cart_empty_title') }}</p>
                                <p class="guest-cart__empty-body">{{ __('guest.cart_empty_body') }}</p>
                                <button wire:click="toggleReview" type="button" class="guest-addbtn guest-addbtn--dark">
                                    {{ __('guest.browse_menu') }}
                                </button>
                            </div>
                        @else
                            <div class="guest-cart__ctx">
                                <svg class="guest-cart__ctx-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8 2a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z"/></svg>
                                <span>{{ __('guest.counter_pickup_hint') }}</span>
                            </div>

                            @if($this->isGroupMode)
                                {{-- Group cart lines, grouped by participant --}}
                                @php $groupedByParticipant = collect($groupCartItems)->groupBy('participant_id'); @endphp
                                @foreach($groupedByParticipant as $pid => $items)
                                    <div class="guest-cart__participant">
                                        <span class="guest-cart__participant-dot" style="background-color: {{ $participantColors[$pid] ?? '#E57373' }}"></span>
                                        <span class="guest-cart__participant-name">
                                            @if($pid === $participantId)
                                                {{ __('guest.you') }}
                                            @else
                                                {{ __('guest.participant') }} {{ array_search($pid, array_keys($groupedByParticipant->toArray())) + 1 }}
                                            @endif
                                        </span>
                                    </div>
                                    @foreach($items as $item)
                                        <div class="guest-cartline">
                                            @if(filled($item['image'] ?? null))
                                                <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="guest-cartline__img" loading="lazy">
                                            @else
                                                <span class="guest-cartline__img guest-cartline__img--ph" aria-hidden="true"></span>
                                            @endif
                                            <div class="guest-cartline__info">
                                                <p class="guest-cartline__name">{{ $item['name'] }}</p>
                                                @if(!empty($item['modifierNames']))
                                                    <p class="guest-cartline__mod">{{ implode(' · ', $item['modifierNames']) }}</p>
                                                @endif
                                                @if(filled($item['note'] ?? null))
                                                    <p class="guest-cartline__note">
                                                        <svg class="guest-cartline__note-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>
                                                        <span>{{ $item['note'] }}</span>
                                                    </p>
                                                @endif
                                                <p class="guest-cartline__price"><x-price :amount="($item['price'] ?? 0) * ($item['quantity'] ?? 1)" :shop="$shop" /></p>
                                            </div>
                                            @if($pid === $participantId)
                                                <div class="guest-cartline__controls">
                                                    <div class="guest-ministep">
                                                        <button wire:click="decrementItem('{{ $item['itemKey'] ?? '' }}')" class="guest-ministep__btn" type="button" aria-label="{{ __('guest.decrease_qty') }}">−</button>
                                                        <span class="guest-ministep__qty">{{ $item['quantity'] }}</span>
                                                        <button wire:click="incrementItem('{{ $item['itemKey'] ?? '' }}')" class="guest-ministep__btn" type="button" aria-label="{{ __('guest.increase_qty') }}">+</button>
                                                    </div>
                                                    <button wire:click="removeItem('{{ $item['itemKey'] ?? '' }}')" class="guest-cartline__remove" type="button" aria-label="{{ __('guest.remove_item') }}">
                                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </div>
                                            @else
                                                <div class="guest-ministep"><span class="guest-ministep__qty">{{ $item['quantity'] }}×</span></div>
                                            @endif
                                        </div>
                                    @endforeach
                                @endforeach
                            @else
                                {{-- Solo cart lines (prototype web-cart-line: thumb + info + stepper) --}}
                                @foreach($cart as $key => $item)
                                    <div class="guest-cartline">
                                        @if(filled($item['image'] ?? null))
                                            <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="guest-cartline__img" loading="lazy">
                                        @else
                                            <span class="guest-cartline__img guest-cartline__img--ph" aria-hidden="true"></span>
                                        @endif
                                        <div class="guest-cartline__info">
                                            <p class="guest-cartline__name">{{ $item['name'] }}</p>
                                            @if(!empty($item['modifierNames']))
                                                <p class="guest-cartline__mod">{{ implode(' · ', $item['modifierNames']) }}</p>
                                            @endif
                                            @if(filled($item['note'] ?? null))
                                                <p class="guest-cartline__note">
                                                    <svg class="guest-cartline__note-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>
                                                    <span>{{ $item['note'] }}</span>
                                                </p>
                                            @endif
                                            <p class="guest-cartline__price"><x-price :amount="$item['price'] * $item['quantity']" :shop="$shop" /></p>
                                        </div>
                                        <div class="guest-cartline__controls">
                                            <div class="guest-ministep">
                                                <button wire:click="decrementItem('{{ $key }}')" class="guest-ministep__btn" type="button" aria-label="{{ __('guest.decrease_qty') }}">−</button>
                                                <span class="guest-ministep__qty">{{ $item['quantity'] }}</span>
                                                <button wire:click="incrementItem('{{ $key }}')" class="guest-ministep__btn" type="button" aria-label="{{ __('guest.increase_qty') }}">+</button>
                                            </div>
                                            <button wire:click="removeItem('{{ $key }}')" class="guest-cartline__remove" type="button" aria-label="{{ __('guest.remove_item') }}">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            @endif

                            {{-- Order-level note for the whole order (#24) --}}
                            <div class="guest-note">
                                <label for="guest-order-note" class="guest-note__label">{{ __('guest.order_note_label') }}</label>
                                <textarea
                                    id="guest-order-note"
                                    wire:model="orderNote"
                                    maxlength="500"
                                    class="guest-note__field"
                                    placeholder="{{ __('guest.order_note_placeholder') }}"></textarea>
                            </div>

                            {{-- Summary: subtotal / tax / total ONLY (no service fee / VAT / voucher — scope #29) --}}
                            <div class="guest-summary">
                                <div class="guest-summary__row">
                                    <span>{{ __('guest.subtotal') }}</span>
                                    <span class="guest-summary__amt"><x-price :amount="$this->subtotal" :shop="$shop" /></span>
                                </div>
                                <div class="guest-summary__row">
                                    <span>{{ __('guest.tax') }}</span>
                                    <span class="guest-summary__amt"><x-price :amount="$this->tax" :shop="$shop" /></span>
                                </div>
                                <div class="guest-summary__row guest-summary__row--total">
                                    <span>{{ __('guest.total') }}</span>
                                    <span class="guest-summary__amt"><x-price :amount="$this->total" :shop="$shop" /></span>
                                </div>
                            </div>

                            <div class="guest-cart__hint">
                                <svg class="guest-cart__hint-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h.01M11 15h2M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z"/></svg>
                                <span>{{ __('guest.pay_at_counter_hint') }}</span>
                            </div>

                            {{-- Checkout contact (mockup screen 6): name + phone required --}}
                            <p class="guest-cart__section-title">{{ __('guest.confirm_your_order') }}</p>

                            <div class="guest-field">
                                <label for="guest-name" class="guest-field__label">{{ __('guest.your_name') }}</label>
                                <input id="guest-name" type="text" wire:model="customerName" maxlength="255" class="guest-field__input" placeholder="{{ __('guest.name_placeholder') }}" autocomplete="name">
                            </div>

                            <div class="guest-field">
                                <label for="guest-phone" class="guest-field__label">
                                    {{ __('guest.phone_label') }}
                                    <span class="guest-field__label-hint">· {{ __('guest.phone_hint') }}</span>
                                </label>
                                <input id="guest-phone" type="tel" wire:model="loyaltyPhone" wire:change.debounce.500ms="recognizeCustomer" class="guest-field__input" placeholder="{{ __('guest.loyalty_placeholder') }}" autocomplete="tel" inputmode="tel">
                                @if($loyaltyError)
                                    <div class="guest-cart__error">{{ $loyaltyError }}</div>
                                @endif

                                {{-- Welcome-back / order-your-usual (loyalty recognition preserved) --}}
                                @if($showWelcomeBack && is_array($recognizedCustomer))
                                    <div class="guest-cart__hint" style="margin-top:12px;color:var(--neutral-900)">
                                        <span>
                                            {{ __('guest.welcome_back') }}
                                            @if($recognizedCustomer['name'] ?? null){{ $recognizedCustomer['name'] }}@endif
                                            · {{ $recognizedCustomer['points'] ?? 0 }} {{ __('guest.points_label') }}
                                        </span>
                                    </div>
                                    <button wire:click="orderUsual" type="button" class="guest-addbtn guest-addbtn--soft">
                                        {{ __('guest.order_your_usual') }}
                                    </button>
                                @endif
                            </div>

                            {{-- Payment: pay at counter ONLY — no dropdown, no online payment --}}
                            <div class="guest-field">
                                <label class="guest-field__label">{{ __('guest.payment') }}</label>
                                <div class="guest-paysel">
                                    <span class="guest-paysel__ic">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/></svg>
                                    </span>
                                    <span class="guest-paysel__t">
                                        <b>{{ __('guest.pay_at_counter') }}</b>
                                        <small>{{ __('guest.pay_at_counter_desc') }}</small>
                                    </span>
                                    <span class="guest-paysel__tick">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="m5 13 4 4L19 7"/></svg>
                                    </span>
                                </div>
                            </div>

                            @if($orderError)
                                <div class="guest-cart__error">{{ $orderError }}</div>
                            @endif

                            <div class="guest-cart__hint">
                                <svg class="guest-cart__hint-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2Zm10-10V7a4 4 0 0 0-8 0v4h8Z"/></svg>
                                <span>{{ __('guest.place_order_hint') }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Action bar: cancel + place order (or just close when empty) --}}
                <div class="guest-actionbar">
                    @if($cartIsEmpty)
                        <button wire:click="toggleReview" type="button" class="guest-addbtn guest-addbtn--dark">{{ __('guest.cancel') }}</button>
                    @else
                        <button wire:click="toggleReview" type="button" class="guest-addbtn guest-addbtn--ghost">{{ __('guest.cancel') }}</button>
                        <button x-on:click="$dispatch('confirm-action', {
                                    title: '{{ __('guest.place_order') }}',
                                    message: '{{ $this->isGroupMode ? __('guest.send_group_to_kitchen') : __('guest.send_to_kitchen') }}',
                                    action: 'submitOrder',
                                    componentId: $wire.id,
                                    destructive: false,
                                })"
                                type="button"
                                class="guest-addbtn">
                            {{ __('guest.place_order') }}
                            <span class="guest-addbtn__price"><x-price :amount="$this->total" :shop="$shop" /></span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Group Share Modal --}}
    @if($showGroupShareModal && $this->isGroupMode)
        <div class="fixed inset-0 z-[100] flex items-end justify-center bg-ink/75 p-0 backdrop-blur-sm sm:items-center sm:p-6">
            <div class="surface-card flex w-full max-w-md flex-col overflow-hidden border-t sm:border sm:rounded-xl">
                <div class="flex items-center justify-between border-b border-line bg-muted/35 px-6 py-5 sm:px-8">
                    <div>
                        <h3 class="font-display text-2xl font-extrabold leading-none text-ink">{{ __('guest.share_group_order') }}</h3>
                        <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('guest.share_group_desc') }}</p>
                    </div>
                    <button wire:click="toggleGroupShare" class="rounded-md border border-line bg-panel p-2.5 text-ink-soft hover:border-ink hover:text-ink">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="space-y-4 p-6 sm:p-8">
                    <div class="rounded-lg border border-line bg-muted/30 px-4 py-3">
                        <p class="break-all font-mono text-xs font-semibold text-ink">{{ $this->groupShareUrl }}</p>
                    </div>

                    <button
                        x-data
                        x-on:click="
                            navigator.clipboard.writeText('{{ $this->groupShareUrl }}').then(() => {
                                $el.textContent = '{{ __('guest.link_copied') }}';
                                setTimeout(() => { $el.textContent = '{{ __('guest.copy_link') }}'; }, 2000);
                            });
                        "
                        class="btn-primary w-full justify-center"
                        type="button">
                        {{ __('guest.copy_link') }}
                    </button>

                    <p class="text-center font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">
                        {{ __('guest.group_expires_hint') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Product detail SHEET (mockup screen 4, #23). Re-skin of the modifier
         modal: image hero, name/price, choice chips per group, add-ons, a
         special-request note (allergen safety), and a sticky "Add to order"
         bar. Modifier min/max validation is unchanged — presentation only. --}}
    @if($showModifierModal && $customizingProduct)
        <div class="guest-sheet-backdrop">
            <div class="guest-sheet">
                <div class="guest-sheet__scroll">
                    {{-- Image hero with close --}}
                    <div class="guest-sheet__hero">
                        @if(productImage($customizingProduct, 'card'))
                            <img src="{{ productImage($customizingProduct, 'card') }}"
                                 alt="{{ $customizingProduct->translated('name') }}"
                                 class="guest-sheet__hero-img">
                        @else
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/>
                                <path d="M7 2v20"/>
                                <path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/>
                            </svg>
                        @endif
                        <button wire:click="$set('showModifierModal', false)" class="guest-sheet__close" type="button" aria-label="{{ __('guest.cancel') }}">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    @if($modifierError)
                        <div class="guest-sheet__error">{{ $modifierError }}</div>
                    @endif

                    @php
                        // Sale state for the metric chip — real data only (Product has
                        // no rating/calories/prep columns, so the prototype's fabricated
                        // metric chips are deliberately not rendered).
                        $sheetTimePriced = $pricingRules->isNotEmpty()
                            ? $customizingProduct->getTimePriced($pricingRules)
                            : null;
                        $sheetOnSale = $customizingProduct->is_on_sale
                            || ($sheetTimePriced !== null && $sheetTimePriced < $customizingProduct->final_price);
                    @endphp
                    <div class="guest-sheet__body guest-detail">
                        {{-- Title + live price (prototype .title-price) --}}
                        <div class="guest-detail__head">
                            <h3 class="guest-detail__name">{{ $customizingProduct->translated('name') }}</h3>
                            <strong class="guest-detail__price"><x-price :amount="$this->customizingProductPrice" :shop="$shop" /></strong>
                        </div>

                        {{-- Metric row (prototype .metric-row) — category + sale chips only --}}
                        <div class="guest-metric-row">
                            @if($customizingProduct->category)
                                <span class="guest-metric">{{ $customizingProduct->category->translated('name') }}</span>
                            @endif
                            @if($sheetOnSale)
                                <span class="guest-metric guest-metric--sale">{{ __('guest.on_sale') }}</span>
                            @endif
                        </div>

                        @if($customizingProduct->translated('description'))
                            <p class="guest-detail__desc">{{ $customizingProduct->translated('description') }}</p>
                        @endif

                        @foreach($customizingProduct->modifierGroups as $group)
                            <section class="guest-mgroup">
                                <div class="guest-mgroup__head">
                                    <h4 class="guest-mgroup__title">{{ $group->translated('name') }}</h4>
                                    @if($group->min_selection > 0)
                                        <span class="guest-mgroup__req">{{ __('guest.required') }}</span>
                                    @else
                                        <span class="guest-mgroup__req guest-mgroup__req--optional">{{ __('guest.optional') }}</span>
                                    @endif
                                    @if($group->max_selection > 1)
                                        <span class="guest-mgroup__opt">{{ __('guest.up_to', ['count' => $group->max_selection]) }}</span>
                                    @endif
                                </div>

                                {{-- Choice-row chips (prototype .choice-row). Buttons toggle the
                                     same selectModifier() flow as before — presentation only; the
                                     min/max validation in addToCart() is unchanged. --}}
                                <div class="guest-choice-row" role="group" aria-label="{{ $group->translated('name') }}">
                                    @foreach($group->options as $option)
                                        @php
                                            $isChecked = $group->max_selection == 1
                                                ? (($selectedModifiers[$group->id] ?? null) == $option->id)
                                                : in_array((string) $option->id, (array) ($selectedModifiers[$group->id] ?? []));
                                        @endphp
                                        <button
                                            type="button"
                                            wire:click="selectModifier({{ $group->id }}, {{ $option->id }}, {{ $group->max_selection > 1 ? 'true' : 'false' }})"
                                            class="guest-choice {{ $isChecked ? 'guest-choice--on' : '' }}"
                                            aria-pressed="{{ $isChecked ? 'true' : 'false' }}"
                                        >
                                            <span class="guest-choice__name">{{ $option->translated('name') }}</span>
                                            @if($option->price_adjustment > 0)
                                                <span class="guest-choice__price">+<x-price :amount="$option->price_adjustment" :shop="$shop" /></span>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach

                        {{-- Special request / allergen note (pilot safety feature) --}}
                        <label class="guest-note" for="guest-item-note">
                            <span class="guest-note__label">{{ __('guest.item_note_label') }}</span>
                            <textarea
                                id="guest-item-note"
                                wire:model="itemNote"
                                maxlength="255"
                                class="guest-note__field"
                                placeholder="{{ __('guest.item_note_placeholder') }}"></textarea>
                        </label>
                    </div>
                </div>

                <div class="guest-actionbar">
                    <button wire:click="addToCart({{ $customizingProduct->id }})"
                            wire:loading.attr="disabled"
                            wire:target="addToCart({{ $customizingProduct->id }})"
                            class="guest-addbtn"
                            type="button">
                        <span wire:loading.remove wire:target="addToCart({{ $customizingProduct->id }})">{{ __('guest.add_to_order') }}</span>
                        <span wire:loading.remove wire:target="addToCart({{ $customizingProduct->id }})" class="guest-addbtn__price"><x-price :amount="$this->customizingProductPrice" :shop="$shop" /></span>
                        <span wire:loading wire:target="addToCart({{ $customizingProduct->id }})" class="loading-spinner"></span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Powered by Bite — guest experience footer --}}
    <footer class="guest-powered guest-powered--page">{{ __('guest.powered_by') }} <b>Bite</b></footer>
</div>

{{-- Flip <html dir>/lang immediately on language switch. SetLocale middleware
     only runs on full page loads; Livewire updates are AJAX partials, so the
     gate's language pick would otherwise show Arabic text in an LTR layout
     until the next reload. --}}
@script
<script>
    $wire.on('guest-locale-changed', ({ direction }) => {
        const dir = direction === 'rtl' ? 'rtl' : 'ltr';
        document.documentElement.setAttribute('dir', dir);
        document.documentElement.setAttribute('lang', dir === 'rtl' ? 'ar' : 'en');
    });
</script>
@endscript
