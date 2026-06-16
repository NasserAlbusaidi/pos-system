@php
    $fallbackImages = [
        'customer-ordering/assets/hopresso/americano.png',
        'customer-ordering/assets/hopresso/coffee-latte-top.png',
        'customer-ordering/assets/hopresso/iced-latte.png',
        'customer-ordering/assets/hopresso/caramel-cream.png',
        'customer-ordering/assets/hopresso/sparkling-tea.png',
    ];
@endphp

@if($popularProducts->isNotEmpty())
    <section class="recommended-section guest-popular bite-popular" x-show="query === '' && activeCategory === 'all'">
        <div class="section-title bite-section-head">
            <h2>{{ __('guest.popular_for_table') }}</h2>
            <span class="sr-only">{{ __('guest.popular_today') }}</span>
            <button type="button" wire:click="showFullMenu">
                {{ __('guest.see_all') }}
            </button>
        </div>

        <div class="product-grid web-product-grid bite-popular__rail">
            @foreach($popularProducts->take(4) as $product)
                @php
                    $popTimePriced = $pricingRules->isNotEmpty()
                        ? $product->getTimePriced($pricingRules)
                        : null;
                    $popHasTimeDiscount = $popTimePriced !== null && $popTimePriced < $product->final_price;
                    $popDisplayPrice = $popHasTimeDiscount ? $popTimePriced : ($product->is_on_sale ? $product->final_price : $product->price);
                    $fallback = $fallbackImages[$loop->index % count($fallbackImages)];
                    $imageUrl = productImage($product, 'card') ?: asset($fallback);
                @endphp
                <article class="product-card bite-popular-card" wire:key="popular-{{ $product->id }}">
                    <button
                        type="button"
                        wire:click="openProductSheet({{ $product->id }})"
                        class="product-open bite-popular-card__image"
                        aria-label="{{ __('guest.view_details_aria', ['name' => $product->translated('name')]) }}"
                    >
                        <img src="{{ $imageUrl }}" alt="{{ $product->translated('name') }}" loading="lazy">
                        @if($product->is_on_sale)
                            <span class="bite-popular-card__badge">{{ __('guest.flash_sale') }}</span>
                        @elseif($popHasTimeDiscount)
                            <span class="bite-popular-card__badge">{{ __('guest.limited_offer') }}</span>
                        @endif
                        <h3>{{ $product->translated('name') }}</h3>
                        <p>{{ Str::limit($product->translated('description') ?: __('guest.guest_experience'), 42) }}</p>
                        <strong><x-price :amount="$popDisplayPrice" :shop="$shop" /></strong>
                    </button>
                    <button
                        wire:click="addToCart({{ $product->id }})"
                        class="mini-plus bite-add-mini"
                        type="button"
                        aria-label="{{ __('guest.add_item_aria', ['name' => $product->translated('name')]) }}"
                    >{!! $prototypeIcon('plus') !!}</button>
                </article>
            @endforeach
        </div>
    </section>
@endif
