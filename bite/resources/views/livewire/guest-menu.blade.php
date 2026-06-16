@php
    $fallbackImages = [
        'customer-ordering/assets/hopresso/americano.png',
        'customer-ordering/assets/hopresso/coffee-latte-top.png',
        'customer-ordering/assets/hopresso/iced-latte.png',
        'customer-ordering/assets/hopresso/caramel-cream.png',
        'customer-ordering/assets/hopresso/sparkling-tea.png',
        'customer-ordering/assets/hopresso/cup-togo.png',
    ];

    $highlight = $popularProducts->first();
    $highlightPrice = null;
    if ($highlight) {
        $highlightTimePrice = $pricingRules->isNotEmpty() ? $highlight->getTimePriced($pricingRules) : null;
        $highlightPrice = $highlightTimePrice !== null && $highlightTimePrice < $highlight->final_price
            ? $highlightTimePrice
            : ($highlight->is_on_sale ? $highlight->final_price : $highlight->price);
    }

    $contextCopy = $tableLabel
        ? __('guest.table_context', ['table' => $tableLabel])
        : __('guest.dine_in');

    $textLanguageAttrs = function ($value) {
        return preg_match('/\p{Arabic}/u', (string) $value)
            ? 'lang="ar" dir="rtl"'
            : 'lang="en" dir="ltr"';
    };

    $prototypeIcon = function ($name) {
        $paths = [
            'location' => '<path d="M12 21s7-5.1 7-11a7 7 0 1 0-14 0c0 5.9 7 11 7 11Z"/><circle cx="12" cy="10" r="2.4"/>',
            'pickup' => '<path d="M7 3h10l1 5H6l1-5Z"/><path d="M6 8h12v10a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V8Z"/><path d="M9 12h6"/>',
            'card' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/>',
            'plus' => '<path d="M12 5v14M5 12h14"/>',
        ];

        return new \Illuminate\Support\HtmlString(
            '<svg viewBox="0 0 24 24" aria-hidden="true">'.($paths[$name] ?? $paths['plus']).'</svg>'
        );
    };
@endphp

<div class="bite-ordering-stage" @if($this->isGroupMode) wire:poll.3s @endif>
    @if($showLanguageGate)
        @include('livewire.partials.guest-gate')
    @endif

    <div class="bite-phone-shell phone-shell">
        <section
            class="screen bite-menu-screen web-screen {{ $screen === 'full_menu' ? 'order-screen bite-full-menu-screen' : 'home-screen' }}"
            data-route-name="{{ $screen === 'full_menu' ? 'order' : 'home' }}"
            data-figma-screen="{{ $screen === 'full_menu' ? '29' : '25' }}"
            lang="{{ $locale }}"
            dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}"
        >
            @if($screen === 'home')
                @include('livewire.partials.guest-hero')
            @else
                <header class="bite-full-menu-top">
                    <div class="status-bar bite-status-bar" aria-hidden="true">
                        <strong lang="en" dir="ltr">9:41</strong>
                        <span class="status-icons bite-status-bar__icons" lang="en" dir="ltr">
                            <svg viewBox="0 0 24 24"><path d="M4 20h2v-5H4v5Zm4 0h2v-8H8v8Zm4 0h2V9h-2v11Zm4 0h2V6h-2v14Z"/></svg>
                            <svg viewBox="0 0 24 24"><path d="M3 8.8c5.8-5 12.2-5 18 0M7 13c3.3-2.6 6.7-2.6 10 0m-6 4.2a2 2 0 0 1 2 0"/></svg>
                            <span class="battery"></span>
                        </span>
                    </div>
                    <div class="bite-full-menu-nav">
                        <button wire:click="showHome" type="button" class="bite-back-btn" aria-label="{{ __('guest.back_to_menu') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="m15 18-6-6 6-6"/>
                            </svg>
                            <span class="sr-only">{{ __('guest.back_to_menu') }}</span>
                        </button>
                        <div>
                            <p><span {!! $textLanguageAttrs($shop->name) !!}>{{ $shop->name }}</span> - {{ $contextCopy }}</p>
                            <h1>{{ __('guest.full_menu') }}</h1>
                        </div>
                    </div>
                </header>
            @endif

            @if($this->isGroupMode)
                <div class="bite-group-banner">
                    <span>{{ __('guest.group_banner', ['count' => count(array_unique(array_column($groupCartItems, 'participant_id')))]) }}</span>
                    <div>
                        <button wire:click="toggleGroupShare" type="button">{{ __('guest.share_link') }}</button>
                        <button wire:click="leaveGroup" type="button">{{ __('guest.leave_group') }}</button>
                    </div>
                </div>
            @endif

            <main
                class="web-main bite-menu-main"
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
                @if($screen === 'home')
                    <div class="web-context-strip bite-context-strip">
                        <div>
                            {!! $prototypeIcon('pickup') !!}
                            <span>{{ __('guest.average_time') }}</span>
                        </div>
                        <div>
                            {!! $prototypeIcon('card') !!}
                            <span>{{ __('guest.pay_at_counter') }}</span>
                        </div>
                    </div>

                    @if($highlight)
                        @php
                            $highlightImage = productImage($highlight, 'card') ?: asset('customer-ordering/assets/hopresso/iced-latte.png');
                        @endphp
                        <section class="offers-section bite-offers" x-show="query === '' && activeCategory === 'all'">
                            <h2>{{ __('guest.todays_highlight') }}</h2>
                            <article
                                class="offer-card highlight-card bite-highlight"
                                wire:click="openProductSheet({{ $highlight->id }})"
                            >
                                <div>
                                    <span class="highlight-kicker bite-kicker">{{ __('guest.owners_pick') }}</span>
                                    <h3>
                                        {{ $highlight->translated('name') }}
                                        @if($highlightPrice !== null)
                                            <span><x-price :amount="$highlightPrice" :shop="$shop" /></span>
                                        @endif
                                    </h3>
                                <p>{{ Str::limit($highlight->translated('description') ?: __('guest.guest_experience'), 80) }}</p>
                                <button wire:click.stop="openProductSheet({{ $highlight->id }})" type="button">
                                    {{ __('guest.view_item') }} &rarr;
                                </button>
                                </div>
                                <img src="{{ $highlightImage }}" alt="{{ $highlight->translated('name') }}" loading="lazy">
                            </article>
                        </section>
                    @endif

                    @include('livewire.partials.guest-popular-rail')
                @else
                <div class="bite-pinbar">
                    <label class="guest-search bite-search">
                        <svg class="bite-search__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>
                        </svg>
                        <input
                            type="search"
                            x-model="query"
                            placeholder="{{ __('guest.search_menu') }}"
                            aria-label="{{ __('guest.search_menu') }}"
                        >
                    </label>

                    @if($categories->isNotEmpty())
                        <div class="guest-tabs bite-tabs" role="tablist">
                            <button
                                type="button"
                                @click="activeCategory = 'all'"
                                :class="{ 'is-active': activeCategory === 'all' }"
                            >{{ __('guest.category_all') }}</button>
                            @foreach($categories as $category)
                                <button
                                    type="button"
                                    @click="activeCategory = '{{ $category->id }}'"
                                    :class="{ 'is-active': activeCategory === '{{ $category->id }}' }"
                                >{{ $category->translated('name') }}</button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <p class="bite-noresults" x-show="query.trim() !== '' && !hasMatches" x-cloak>
                    {{ __('guest.no_search_results') }}
                </p>

                <div id="bite-full-menu" class="bite-full-menu" wire:loading.remove wire:target="switchLanguage">
                    @forelse($categories as $category)
                        @php
                            $categoryNames = $category->products
                                ->map(fn ($p) => $searchNames[$p->id] ?? '')
                                ->values()
                                ->all();
                        @endphp
                        <section
                            class="bite-category"
                            data-category-section="{{ $category->id }}"
                            x-data="{ names: @js($categoryNames) }"
                            x-show="(activeCategory === 'all' || activeCategory === '{{ $category->id }}')
                                && (query.trim() === '' || names.some(n => n.includes(query.toLowerCase().trim())))"
                        >
                            <h3 class="menu-category-header bite-category__title">{{ $category->translated('name') }}</h3>
                            <div class="bite-menu-list bite-menu-card-grid">
                                @foreach($category->products as $product)
                                    @php
                                        $timePricedAmount = $pricingRules->isNotEmpty()
                                            ? $product->getTimePriced($pricingRules)
                                            : null;
                                        $hasTimeDiscount = $timePricedAmount !== null && $timePricedAmount < $product->final_price;
                                        $displayPrice = $hasTimeDiscount ? $timePricedAmount : ($product->is_on_sale ? $product->final_price : $product->price);
                                        $searchName = $searchNames[$product->id] ?? '';
                                        $fallback = $fallbackImages[$loop->index % count($fallbackImages)];
                                        $imageUrl = productImage($product, 'card') ?: asset($fallback);
                                    @endphp
                                    <article
                                        class="product-card bite-popular-card bite-menu-card{{ ! $product->is_available ? ' is-sold-out' : '' }}"
                                        data-product
                                        data-name="{{ $searchName }}"
                                        x-bind:hidden="query.trim() !== '' && !$el.dataset.name.includes(query.toLowerCase().trim())"
                                        wire:key="product-{{ $product->id }}"
                                    >
                                        <button
                                            type="button"
                                            class="product-open bite-popular-card__image"
                                            @if($product->is_available)
                                                wire:click="openProductSheet({{ $product->id }})"
                                                aria-label="{{ __('guest.view_details_aria', ['name' => $product->translated('name')]) }}"
                                            @else
                                                disabled
                                                aria-label="{{ __('guest.sold_out') }}"
                                            @endif
                                        >
                                            <img src="{{ $imageUrl }}" alt="{{ $product->translated('name') }}" loading="lazy">
                                            @if(! $product->is_available)
                                                <span class="bite-popular-card__badge bite-popular-card__badge--muted">{{ __('guest.sold_out') }}</span>
                                            @elseif($product->is_on_sale)
                                                <span class="bite-popular-card__badge">{{ __('guest.flash_sale') }}</span>
                                            @elseif($hasTimeDiscount)
                                                <span class="bite-popular-card__badge">{{ __('guest.limited_offer') }}</span>
                                            @endif
                                            <h3>{{ $product->translated('name') }}</h3>
                                            <p>{{ Str::limit($product->translated('description') ?: __('guest.guest_experience'), 42) }}</p>
                                            <strong><x-price :amount="$displayPrice" :shop="$shop" /></strong>
                                        </button>

                                        @if($product->is_available)
                                            <button
                                                wire:click.stop="addToCart({{ $product->id }})"
                                                class="mini-plus bite-add-mini"
                                                type="button"
                                                aria-label="{{ __('guest.add_item_aria', ['name' => $product->translated('name')]) }}"
                                            >{!! $prototypeIcon('plus') !!}</button>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @empty
                        <section class="bite-empty">
                            <p>{{ __('guest.no_items_available') }}</p>
                            <span>{{ __('guest.no_items_hint') }}</span>
                        </section>
                    @endforelse
                </div>
                @endif

                <div wire:loading wire:target="switchLanguage" class="bite-loading">
                    <span class="loading-spinner"></span>
                </div>
            </main>

            @php
                $cartLines = $this->isGroupMode ? $groupCartItems : $cart;
                $hasCartLines = count($cartLines) > 0;
            @endphp

            @if($hasCartLines)
                <div class="bite-cart-bar">
                    <button wire:click="toggleReview" type="button">
                        <span>
                            <b>{{ $this->cartItemCount }}</b>
                            {{ $this->isGroupMode ? __('guest.review_group_order') : __('guest.review_order') }}
                        </span>
                        <strong><x-price :amount="$this->total" :shop="$shop" /></strong>
                    </button>
                </div>
            @endif

            <footer class="powered-by-bite bite-powered bite-powered--page" aria-label="{{ __('guest.powered_by') }} Bite">
                <span>{{ __('guest.powered_by') }}</span>
                <img src="{{ asset('customer-ordering/assets/brand/bite-powered-logo.png') }}" alt="Bite">
            </footer>
        </section>
    </div>

    @if($showReviewModal)
        @php
            $cartIsEmpty = $this->isGroupMode ? empty($groupCartItems) : empty($cart);
        @endphp
        <div class="guest-sheet-backdrop bite-sheet-backdrop">
            <div class="guest-sheet bite-sheet bite-sheet--cart">
                <div class="bite-sheet__header">
                    <div>
                        <h3>{{ $this->isGroupMode ? __('guest.group_order') : __('guest.your_order') }}</h3>
                        <p><span {!! $textLanguageAttrs($shop->name) !!}>{{ $shop->name }}</span> - {{ $contextCopy }}</p>
                    </div>
                    <button wire:click="toggleReview" type="button" aria-label="{{ __('guest.cancel') }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="guest-sheet__scroll bite-sheet__scroll">
                    @if($cartIsEmpty)
                        <div class="bite-empty bite-empty--sheet">
                            <p>{{ __('guest.cart_empty_title') }}</p>
                            <span>{{ __('guest.cart_empty_body') }}</span>
                            <button wire:click="toggleReview" type="button" class="bite-primary-btn">{{ __('guest.browse_menu') }}</button>
                        </div>
                    @else
                        <div class="bite-cart-context">{{ __('guest.counter_pickup_hint') }}</div>

                        @if($this->isGroupMode)
                            @php $groupedByParticipant = collect($groupCartItems)->groupBy('participant_id'); @endphp
                            @foreach($groupedByParticipant as $pid => $items)
                                <p class="bite-cart-participant">
                                    {{ $pid === $participantId ? __('guest.you') : __('guest.participant') }}
                                </p>
                                @foreach($items as $item)
                                    <div class="bite-cart-line">
                                        <div>
                                            <h4>{{ $item['name'] }}</h4>
                                            @if(!empty($item['modifierNames']))
                                                <p>{{ implode(' - ', $item['modifierNames']) }}</p>
                                            @endif
                                            @if(filled($item['note'] ?? null))
                                                <p>{{ $item['note'] }}</p>
                                            @endif
                                            <strong><x-price :amount="($item['price'] ?? 0) * ($item['quantity'] ?? 1)" :shop="$shop" /></strong>
                                        </div>
                                        @if($pid === $participantId)
                                            <div class="bite-qty">
                                                <button wire:click="decrementItem('{{ $item['itemKey'] ?? '' }}')" type="button" aria-label="{{ __('guest.decrease_qty') }}">-</button>
                                                <span>{{ $item['quantity'] }}</span>
                                                <button wire:click="incrementItem('{{ $item['itemKey'] ?? '' }}')" type="button" aria-label="{{ __('guest.increase_qty') }}">+</button>
                                            </div>
                                            <button wire:click="removeItem('{{ $item['itemKey'] ?? '' }}')" class="bite-remove" type="button" aria-label="{{ __('guest.remove_item') }}">x</button>
                                        @else
                                            <span class="bite-readonly-qty">{{ $item['quantity'] }}x</span>
                                        @endif
                                    </div>
                                @endforeach
                            @endforeach
                        @else
                            @foreach($cart as $key => $item)
                                <div class="bite-cart-line">
                                    <div>
                                        <h4>{{ $item['name'] }}</h4>
                                        @if(!empty($item['modifierNames']))
                                            <p>{{ implode(' - ', $item['modifierNames']) }}</p>
                                        @endif
                                        @if(filled($item['note'] ?? null))
                                            <p>{{ $item['note'] }}</p>
                                        @endif
                                        <strong><x-price :amount="$item['price'] * $item['quantity']" :shop="$shop" /></strong>
                                    </div>
                                    <div class="bite-qty">
                                        <button wire:click="decrementItem('{{ $key }}')" type="button" aria-label="{{ __('guest.decrease_qty') }}">-</button>
                                        <span>{{ $item['quantity'] }}</span>
                                        <button wire:click="incrementItem('{{ $key }}')" type="button" aria-label="{{ __('guest.increase_qty') }}">+</button>
                                    </div>
                                    <button wire:click="removeItem('{{ $key }}')" class="bite-remove" type="button" aria-label="{{ __('guest.remove_item') }}">x</button>
                                </div>
                            @endforeach
                        @endif

                        <label class="bite-field">
                            <span>{{ __('guest.order_note_label') }}</span>
                            <textarea wire:model="orderNote" maxlength="500" placeholder="{{ __('guest.order_note_placeholder') }}"></textarea>
                        </label>

                        <div class="bite-payment-card">
                            <label class="bite-payment-select">
                                <span>{{ __('guest.payment_method_label') }}</span>
                                <select wire:model.live="paymentMethod" aria-label="{{ __('guest.payment_method_label') }}">
                                    <option value="counter">{{ __('guest.payment_method_counter') }}</option>
                                    <option value="online">{{ __('guest.payment_method_online') }}</option>
                                </select>
                            </label>
                        </div>

                        <section class="bite-checkout-section bite-voucher-section">
                            <h4>{{ __('guest.voucher') }}</h4>
                            <div class="bite-voucher-field{{ $voucherApplied ? ' is-applied' : '' }}">
                                <span class="bite-voucher-field__icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24"><path d="M12 21s-7-4.7-9.2-8.6C.8 8.7 2.8 5 6.5 5c2 0 3.5 1 4.4 2.3C11.8 6 13.4 5 15.5 5c3.7 0 5.7 3.7 3.7 7.4C17 16.3 12 21 12 21Z"/></svg>
                                </span>
                                <label>
                                    <span>{{ __('guest.promo_code') }}</span>
                                    <input
                                        type="text"
                                        wire:model.live.debounce.250ms="voucherCode"
                                        maxlength="40"
                                        placeholder="{{ __('guest.voucher_placeholder') }}"
                                        autocomplete="off"
                                        autocapitalize="characters"
                                    >
                                </label>
                                <button wire:click="applyVoucher" type="button">
                                    {{ $voucherApplied ? __('guest.voucher_applied_button') : __('guest.apply_voucher') }}
                                </button>
                            </div>
                            @if($voucherApplied)
                                <p class="bite-voucher-feedback">{{ __('guest.voucher_applied', ['code' => $voucherCode]) }}</p>
                            @endif
                        </section>

                        <div class="bite-summary">
                            <div><span>{{ __('guest.subtotal') }}</span><strong><x-price :amount="$this->subtotal" :shop="$shop" /></strong></div>
                            <div><span>{{ __('guest.tax') }}</span><strong><x-price :amount="$this->tax" :shop="$shop" /></strong></div>
                            <div><span>{{ __('guest.total') }}</span><strong><x-price :amount="$this->total" :shop="$shop" /></strong></div>
                        </div>

                        <p class="bite-sheet-section">{{ __('guest.confirm_your_order') }}</p>

                        <label class="bite-field">
                            <span>{{ __('guest.your_name') }}</span>
                            <input type="text" wire:model="customerName" maxlength="255" placeholder="{{ __('guest.name_placeholder') }}" autocomplete="name">
                        </label>

                        <label class="bite-field">
                            <span>{{ __('guest.phone_label') }} <small>{{ __('guest.phone_hint') }}</small></span>
                            <input type="tel" wire:model="loyaltyPhone" wire:change.debounce.500ms="recognizeCustomer" placeholder="{{ __('guest.loyalty_placeholder') }}" autocomplete="tel" inputmode="tel">
                        </label>

                        @if($loyaltyError)
                            <div class="bite-error">{{ $loyaltyError }}</div>
                        @endif

                        @if($showWelcomeBack && is_array($recognizedCustomer))
                            <div class="bite-welcome-back">
                                <span>
                                    {{ __('guest.welcome_back') }}
                                    @if($recognizedCustomer['name'] ?? null){{ $recognizedCustomer['name'] }}@endif
                                    - {{ $recognizedCustomer['points'] ?? 0 }} {{ __('guest.points_label') }}
                                </span>
                                <button wire:click="orderUsual" type="button">{{ __('guest.order_your_usual') }}</button>
                            </div>
                        @endif

                        @if($orderError)
                            <div class="bite-error">{{ $orderError }}</div>
                        @endif
                    @endif
                </div>

                <div class="guest-actionbar bite-actionbar">
                    @if($cartIsEmpty)
                        <button wire:click="toggleReview" type="button" class="bite-secondary-btn">{{ __('guest.cancel') }}</button>
                    @else
                        <button wire:click="toggleReview" type="button" class="bite-secondary-btn">{{ __('guest.cancel') }}</button>
                        <button
                            wire:click="submitOrder"
                            wire:loading.attr="disabled"
                            wire:target="submitOrder"
                            type="button"
                            class="bite-primary-btn"
                        >
                            <span wire:loading.remove wire:target="submitOrder">{{ __('guest.place_order') }}</span>
                            <span wire:loading wire:target="submitOrder" class="loading-spinner"></span>
                            <strong><x-price :amount="$this->total" :shop="$shop" /></strong>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if($showGroupShareModal && $this->isGroupMode)
        <div class="guest-sheet-backdrop bite-sheet-backdrop">
            <div class="bite-share-modal">
                <div class="bite-sheet__header">
                    <div>
                        <h3>{{ __('guest.share_group_order') }}</h3>
                        <p>{{ __('guest.share_group_desc') }}</p>
                    </div>
                    <button wire:click="toggleGroupShare" type="button" aria-label="{{ __('guest.cancel') }}">x</button>
                </div>
                <p class="bite-share-url">{{ $this->groupShareUrl }}</p>
                <button
                    x-data
                    x-on:click="
                        navigator.clipboard.writeText('{{ $this->groupShareUrl }}').then(() => {
                            $el.textContent = '{{ __('guest.link_copied') }}';
                            setTimeout(() => { $el.textContent = '{{ __('guest.copy_link') }}'; }, 2000);
                        });
                    "
                    class="bite-primary-btn"
                    type="button"
                >
                    {{ __('guest.copy_link') }}
                </button>
                <small>{{ __('guest.group_expires_hint') }}</small>
            </div>
        </div>
    @endif

    @if($showModifierModal && $customizingProduct)
        @php
            $detailImage = productImage($customizingProduct, 'card') ?: asset('customer-ordering/assets/hopresso/creamy-latte.png');
        @endphp
        <div class="guest-sheet-backdrop bite-sheet-backdrop">
            <div class="guest-sheet bite-sheet">
                <div class="guest-sheet__scroll bite-sheet__scroll">
                    <div class="bite-detail-hero">
                        <img src="{{ $detailImage }}" alt="{{ $customizingProduct->translated('name') }}">
                        <button wire:click="$set('showModifierModal', false)" type="button" aria-label="{{ __('guest.cancel') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    @if($modifierError)
                        <div class="bite-error">{{ $modifierError }}</div>
                    @endif

                    <div class="bite-detail-body">
                        <div class="bite-detail-title">
                            <div>
                                <h3>{{ $customizingProduct->translated('name') }}</h3>
                                @if($customizingProduct->translated('description'))
                                    <p>{{ $customizingProduct->translated('description') }}</p>
                                @endif
                            </div>
                            <strong><x-price :amount="$this->customizingProductPrice" :shop="$shop" /></strong>
                        </div>

                        @foreach($customizingProduct->modifierGroups as $group)
                            <section class="bite-modifier-group">
                                <header>
                                    <h4>{{ $group->translated('name') }}</h4>
                                    <span>{{ $group->min_selection > 0 ? __('guest.required') : __('guest.optional') }}</span>
                                </header>

                                @foreach($group->options as $option)
                                    @php
                                        $isChecked = $group->max_selection == 1
                                            ? (($selectedModifiers[$group->id] ?? null) == $option->id)
                                            : in_array((string) $option->id, (array) ($selectedModifiers[$group->id] ?? []));
                                    @endphp
                                    <label class="bite-option">
                                        <input
                                            type="{{ $group->max_selection == 1 ? 'radio' : 'checkbox' }}"
                                            value="{{ $option->id }}"
                                            wire:click="selectModifier({{ $group->id }}, {{ $option->id }}, {{ $group->max_selection > 1 ? 'true' : 'false' }})"
                                            name="group_{{ $group->id }}"
                                            @checked($isChecked)
                                        >
                                        <span>{{ $option->translated('name') }}</span>
                                        <strong>
                                            @if($option->price_adjustment > 0)
                                                +<x-price :amount="$option->price_adjustment" :shop="$shop" />
                                            @else
                                                {{ __('guest.included') }}
                                            @endif
                                        </strong>
                                    </label>
                                @endforeach
                            </section>
                        @endforeach

                        <label class="bite-field">
                            <span>{{ __('guest.item_note_label') }}</span>
                            <textarea wire:model="itemNote" maxlength="255" placeholder="{{ __('guest.item_note_placeholder') }}"></textarea>
                        </label>
                    </div>
                </div>

                <div class="guest-actionbar bite-actionbar">
                    <button
                        wire:click="addToCart({{ $customizingProduct->id }})"
                        wire:loading.attr="disabled"
                        wire:target="addToCart({{ $customizingProduct->id }})"
                        class="bite-primary-btn"
                        type="button"
                    >
                        <span wire:loading.remove wire:target="addToCart({{ $customizingProduct->id }})">{{ __('guest.add_to_order') }}</span>
                        <span wire:loading wire:target="addToCart({{ $customizingProduct->id }})" class="loading-spinner"></span>
                        <strong><x-price :amount="$this->customizingProductPrice" :shop="$shop" /></strong>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

@script
<script>
    $wire.on('guest-locale-changed', ({ direction }) => {
        const dir = direction === 'rtl' ? 'rtl' : 'ltr';
        document.documentElement.setAttribute('dir', dir);
        document.documentElement.setAttribute('lang', dir === 'rtl' ? 'ar' : 'en');
    });

    $wire.on('guest-screen-changed', ({ screen }) => {
        const url = new URL(window.location.href);
        if (screen === 'full_menu') {
            url.searchParams.set('view', 'menu');
        } else {
            url.searchParams.delete('view');
        }
        window.history.pushState({}, '', url);
    });
</script>
@endscript
