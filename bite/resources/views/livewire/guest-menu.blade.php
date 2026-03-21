<div class="guest-menu-bg relative flex min-h-full flex-col overflow-x-hidden"
     @if($this->isGroupMode) wire:poll.3s @endif>
    <header class="sticky top-0 z-50 border-b border-line/80 bg-panel/85 px-4 py-4 backdrop-blur-xl sm:px-6">
        <div class="mx-auto flex w-full max-w-6xl items-center justify-between gap-3">
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

    <main class="mx-auto w-full max-w-6xl flex-1 space-y-10 px-4 py-6 pb-32 sm:px-6">
        <section class="surface-card p-5 sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="section-headline">{{ __('guest.guest_experience') }}</p>
                    <h2 class="mt-1 text-3xl font-extrabold leading-none text-ink">{{ __('guest.build_your_order') }}</h2>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" onclick="window.biteFavorites.load({{ $shop->id }})" class="btn-secondary !px-3 !py-2">
                        {{ __('guest.load_favorite') }}
                    </button>
                    <button type="button" wire:click="saveFavorite" class="btn-primary !px-3 !py-2">
                        {{ __('guest.save_favorite') }}
                    </button>
                </div>
            </div>
        </section>

        {{-- Skeleton product cards shown while language switches --}}
        <div wire:loading wire:target="switchLanguage" class="space-y-10">
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
                <section>
                    <h3 class="menu-category-header">{{ $category->translated('name') }}</h3>

                    <div x-data="{ expanded: null }" class="menu-product-grid">
                        @foreach($category->products as $product)
                            @php
                                $timePricedAmount = $pricingRules->isNotEmpty()
                                    ? $product->getTimePriced($pricingRules)
                                    : null;
                                $hasTimeDiscount = $timePricedAmount !== null && $timePricedAmount < $product->final_price;
                                $displayPrice = $hasTimeDiscount ? $timePricedAmount : ($product->is_on_sale ? $product->final_price : $product->price);
                            @endphp

                            @if($theme === 'modern')
                                {{-- Modern theme: horizontal card (image left, text right) --}}
                                <article
                                    class="surface-card menu-product-card menu-card-modern {{ ! $product->is_available ? 'menu-product-sold-out' : '' }}"
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
                                                     x-show="loaded && !broken"
                                                     x-on:load="loaded = true"
                                                     x-on:error="broken = true"
                                                     x-bind:style="(loaded && !broken) ? '' : 'display: none'">
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
                                                 x-show="loaded && !broken"
                                                 x-on:load="loaded = true"
                                                 x-on:error="broken = true"
                                                 x-bind:style="(loaded && !broken) ? '' : 'display: none'">
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
                                                x-show="loaded && !broken"
                                                x-on:load="loaded = true"
                                                x-on:error="broken = true"
                                                x-bind:style="(loaded && !broken) ? '' : 'display: none'"
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

    {{-- Review Modal --}}
    @if($showReviewModal)
        <div class="fixed inset-0 z-[100] flex items-end justify-center bg-ink/75 p-0 backdrop-blur-sm sm:items-center sm:p-6">
            <div class="surface-card flex h-full w-full max-w-2xl flex-col overflow-hidden sm:h-auto sm:max-h-[90vh] sm:rounded-xl">
                <div class="border-b border-line bg-muted/35 px-6 py-5 sm:px-8">
                    <h3 class="font-display text-3xl font-extrabold leading-none text-ink">
                        @if($this->isGroupMode)
                            {{ __('guest.group_order') }}
                        @else
                            {{ __('guest.your_order') }}
                        @endif
                    </h3>
                    <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('guest.review_before_sending') }}</p>
                </div>

                <div class="min-h-0 flex-1 space-y-6 overflow-y-auto p-5 sm:p-8">
                    <section class="space-y-3">
                        <p class="section-headline">{{ __('guest.items') }}</p>

                        @if($this->isGroupMode)
                            {{-- Group Cart Items (grouped by participant) --}}
                            <div class="divide-y divide-line rounded-xl border border-line bg-panel">
                                @php
                                    $groupedByParticipant = collect($groupCartItems)->groupBy('participant_id');
                                @endphp
                                @foreach($groupedByParticipant as $pid => $items)
                                    <div class="px-3 py-3 sm:px-4">
                                        <div class="mb-2 flex items-center gap-2">
                                            <span class="inline-flex h-2.5 w-2.5 rounded-full" style="background-color: {{ $participantColors[$pid] ?? '#E57373' }}"></span>
                                            <span class="font-mono text-[9px] font-semibold uppercase tracking-[0.16em] text-ink-soft">
                                                @if($pid === $participantId)
                                                    {{ __('guest.you') }}
                                                @else
                                                    {{ __('guest.participant') }} {{ array_search($pid, array_keys($groupedByParticipant->toArray())) + 1 }}
                                                @endif
                                            </span>
                                        </div>
                                        @foreach($items as $item)
                                            <div class="flex flex-col gap-2 py-2 sm:flex-row sm:items-start sm:justify-between sm:gap-3 {{ !$loop->last ? 'border-b border-line/50' : '' }}">
                                                <div class="flex items-start gap-3">
                                                    @if($pid === $participantId)
                                                        <div class="flex items-center gap-1">
                                                            <button wire:click="decrementItem('{{ $item['itemKey'] ?? '' }}')" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-line bg-muted font-mono text-xs font-bold text-ink transition-colors hover:border-ink">-</button>
                                                            <span class="inline-flex h-9 min-w-7 items-center justify-center font-mono text-[10px] font-bold uppercase text-ink">{{ $item['quantity'] }}</span>
                                                            <button wire:click="incrementItem('{{ $item['itemKey'] ?? '' }}')" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-line bg-muted font-mono text-xs font-bold text-ink transition-colors hover:border-ink">+</button>
                                                        </div>
                                                    @else
                                                        <span class="inline-flex h-7 min-w-7 items-center justify-center font-mono text-[10px] font-bold uppercase text-ink-soft">{{ $item['quantity'] }}x</span>
                                                    @endif
                                                    <div>
                                                        <p class="text-sm font-semibold uppercase tracking-tight text-ink">{{ $item['name'] }}</p>
                                                        @if(!empty($item['modifierNames']))
                                                            <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ implode(', ', $item['modifierNames']) }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="flex items-start gap-2">
                                                    <p class="font-mono text-xs font-bold uppercase text-ink"><x-price :amount="($item['price'] ?? 0) * ($item['quantity'] ?? 1)" :shop="$shop" /></p>
                                                    @if($pid === $participantId)
                                                        <button wire:click="removeItem('{{ $item['itemKey'] ?? '' }}')" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-line bg-muted font-mono text-[10px] font-bold text-ink-soft transition-colors hover:border-alert hover:bg-alert/10 hover:text-alert" title="Remove item">
                                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        @else
                            {{-- Solo Cart Items --}}
                            <div class="divide-y divide-line rounded-xl border border-line bg-panel">
                                @foreach($cart as $key => $item)
                                    <div class="flex flex-col gap-2 px-3 py-3 sm:flex-row sm:items-start sm:justify-between sm:gap-3 sm:px-4">
                                        <div class="flex items-start gap-3">
                                            <div class="flex items-center gap-1">
                                                <button wire:click="decrementItem('{{ $key }}')" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-line bg-muted font-mono text-xs font-bold text-ink transition-colors hover:border-ink">-</button>
                                                <span class="inline-flex h-9 min-w-7 items-center justify-center font-mono text-[10px] font-bold uppercase text-ink">{{ $item['quantity'] }}</span>
                                                <button wire:click="incrementItem('{{ $key }}')" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-line bg-muted font-mono text-xs font-bold text-ink transition-colors hover:border-ink">+</button>
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold uppercase tracking-tight text-ink">{{ $item['name'] }}</p>
                                                @if(!empty($item['modifierNames']))
                                                    <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ implode(', ', $item['modifierNames']) }}</p>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex items-start gap-2">
                                            <p class="font-mono text-xs font-bold uppercase text-ink"><x-price :amount="$item['price'] * $item['quantity']" :shop="$shop" /></p>
                                            <button wire:click="removeItem('{{ $key }}')" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-line bg-muted font-mono text-[10px] font-bold text-ink-soft transition-colors hover:border-alert hover:bg-alert/10 hover:text-alert" title="Remove item">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </section>

                    <section class="grid grid-cols-3 gap-2 sm:gap-3">
                        <div class="rounded-lg border border-line bg-panel px-3 py-2">
                            <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('guest.subtotal') }}</p>
                            <p class="mt-1 font-mono text-xs font-bold uppercase text-ink"><x-price :amount="$this->subtotal" :shop="$shop" /></p>
                        </div>
                        <div class="rounded-lg border border-line bg-panel px-3 py-2">
                            <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('guest.tax') }}</p>
                            <p class="mt-1 font-mono text-xs font-bold uppercase text-ink"><x-price :amount="$this->tax" :shop="$shop" /></p>
                        </div>
                        <div class="rounded-lg border border-line bg-panel px-3 py-2">
                            <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('guest.total') }}</p>
                            <p class="mt-1 font-mono text-xs font-bold uppercase text-ink"><x-price :amount="$this->total" :shop="$shop" /></p>
                        </div>
                    </section>

                    <section class="space-y-2">
                        <label class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('guest.loyalty_phone') }}</label>
                        <input type="tel" wire:model="loyaltyPhone" wire:change.debounce.500ms="recognizeCustomer" class="field w-full font-mono text-sm font-semibold" placeholder="{{ __('guest.loyalty_placeholder') }}">
                        @if($loyaltyError)
                            <div class="rounded-lg border border-alert/35 bg-alert/10 px-3 py-2 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-alert">
                                {{ $loyaltyError }}
                            </div>
                        @endif

                        {{-- Welcome Back Banner --}}
                        @if($showWelcomeBack && is_array($recognizedCustomer))
                            <div class="mt-3 rounded-xl border border-crema/30 bg-crema/5 px-4 py-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-bold text-ink">
                                            {{ __('guest.welcome_back') }}
                                            @if($recognizedCustomer['name'] ?? null)
                                                {{ $recognizedCustomer['name'] }}
                                            @endif
                                        </p>
                                        <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">
                                            {{ $recognizedCustomer['points'] ?? 0 }} {{ __('guest.points_label') }}
                                            &middot;
                                            {{ $recognizedCustomer['visit_count'] ?? 0 }} {{ __('guest.visits_label') }}
                                        </p>
                                    </div>
                                </div>

                                @if(!empty($recognizedCustomer['favorites']))
                                    <button wire:click="orderUsual" class="btn-secondary mt-3 w-full justify-center !border-crema/40 !bg-crema/10 !text-crema hover:!bg-crema/20">
                                        {{ __('guest.order_your_usual') }}
                                    </button>
                                @endif
                            </div>

                            {{-- Recent Order History --}}
                            @if(!empty($customerOrderHistory))
                                <div class="mt-3 space-y-2">
                                    <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('guest.recent_orders') }}</p>
                                    <div class="divide-y divide-line rounded-lg border border-line bg-panel">
                                        @foreach($customerOrderHistory as $pastOrder)
                                            <div class="px-3 py-2">
                                                <div class="flex items-center justify-between">
                                                    <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-soft">{{ $pastOrder['date'] }}</p>
                                                    <p class="font-mono text-[10px] font-bold uppercase text-ink"><x-price :amount="$pastOrder['total']" :shop="$shop" /></p>
                                                </div>
                                                <p class="mt-1 text-xs text-ink-soft">
                                                    {{ collect($pastOrder['items'])->map(fn($i) => $i['quantity'] . 'x ' . $i['name'])->join(', ') }}
                                                </p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif
                    </section>
                </div>

                @if($orderError)
                    <div class="mx-4 mb-0 mt-0 rounded-lg border border-alert/35 bg-alert/10 px-4 py-3 font-mono text-[11px] font-semibold leading-relaxed text-alert sm:mx-8">
                        {{ $orderError }}
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-3 border-t border-line bg-muted/20 p-4 sm:p-8">
                    <button wire:click="toggleReview" class="btn-secondary w-full justify-center">{{ __('guest.cancel') }}</button>
                    <button x-on:click="$dispatch('confirm-action', {
                                title: '{{ __('guest.place_order') }}',
                                message: '{{ $this->isGroupMode ? __('guest.send_group_to_kitchen') : __('guest.send_to_kitchen') }}',
                                action: 'submitOrder',
                                componentId: $wire.id,
                                destructive: false,
                            })"
                            class="btn-primary w-full justify-center">
                        {{ __('guest.place_order') }}
                    </button>
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
            <div class="surface-card flex w-full max-w-xl flex-col overflow-hidden border-t sm:border sm:rounded-xl">
                <div class="flex items-center justify-between border-b border-line bg-muted/35 px-6 py-5 sm:px-8">
                    <div>
                        <h3 class="font-display text-3xl font-extrabold leading-none text-ink">{{ $customizingProduct->translated('name') }}</h3>
                        <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ __('guest.price') }}: <x-price :amount="$this->customizingProductPrice" :shop="$shop" /></p>
                    </div>
                    <button wire:click="$set('showModifierModal', false)" class="rounded-md border border-line bg-panel p-2.5 text-ink-soft hover:border-ink hover:text-ink">
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
                                <h4 class="font-mono text-[11px] font-semibold uppercase tracking-[0.18em] text-ink-soft">{{ $group->translated('name') }}</h4>
                                <span class="font-mono text-[9px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ $group->min_selection > 0 ? __('guest.required') : __('guest.optional') }}</span>
                            </div>

                            <div class="space-y-2">
                                @foreach($group->options as $option)
                                    @php
                                        $isChecked = $group->max_selection == 1
                                            ? (($selectedModifiers[$group->id] ?? null) == $option->id)
                                            : in_array((string) $option->id, (array) ($selectedModifiers[$group->id] ?? []));
                                    @endphp
                                    <label class="flex cursor-pointer items-center justify-between rounded-lg border border-line bg-panel px-3 py-3 transition-colors duration-200 hover:border-ink-soft has-[:checked]:border-crema has-[:checked]:bg-crema/5">
                                        <span class="flex items-center gap-3">
                                            <input
                                                type="{{ $group->max_selection == 1 ? 'radio' : 'checkbox' }}"
                                                value="{{ $option->id }}"
                                                wire:click="selectModifier({{ $group->id }}, {{ $option->id }}, {{ $group->max_selection > 1 ? 'true' : 'false' }})"
                                                name="group_{{ $group->id }}"
                                                class="h-4 w-4 cursor-pointer border-line text-crema focus:ring-0"
                                                @checked($isChecked)
                                            >
                                            <span class="text-sm font-semibold uppercase tracking-tight text-ink">{{ $option->translated('name') }}</span>
                                        </span>
                                        @if($option->price_adjustment > 0)
                                            <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-crema">+<x-price :amount="$option->price_adjustment" :shop="$shop" /></span>
                                        @elseif($group->min_selection > 0 && $group->options->count() > 1)
                                            <span class="font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">{{ __('guest.base_price') }}</span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>

                <div class="grid grid-cols-2 gap-3 border-t border-line bg-muted/20 p-4 sm:p-8">
                    <button wire:click="$set('showModifierModal', false)" class="btn-secondary w-full justify-center">{{ __('guest.cancel') }}</button>
                    <button wire:click="addToCart({{ $customizingProduct->id }})"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-wait"
                            wire:target="addToCart({{ $customizingProduct->id }})"
                            class="btn-primary w-full justify-center">
                        <span wire:loading.remove wire:target="addToCart({{ $customizingProduct->id }})">{{ __('guest.add_for', ['price' => '']) }}<x-price :amount="$this->customizingProductPrice" :shop="$shop" /></span>
                        <span wire:loading wire:target="addToCart({{ $customizingProduct->id }})" class="loading-spinner"></span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
