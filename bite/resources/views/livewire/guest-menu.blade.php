<div class="guest-menu-bg relative flex min-h-full flex-col overflow-x-hidden"
     @if($this->isGroupMode) wire:poll.3s @endif>

    {{-- Language gate (mockup screen 1) — full-screen, blocks menu until a language is picked --}}
    @if($showLanguageGate)
        @include('livewire.partials.guest-gate')
    @endif

    <header
        class="guest-header"
        x-data
        x-init="
            const sync = () => document.documentElement.style.setProperty('--guest-header-h', $el.offsetHeight + 'px');
            sync();
            const ro = new ResizeObserver(sync);
            ro.observe($el);
            $cleanup(() => ro.disconnect());
        "
    >
        <div class="guest-header__inner">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg border border-line bg-ink text-panel font-display text-xl font-black">B</div>
                <div>
                    <h1 class="font-display text-2xl font-extrabold leading-none text-ink">{{ $shop->name }}</h1>
                    <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.18em] text-ink-soft">
                        @if($this->isGroupMode)
                            {{ __('guest.group_ordering') }}
                        @else
                            {{ __('guest.guest_ordering') }}
                        @endif
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                {{-- Group Order Button --}}
                @if($this->isGroupMode)
                    <button wire:click="toggleGroupShare" class="inline-flex items-center gap-1.5 rounded-full border border-crema/40 bg-crema/10 px-3 py-1.5 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-crema transition-colors hover:bg-crema/20" type="button">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        {{ __('guest.group_active') }}
                    </button>
                @else
                    <button wire:click="createGroup" class="inline-flex items-center gap-1.5 rounded-full border border-line bg-panel px-3 py-1.5 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft transition-colors hover:border-ink hover:text-ink" type="button">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        {{ __('guest.group_order') }}
                    </button>
                @endif

                {{-- Language Toggle --}}
                <div class="flex items-center gap-0.5 rounded-full border border-line bg-panel p-0.5">
                    <button wire:click="switchLanguage('en')" class="lang-toggle {{ $locale === 'en' ? 'lang-toggle-active' : '' }}" type="button">
                        EN
                    </button>
                    <button wire:click="switchLanguage('ar')" class="lang-toggle {{ $locale === 'ar' ? 'lang-toggle-active' : '' }}" type="button">
                        عربي
                    </button>
                </div>
            </div>
        </div>
    </header>

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

    {{-- Hero shell (mockup screens 2 / 2b) — cover, logo, name, open pill, dine-in chip --}}
    @include('livewire.partials.guest-hero')

    {{-- Browse skin (mockup screens 2b / 3): sticky search + category tabs, then
         the Popular rail and the full category list. Search and tab filtering are
         client-side over the already-rendered, server-validated menu — no extra
         Livewire round-trips. Sale / limited-offer / sold-out states and the three
         themes are preserved by the existing card markup below. --}}
    <main
        x-data="{
            query: '',
            activeCategory: 'all',
            allNames: @js(array_values($searchNames)),
            get hasMatches() {
                const q = this.query.toLowerCase().trim();
                return q === '' || this.allNames.some(n => n.includes(q));
            },
        }"
        class="mx-auto w-full max-w-6xl flex-1 px-4 pb-32 sm:px-6"
    >
        {{-- Pinned bar: search + horizontal category tabs --}}
        <div class="guest-pinbar">
            <label class="guest-search">
                <svg class="guest-search__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>
                </svg>
                <input
                    type="search"
                    x-model="query"
                    class="guest-search__input"
                    placeholder="{{ __('guest.search_menu') }}"
                    aria-label="{{ __('guest.search_menu') }}"
                >
            </label>

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
        </div>

        {{-- Popular today rail (mockup screen 2/2b/3). Hidden while searching or
             when a single category is selected, since the list below covers it. --}}
        @include('livewire.partials.guest-popular-rail')

        {{-- Empty-search hint (shown only when search hides every item) --}}
        <p class="guest-noresults" x-show="query.trim() !== '' && !hasMatches" x-cloak>
            {{ __('guest.no_search_results') }}
        </p>

        {{-- Skeleton product cards shown while language switches --}}
        <div wire:loading wire:target="switchLanguage" class="space-y-10 pt-4">
            @for($s = 0; $s < 2; $s++)
                <section class="space-y-4">
                    <div class="skeleton h-5 w-32">&nbsp;</div>
                    <div class="menu-product-grid">
                        @for($i = 0; $i < 4; $i++)
                            <article class="surface-card menu-product-card">
                                <div class="menu-product-image-area skeleton">&nbsp;</div>
                                <div class="menu-product-body">
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
        <div wire:loading.remove wire:target="switchLanguage">
            @forelse($categories as $category)
                @php
                    $categoryNames = $category->products
                        ->map(fn ($p) => $searchNames[$p->id] ?? '')
                        ->values()
                        ->all();
                @endphp
                <section
                    data-category-section="{{ $category->id }}"
                    x-data="{ names: @js($categoryNames) }"
                    x-show="(activeCategory === 'all' || activeCategory === '{{ $category->id }}')
                        && (query.trim() === '' || names.some(n => n.includes(query.toLowerCase().trim())))"
                >
                    <h3 class="menu-category-header">{{ $category->translated('name') }}</h3>

                    <div x-data="{ expanded: null }" class="menu-product-grid">
                        @foreach($category->products as $product)
                            @php
                                $timePricedAmount = $pricingRules->isNotEmpty()
                                    ? $product->getTimePriced($pricingRules)
                                    : null;
                                $hasTimeDiscount = $timePricedAmount !== null && $timePricedAmount < $product->final_price;
                                $displayPrice = $hasTimeDiscount ? $timePricedAmount : ($product->is_on_sale ? $product->final_price : $product->price);
                                $searchName = $searchNames[$product->id] ?? '';
                                // Shared filter attributes spread onto each theme's <article> so
                                // client-side search hides non-matching cards without a round-trip.
                                $filterAttrs = 'data-product data-name="'.e($searchName).'" '
                                    .'x-bind:hidden="query.trim() !== \'\' && !$el.dataset.name.includes(query.toLowerCase().trim())"';
                            @endphp

                            @if($theme === 'modern')
                                {{-- Modern theme: horizontal card (image left, text right) --}}
                                <article
                                    class="surface-card menu-product-card menu-card-modern {{ ! $product->is_available ? 'menu-product-sold-out' : '' }}"
                                    {!! $filterAttrs !!}
                                    wire:key="product-{{ $product->id }}"
                                >
                                    {{-- Sold Out badge --}}
                                    @if(! $product->is_available)
                                        <div class="menu-product-sold-out-badge">{{ __('guest.sold_out') }}</div>
                                    @endif

                                    {{-- Sale/Discount badge --}}
                                    @if($product->is_on_sale)
                                        <div class="menu-badge-sale">{{ __('guest.flash_sale') }}</div>
                                    @elseif($hasTimeDiscount)
                                        <div class="menu-badge-sale">{{ __('guest.limited_offer') }}</div>
                                    @endif

                                    {{-- Horizontal layout: image left, content right --}}
                                    <div class="menu-card-modern-inner">
                                        <div class="menu-card-modern-image"
                                             x-data="{ loaded: {{ productImage($product) ? 'false' : 'true' }}, broken: false }">
                                            @if(productImage($product, 'card'))
                                                <img src="{{ productImage($product, 'card') }}"
                                                     alt="{{ $product->translated('name') }}"
                                                     class="menu-product-img"
                                                     loading="lazy"
                                                     style="opacity: 0; transition: opacity 200ms ease"
                                                     x-on:load="loaded = true"
                                                     x-on:error="broken = true"
                                                     x-bind:style="(loaded && !broken) ? 'opacity:1; transition: opacity 200ms ease' : 'opacity:0'">
                                            @endif
                                            <div class="menu-product-placeholder"
                                                 x-show="broken || {{ productImage($product) ? 'false' : 'true' }}"
                                                 x-cloak>
                                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/>
                                                    <path d="M7 2v20"/>
                                                    <path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/>
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="menu-card-modern-content">
                                            <p class="menu-product-name">{{ $product->translated('name') }}</p>
                                            @if($product->translated('description'))
                                                <p class="menu-card-modern-desc">{{ Str::limit($product->translated('description'), 60) }}</p>
                                            @endif
                                            <div class="menu-card-modern-footer">
                                                <span class="menu-product-price">
                                                    @if($hasTimeDiscount || $product->is_on_sale)
                                                        <span style="text-decoration:line-through;opacity:0.5;margin-right:4px"><x-price :amount="$product->price" :shop="$shop" /></span>
                                                    @endif
                                                    <x-price :amount="$displayPrice" :shop="$shop" />
                                                </span>
                                                @if($product->is_available)
                                                    <button wire:click.stop="addToCart({{ $product->id }})" class="menu-product-add" type="button" aria-label="Add {{ $product->translated('name') }} to order">+</button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </article>

                            @elseif($theme === 'dark')
                                {{-- Dark theme: hero card with overlay text on image --}}
                                <article
                                    class="surface-card menu-product-card menu-card-dark {{ ! $product->is_available ? 'menu-product-sold-out' : '' }}"
                                    x-data="{ loaded: {{ productImage($product) ? 'false' : 'true' }}, broken: false }"
                                    {!! $filterAttrs !!}
                                    wire:key="product-{{ $product->id }}"
                                >
                                    {{-- Sold Out badge --}}
                                    @if(! $product->is_available)
                                        <div class="menu-product-sold-out-badge">{{ __('guest.sold_out') }}</div>
                                    @endif

                                    {{-- Sale/Discount badge --}}
                                    @if($product->is_on_sale)
                                        <div class="menu-badge-sale">{{ __('guest.flash_sale') }}</div>
                                    @elseif($hasTimeDiscount)
                                        <div class="menu-badge-sale">{{ __('guest.limited_offer') }}</div>
                                    @endif

                                    {{-- Hero image with overlay --}}
                                    <div class="menu-product-image-area">
                                        <div class="skeleton" style="position:absolute;inset:0;border-radius:0" x-show="!loaded && !broken"></div>
                                        @if(productImage($product, 'card'))
                                            <img src="{{ productImage($product, 'card') }}"
                                                 alt="{{ $product->translated('name') }}"
                                                 class="menu-product-img"
                                                 loading="lazy"
                                                 style="opacity: 0; transition: opacity 200ms ease"
                                                 x-on:load="loaded = true"
                                                 x-on:error="broken = true"
                                                 x-bind:style="(loaded && !broken) ? 'opacity:1; transition: opacity 200ms ease' : 'opacity:0'">
                                        @endif
                                        <div class="menu-product-placeholder" x-show="broken || {{ productImage($product) ? 'false' : 'true' }}" x-cloak>
                                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/>
                                                <path d="M7 2v20"/>
                                                <path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/>
                                            </svg>
                                        </div>

                                        {{-- Overlay text on image --}}
                                        <div class="menu-card-dark-overlay">
                                            <p class="menu-product-name" style="color: rgb(var(--ink));">{{ $product->translated('name') }}</p>
                                            <div style="display:flex;align-items:center;justify-content:space-between;gap:4px">
                                                <span class="menu-product-price" style="color: rgb(var(--ink));">
                                                    @if($hasTimeDiscount || $product->is_on_sale)
                                                        <span style="text-decoration:line-through;opacity:0.5;margin-right:4px"><x-price :amount="$product->price" :shop="$shop" /></span>
                                                    @endif
                                                    <x-price :amount="$displayPrice" :shop="$shop" />
                                                </span>
                                                @if($product->is_available)
                                                    <button wire:click.stop="addToCart({{ $product->id }})" class="menu-product-add" type="button" aria-label="Add {{ $product->translated('name') }} to order">+</button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Description below image --}}
                                    @if($product->translated('description'))
                                        <div class="menu-product-body">
                                            <p class="menu-card-dark-desc">{{ Str::limit($product->translated('description'), 80) }}</p>
                                        </div>
                                    @endif
                                </article>

                            @else
                                {{-- Warm theme (default): vertical card, 2-column grid --}}
                                <article
                                    class="surface-card menu-product-card {{ ! $product->is_available ? 'menu-product-sold-out' : '' }}"
                                    x-data="{ loaded: {{ productImage($product) ? 'false' : 'true' }}, broken: false }"
                                    {!! $filterAttrs !!}
                                    @click="expanded = (expanded === {{ $product->id }}) ? null : {{ $product->id }}"
                                    wire:key="product-{{ $product->id }}"
                                >
                                    {{-- Sale/Discount badge --}}
                                    @if($product->is_on_sale)
                                        <div class="menu-badge-sale">{{ __('guest.flash_sale') }}</div>
                                    @elseif($hasTimeDiscount)
                                        <div class="menu-badge-sale">{{ __('guest.limited_offer') }}</div>
                                    @endif

                                    {{-- Sold Out badge (mutually exclusive with sale badge) --}}
                                    @if(! $product->is_available)
                                        <div class="menu-product-sold-out-badge">
                                            {{ __('guest.sold_out') }}
                                        </div>
                                    @endif

                                    {{-- Image area: fixed height, shimmer while loading --}}
                                    <div class="menu-product-image-area">
                                        {{-- Shimmer skeleton (shown while image loads) --}}
                                        <div class="skeleton" style="position:absolute;inset:0;border-radius:0" x-show="!loaded && !broken"></div>

                                        @if(productImage($product, 'card'))
                                            <img
                                                src="{{ productImage($product, 'card') }}"
                                                alt="{{ $product->translated('name') }}"
                                                class="menu-product-img"
                                                loading="lazy"
                                                style="opacity: 0; transition: opacity 200ms ease"
                                                x-on:load="loaded = true"
                                                x-on:error="broken = true"
                                                x-bind:style="(loaded && !broken) ? 'opacity:1; transition: opacity 200ms ease' : 'opacity:0'"
                                            >
                                        @endif

                                        {{-- Placeholder icon (shown when broken or no image) --}}
                                        <div class="menu-product-placeholder"
                                             x-show="broken || {{ productImage($product) ? 'false' : 'true' }}"
                                             x-cloak>
                                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/>
                                                <path d="M7 2v20"/>
                                                <path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/>
                                            </svg>
                                        </div>
                                    </div>

                                    {{-- Name + price + quick-add --}}
                                    <div class="menu-product-body">
                                        <p class="menu-product-name">{{ $product->translated('name') }}</p>
                                        <div style="display:flex;align-items:center;justify-content:space-between;gap:4px">
                                            <span class="menu-product-price">
                                                @if($hasTimeDiscount || $product->is_on_sale)
                                                    <span style="text-decoration:line-through;opacity:0.5;margin-right:4px"><x-price :amount="$product->price" :shop="$shop" /></span>
                                                @endif
                                                <x-price :amount="$displayPrice" :shop="$shop" />
                                            </span>
                                            @if($product->is_available)
                                                <button
                                                    wire:click.stop="addToCart({{ $product->id }})"
                                                    class="menu-product-add"
                                                    type="button"
                                                    aria-label="Add {{ $product->translated('name') }} to order"
                                                >+</button>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Expandable description (accordion — one at a time) --}}
                                    @if($product->translated('description'))
                                        <div
                                            class="menu-product-description"
                                            x-bind:data-expanded="expanded === {{ $product->id }} ? 'true' : 'false'"
                                        >
                                            <p>{{ $product->translated('description') }}</p>
                                        </div>
                                    @endif
                                </article>
                            @endif
                        @endforeach
                    </div>
                </section>
            @empty
                <section class="surface-card border-dashed p-14 text-center">
                    <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.2em] text-ink-soft">{{ __('guest.no_items_available') }}</p>
                    <p class="mt-2 text-sm text-ink-soft">Check back soon -- the menu is being updated.</p>
                </section>
            @endforelse
        </div>
    </main>

    {{-- Bottom Bar: Solo Mode --}}
    @if(!$this->isGroupMode && count($cart) > 0)
        <div class="fixed bottom-0 left-0 right-0 z-[60] p-4 sm:p-6">
            <div class="mx-auto w-full max-w-6xl">
                <button wire:click="toggleReview" class="surface-card flex w-full items-center justify-between gap-3 border-ink bg-ink px-5 py-4 text-panel transition-transform duration-200 hover:-translate-y-0.5">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center rounded-full border border-panel/20 bg-panel/15 px-2.5 py-1 font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-panel/90">{{ __('guest.ready') }}</span>
                        <span class="font-display text-base font-bold leading-none sm:text-2xl">{{ __('guest.review_order') }}</span>
                    </div>
                    <span class="font-display text-xl font-extrabold leading-none sm:text-3xl"><x-price :amount="$this->total" :shop="$shop" /></span>
                </button>
            </div>
        </div>
    @endif

    {{-- Bottom Bar: Group Mode --}}
    @if($this->isGroupMode && count($groupCartItems) > 0)
        <div class="fixed bottom-0 left-0 right-0 z-[60] p-4 sm:p-6">
            <div class="mx-auto w-full max-w-6xl">
                <button wire:click="toggleReview" class="surface-card flex w-full items-center justify-between gap-3 border-crema bg-ink px-5 py-4 text-panel transition-transform duration-200 hover:-translate-y-0.5">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center rounded-full border border-crema/30 bg-crema/15 px-2.5 py-1 font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-crema">
                            {{ $this->cartItemCount }} {{ __('guest.items') }}
                        </span>
                        <span class="font-display text-base font-bold leading-none sm:text-2xl">{{ __('guest.review_group_order') }}</span>
                    </div>
                    <span class="font-display text-xl font-extrabold leading-none sm:text-3xl"><x-price :amount="$this->total" :shop="$shop" /></span>
                </button>
            </div>
        </div>
    @endif

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
                                <button wire:click="toggleReview" type="button" class="guest-addbtn guest-addbtn--dark" style="flex:none;width:auto;padding-inline:var(--space-6)">
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
                                {{-- Solo cart lines --}}
                                @foreach($cart as $key => $item)
                                    <div class="guest-cartline">
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
                                    <div class="guest-cart__hint" style="margin-top:var(--space-3);color:rgb(var(--ink))">
                                        <span>
                                            {{ __('guest.welcome_back') }}
                                            @if($recognizedCustomer['name'] ?? null){{ $recognizedCustomer['name'] }}@endif
                                            · {{ $recognizedCustomer['points'] ?? 0 }} {{ __('guest.points_label') }}
                                        </span>
                                    </div>
                                    <button wire:click="orderUsual" type="button" class="guest-addbtn" style="margin-top:var(--space-3);background:rgb(var(--crema) / 0.12);color:rgb(var(--crema));box-shadow:none">
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
                        <button wire:click="toggleReview" type="button" class="guest-addbtn guest-addbtn--dark" style="flex:0 0 auto;background:rgb(var(--panel));color:rgb(var(--ink));box-shadow:none;border:1px solid rgb(var(--line))">{{ __('guest.cancel') }}</button>
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

                    <div class="guest-sheet__body">
                        <h3 class="guest-sheet__name">{{ $customizingProduct->translated('name') }}</h3>
                        <p class="guest-sheet__price"><x-price :amount="$this->customizingProductPrice" :shop="$shop" /></p>

                        @if($customizingProduct->translated('description'))
                            <p class="guest-sheet__desc">{{ $customizingProduct->translated('description') }}</p>
                        @endif

                        @foreach($customizingProduct->modifierGroups as $group)
                            <section class="guest-mgroup">
                                <div class="guest-mgroup__head">
                                    <span class="guest-mgroup__title">{{ $group->translated('name') }}</span>
                                    @if($group->min_selection > 0)
                                        <span class="guest-mgroup__req">{{ __('guest.required') }}</span>
                                    @else
                                        <span class="guest-mgroup__req" style="background:rgb(var(--ink-soft)/0.12);color:rgb(var(--ink-soft))">{{ __('guest.optional') }}</span>
                                    @endif
                                    @if($group->max_selection > 1)
                                        <span class="guest-mgroup__opt">{{ __('guest.up_to', ['count' => $group->max_selection]) }}</span>
                                    @endif
                                </div>

                                @foreach($group->options as $option)
                                    @php
                                        $isChecked = $group->max_selection == 1
                                            ? (($selectedModifiers[$group->id] ?? null) == $option->id)
                                            : in_array((string) $option->id, (array) ($selectedModifiers[$group->id] ?? []));
                                    @endphp
                                    <label class="guest-opt">
                                        <input
                                            type="{{ $group->max_selection == 1 ? 'radio' : 'checkbox' }}"
                                            value="{{ $option->id }}"
                                            wire:click="selectModifier({{ $group->id }}, {{ $option->id }}, {{ $group->max_selection > 1 ? 'true' : 'false' }})"
                                            name="group_{{ $group->id }}"
                                            class="guest-opt__input"
                                            @checked($isChecked)
                                        >
                                        <span class="guest-opt__mark {{ $group->max_selection == 1 ? '' : 'guest-opt__mark--sq' }}" aria-hidden="true"></span>
                                        <span class="guest-opt__name">{{ $option->translated('name') }}</span>
                                        @if($option->price_adjustment > 0)
                                            <span class="guest-opt__price guest-opt__price--add">+<x-price :amount="$option->price_adjustment" :shop="$shop" /></span>
                                        @else
                                            <span class="guest-opt__price">{{ __('guest.included') }}</span>
                                        @endif
                                    </label>
                                @endforeach
                            </section>
                        @endforeach

                        {{-- Special request / allergen note (pilot safety feature) --}}
                        <div class="guest-note">
                            <label for="guest-item-note" class="guest-note__label">{{ __('guest.item_note_label') }}</label>
                            <textarea
                                id="guest-item-note"
                                wire:model="itemNote"
                                maxlength="255"
                                class="guest-note__field"
                                placeholder="{{ __('guest.item_note_placeholder') }}"></textarea>
                        </div>
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
