{{-- Home landing (prototype screen 2) — hero over the shop cover, context strip,
     Today's Highlight, popular grid. Shown when $screen === 'home'. "See all" and
     the popular section header switch to the full browse screen via showMenu().
     The green sticky cart CTA is rendered once by the parent (guest-cart-cta).
     Expects: $shop, $locale, $homeHighlight, $homeGrid, $pricingRules. --}}
@php
    $homeCoverUrl = \App\Support\BrandingUrl::safe($shop->branding['cover_url'] ?? null);
    $homeLogoUrl = \App\Support\BrandingUrl::safe($shop->branding['logo_url'] ?? null);
    $homeNextLocale = $locale === 'ar' ? 'en' : 'ar';
    $homeIsOpen = \App\Support\ShopHours::isOpen($shop);

    $homePrice = function ($product) use ($pricingRules) {
        $timePriced = $pricingRules->isNotEmpty() ? $product->getTimePriced($pricingRules) : null;
        $hasDiscount = $timePriced !== null && $timePriced < $product->final_price;

        return [
            $hasDiscount,
            $hasDiscount ? $timePriced : ($product->is_on_sale ? $product->final_price : $product->price),
        ];
    };
@endphp

<section class="guest-home guest-screen--home">
    <header class="guest-home__hero {{ $homeCoverUrl ? '' : 'guest-home__hero--plain' }}">
        @if($homeCoverUrl)
            <div class="guest-home__herobg" style="background-image: linear-gradient(180deg, rgba(18, 22, 15, 0.24), rgba(18, 22, 15, 0.82)), url('{{ $homeCoverUrl }}')" aria-hidden="true"></div>
        @endif
        <div class="guest-home__toprow">
            @if($homeLogoUrl)
                <img src="{{ $homeLogoUrl }}" alt="{{ $shop->name }}" class="guest-home__logo">
            @else
                <span class="guest-home__wordmark">{{ $shop->name }}</span>
            @endif
            <button type="button" wire:click="switchLanguage('{{ $homeNextLocale }}')" class="guest-home__lang" aria-label="{{ __('guest.choose_language') }}">
                {{ $locale === 'ar' ? 'EN' : 'AR' }}
            </button>
        </div>
        <div class="guest-home__copy">
            <p class="guest-home__venue">{{ $shop->name }}</p>
            <h1 class="guest-home__title">{{ __('guest.home_hero_title') }}</h1>
            <span class="guest-home__loc">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                </svg>
                {{ __('guest.dine_in') }}
            </span>
        </div>
    </header>

    <div class="guest-home__body">
        <div class="guest-home__context">
            <div class="guest-home__chip {{ $homeIsOpen ? '' : 'guest-home__chip--closed' }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
                </svg>
                <span>{{ $homeIsOpen ? __('guest.status_open') : __('guest.status_closed') }}</span>
            </div>
            <div class="guest-home__chip">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>
                </svg>
                <span>{{ __('guest.pay_at_counter') }}</span>
            </div>
        </div>

        @if($homeHighlight)
            @php [$hlDiscount, $hlPrice] = $homePrice($homeHighlight); @endphp
            <section class="guest-home__offers">
                <h2 class="guest-home__h2">{{ __('guest.todays_highlight') }}</h2>
                <button type="button" wire:click="openProductSheet({{ $homeHighlight->id }})" class="guest-home__highlight">
                    <div class="guest-home__highlight-copy">
                        <span class="guest-home__kicker">{{ __('guest.owners_pick') }}</span>
                        <h3 class="guest-home__highlight-name">
                            {{ $homeHighlight->translated('name') }}
                            <span class="guest-home__highlight-price">
                                @if($hlDiscount || $homeHighlight->is_on_sale)
                                    <span class="guest-home__strike"><x-price :amount="$homeHighlight->price" :shop="$shop" /></span>
                                @endif
                                <x-price :amount="$hlPrice" :shop="$shop" />
                            </span>
                        </h3>
                        @if($homeHighlight->translated('description'))
                            <p class="guest-home__highlight-desc">{{ Str::limit($homeHighlight->translated('description'), 72) }}</p>
                        @endif
                        <span class="guest-home__highlight-cta">{{ __('guest.view_item') }} <span aria-hidden="true">→</span></span>
                    </div>
                    @if(productImage($homeHighlight, 'card'))
                        <img src="{{ productImage($homeHighlight, 'card') }}" alt="{{ $homeHighlight->translated('name') }}" class="guest-home__highlight-img" loading="lazy">
                    @else
                        <span class="guest-home__highlight-img guest-home__imgph" aria-hidden="true"></span>
                    @endif
                </button>
            </section>
        @endif

        @if($homeGrid->isNotEmpty())
            <section class="guest-home__popular">
                <div class="guest-home__sectionhead">
                    <h2 class="guest-home__h2">{{ __('guest.popular_today') }}</h2>
                    <button type="button" wire:click="showMenu" class="guest-home__seeall">{{ __('guest.see_all') }}</button>
                </div>
                <div class="guest-home__grid">
                    @foreach($homeGrid as $product)
                        @php [$pDiscount, $pPrice] = $homePrice($product); @endphp
                        <article class="guest-home__card" wire:key="home-{{ $product->id }}">
                            <button type="button" wire:click="openProductSheet({{ $product->id }})" class="guest-home__cardopen" aria-label="{{ __('guest.view_details_aria', ['name' => $product->translated('name')]) }}">
                                @if(productImage($product, 'card'))
                                    <img src="{{ productImage($product, 'card') }}" alt="{{ $product->translated('name') }}" class="guest-home__cardimg" loading="lazy">
                                @else
                                    <span class="guest-home__cardimg guest-home__imgph" aria-hidden="true"></span>
                                @endif
                                <h3 class="guest-home__cardname">{{ $product->translated('name') }}</h3>
                                @if($product->translated('description'))
                                    <p class="guest-home__carddesc">{{ Str::limit($product->translated('description'), 46) }}</p>
                                @endif
                                <strong class="guest-home__cardprice">
                                    @if($pDiscount || $product->is_on_sale)
                                        <span class="guest-home__strike"><x-price :amount="$product->price" :shop="$shop" /></span>
                                    @endif
                                    <x-price :amount="$pPrice" :shop="$shop" />
                                </strong>
                            </button>
                            @if($product->is_available)
                                <button type="button" wire:click="addToCart({{ $product->id }})" class="guest-home__plus" aria-label="{{ __('guest.add_item_aria', ['name' => $product->translated('name')]) }}">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                                </button>
                            @else
                                <span class="guest-home__soldout">{{ __('guest.sold_out') }}</span>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</section>
