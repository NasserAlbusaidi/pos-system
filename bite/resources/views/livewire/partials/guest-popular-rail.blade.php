{{-- Popular today rail (mockup screen 2/2b/3). Hidden while searching or when a
     single category is selected, since the list below covers it. Extracted from
     guest-menu.blade.php to keep that file under the 800-line ceiling.
     Expects: $popularProducts, $pricingRules, $shop. Rendered inside the <main>
     Alpine scope, so x-show reads `query` / `activeCategory` from the parent. --}}
@if($popularProducts->isNotEmpty())
    <section class="guest-popular" x-show="query === '' && activeCategory === 'all'">
        <div class="guest-popular__head">
            <h3 class="guest-popular__title">{{ __('guest.popular_today') }}</h3>
        </div>
        <div class="guest-popular__rail">
            @foreach($popularProducts as $product)
                @php
                    $popTimePriced = $pricingRules->isNotEmpty()
                        ? $product->getTimePriced($pricingRules)
                        : null;
                    $popHasTimeDiscount = $popTimePriced !== null && $popTimePriced < $product->final_price;
                    $popDisplayPrice = $popHasTimeDiscount ? $popTimePriced : ($product->is_on_sale ? $product->final_price : $product->price);
                @endphp
                <article class="guest-pcard" wire:key="popular-{{ $product->id }}">
                    <button
                        type="button"
                        wire:click="addToCart({{ $product->id }})"
                        class="guest-pcard__tile"
                        aria-label="{{ __('guest.add_item_aria', ['name' => $product->translated('name')]) }}"
                    >
                        @if(productImage($product, 'card'))
                            <img src="{{ productImage($product, 'card') }}" alt="{{ $product->translated('name') }}" class="guest-pcard__img" loading="lazy">
                        @else
                            <svg class="guest-pcard__placeholder" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/>
                            </svg>
                        @endif
                        @if($product->is_on_sale)
                            <span class="guest-pcard__flag">{{ __('guest.flash_sale') }}</span>
                        @elseif($popHasTimeDiscount)
                            <span class="guest-pcard__flag">{{ __('guest.limited_offer') }}</span>
                        @endif
                    </button>
                    <p class="guest-pcard__name">{{ $product->translated('name') }}</p>
                    <span class="menu-product-price guest-pcard__price">
                        @if($popHasTimeDiscount || $product->is_on_sale)
                            <span style="text-decoration:line-through;opacity:0.5;margin-right:4px"><x-price :amount="$product->price" :shop="$shop" /></span>
                        @endif
                        <x-price :amount="$popDisplayPrice" :shop="$shop" />
                    </span>
                </article>
            @endforeach
        </div>
    </section>
@endif
