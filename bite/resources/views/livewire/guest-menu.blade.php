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
            'check' => '<path d="m5 12 4 4L19 6"/>',
            'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
            'plus' => '<path d="M12 5v14M5 12h14"/>',
        ];

        return new \Illuminate\Support\HtmlString(
            '<svg viewBox="0 0 24 24" aria-hidden="true">'.($paths[$name] ?? $paths['plus']).'</svg>'
        );
    };

    $checkoutVisible = $screen === 'checkout' || $showReviewModal;
    $productVisible = ($screen === 'product' || $showModifierModal) && $customizingProduct;
    $activeScreen = $checkoutVisible ? 'checkout' : ($productVisible ? 'product' : $screen);
    $screenClass = match ($activeScreen) {
        'full_menu' => 'order-screen bite-full-menu-screen',
        'checkout' => 'bite-checkout-screen',
        'product' => 'bite-product-screen',
        default => 'home-screen',
    };
    $routeName = match ($activeScreen) {
        'full_menu' => 'order',
        'checkout' => 'checkout',
        'product' => 'product',
        default => 'home',
    };
    $figmaScreen = match ($activeScreen) {
        'full_menu' => '29',
        'checkout' => 'checkout',
        'product' => 'product',
        default => '25',
    };
@endphp

<div class="bite-ordering-stage" @if($this->isGroupMode) wire:poll.3s @endif>
    @if($showLanguageGate)
        @include('livewire.partials.guest-gate')
    @endif

    <div class="bite-phone-shell phone-shell">
        <section
            class="screen bite-menu-screen web-screen {{ $screenClass }}"
            data-route-name="{{ $routeName }}"
            data-figma-screen="{{ $figmaScreen }}"
            lang="{{ $locale }}"
            dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}"
        >
            @if($activeScreen === 'home')
                @include('livewire.partials.guest-hero')
            @elseif($activeScreen === 'product')
            @elseif($activeScreen === 'checkout')
                <header class="bite-cart-top">
                    <div class="bite-cart-nav">
                        <button wire:click="closeCheckout" type="button" class="bite-cart-back" aria-label="{{ __('guest.back_to_menu') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="m15 18-6-6 6-6"/>
                            </svg>
                            <span class="sr-only">{{ __('guest.back_to_menu') }}</span>
                        </button>
                        <div>
                            <h1>{{ __('guest.your_cart') }}</h1>
                            <p>{{ $contextCopy }}</p>
                        </div>
                        <button
                            wire:click="switchLanguage('{{ $locale === 'ar' ? 'en' : 'ar' }}')"
                            type="button"
                            class="bite-cart-lang"
                            lang="en"
                            dir="ltr"
                        >{{ $locale === 'ar' ? 'EN' : 'AR' }}</button>
                    </div>
                </header>
            @else
                <header class="bite-full-menu-top">
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
                class="web-main bite-menu-main {{ $activeScreen === 'checkout' ? 'bite-checkout-main' : '' }} {{ $activeScreen === 'product' ? 'bite-product-main' : '' }}"
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
                @if($activeScreen === 'product')
                    @include('livewire.partials.guest-product-detail')
                @elseif($activeScreen === 'checkout')
                    @include('livewire.partials.guest-checkout')
                @elseif($activeScreen === 'home')
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
                                wire:click="openProductPage({{ $highlight->id }})"
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
                                <button wire:click.stop="openProductPage({{ $highlight->id }})" type="button">
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
                                                wire:click="openProductPage({{ $product->id }})"
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

            @if($hasCartLines && ! in_array($activeScreen, ['checkout', 'product'], true))
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

            @if(! in_array($activeScreen, ['checkout', 'product'], true))
                <footer class="powered-by-bite bite-powered bite-powered--page" aria-label="{{ __('guest.powered_by') }} Bite">
                    <span>{{ __('guest.powered_by') }}</span>
                    <img src="{{ asset('customer-ordering/assets/brand/bite-powered-logo.png') }}" alt="Bite">
                </footer>
            @endif
        </section>
    </div>

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

</div>

@script
<script>
    $wire.on('guest-locale-changed', ({ direction }) => {
        const dir = direction === 'rtl' ? 'rtl' : 'ltr';
        document.documentElement.setAttribute('dir', dir);
        document.documentElement.setAttribute('lang', dir === 'rtl' ? 'ar' : 'en');
    });

    $wire.on('guest-screen-changed', ({ screen, productId }) => {
        const url = new URL(window.location.href);
        if (screen === 'full_menu') {
            url.searchParams.set('view', 'menu');
            url.searchParams.delete('product');
        } else if (screen === 'checkout') {
            url.searchParams.set('view', 'checkout');
            url.searchParams.delete('product');
        } else if (screen === 'product') {
            url.searchParams.set('view', 'product');
            if (productId) {
                url.searchParams.set('product', productId);
            }
        } else {
            url.searchParams.delete('view');
            url.searchParams.delete('product');
        }
        window.history.pushState({}, '', url);
    });
</script>
@endscript
