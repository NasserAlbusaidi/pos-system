@php
    $cartIsEmpty = $this->isGroupMode ? empty($groupCartItems) : empty($cart);
    $cartProducts = $categories
        ->flatMap(fn ($category) => $category->products)
        ->keyBy('id');
    $checkoutFallbacks = [
        'customer-ordering/assets/hopresso/coffee-latte-top.png',
        'customer-ordering/assets/hopresso/iced-latte.png',
        'customer-ordering/assets/hopresso/caramel-cream.png',
        'customer-ordering/assets/hopresso/americano.png',
    ];
    $checkoutServiceFee = $this->checkoutServiceFee;
    $checkoutDisplayTotal = $this->checkoutTotal;
    $showDetails = ! $cartIsEmpty;
    $lineIndex = 0;

    $lineImage = function ($item, int $index) use ($cartProducts, $checkoutFallbacks) {
        $product = $cartProducts->get($item['id'] ?? null);
        $fallback = $checkoutFallbacks[$index % count($checkoutFallbacks)];

        return productImage($product, 'card') ?: asset($fallback);
    };

    $lineName = function ($item) use ($cartProducts) {
        $product = $cartProducts->get($item['id'] ?? null);

        return $product?->translated('name') ?: ($item['name'] ?? __('guest.items'));
    };

    $modifierText = function ($item) use ($cartProducts) {
        $product = $cartProducts->get($item['id'] ?? null);
        $selected = collect((array) ($item['selectedModifiers'] ?? []))
            ->flatMap(fn ($value) => is_array($value) ? $value : [$value])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($product && $selected->isNotEmpty()) {
            $translated = $product->modifierGroups
                ->flatMap(fn ($group) => $group->options)
                ->filter(fn ($option) => $selected->contains((int) $option->id))
                ->map(fn ($option) => $option->translated('name'))
                ->filter()
                ->values()
                ->all();

            if (! empty($translated)) {
                return implode(' · ', $translated);
            }
        }

        $names = array_values(array_filter((array) ($item['modifierNames'] ?? [])));

        return ! empty($names) ? implode(' · ', $names) : null;
    };

    $paymentOptions = [
        'counter' => __('guest.payment_method_counter'),
        'online' => __('guest.payment_method_online'),
    ];
    $selectedPaymentLabel = $paymentOptions[$paymentMethod] ?? $paymentOptions['counter'];
@endphp

<div class="bite-checkout-page">
    @if($cartIsEmpty)
        <div class="bite-empty bite-empty--checkout">
            <p>{{ __('guest.cart_empty_title') }}</p>
            <span>{{ __('guest.cart_empty_body') }}</span>
            <button wire:click="closeCheckout" type="button" class="bite-primary-btn">{{ __('guest.browse_menu') }}</button>
        </div>
    @else
        <div class="bite-checkout-list">
            @if($this->isGroupMode)
                @php $groupedByParticipant = collect($groupCartItems)->groupBy('participant_id'); @endphp
                @foreach($groupedByParticipant as $pid => $items)
                    @foreach($items as $item)
                        @php
                            $imageUrl = $lineImage($item, $lineIndex++);
                            $modifiers = $modifierText($item);
                            $lineKey = $item['itemKey'] ?? '';
                            $lineCanEdit = $pid === $participantId;
                        @endphp
                        <article class="bite-checkout-card">
                            <img src="{{ $imageUrl }}" alt="{{ $lineName($item) }}" loading="lazy">
                            <div class="bite-checkout-card__body">
                                <h2>{{ $lineName($item) }}</h2>
                                @if($modifiers)
                                    <p>{{ $modifiers }}</p>
                                @endif
                                @if(filled($item['note'] ?? null))
                                    <p>{{ $item['note'] }}</p>
                                @endif
                                <strong><x-price :amount="($item['price'] ?? 0) * ($item['quantity'] ?? 1)" :shop="$shop" /></strong>
                            </div>
                            @if($lineCanEdit)
                                <div class="bite-checkout-qty">
                                    <button wire:click="decrementItem('{{ $lineKey }}')" type="button" aria-label="{{ __('guest.decrease_qty') }}">-</button>
                                    <span>{{ $item['quantity'] ?? 1 }}</span>
                                    <button wire:click="incrementItem('{{ $lineKey }}')" type="button" aria-label="{{ __('guest.increase_qty') }}">+</button>
                                </div>
                            @else
                                <span class="bite-checkout-readonly-qty">{{ $item['quantity'] ?? 1 }}</span>
                            @endif
                        </article>
                    @endforeach
                @endforeach
            @else
                @foreach($cart as $key => $item)
                    @php
                        $imageUrl = $lineImage($item, $lineIndex++);
                        $modifiers = $modifierText($item);
                    @endphp
                    <article class="bite-checkout-card">
                        <img src="{{ $imageUrl }}" alt="{{ $lineName($item) }}" loading="lazy">
                        <div class="bite-checkout-card__body">
                            <h2>{{ $lineName($item) }}</h2>
                            @if($modifiers)
                                <p>{{ $modifiers }}</p>
                            @endif
                            @if(filled($item['note'] ?? null))
                                <p>{{ $item['note'] }}</p>
                            @endif
                            <strong><x-price :amount="$item['price'] * $item['quantity']" :shop="$shop" /></strong>
                        </div>
                        <div class="bite-checkout-qty">
                            <button wire:click="decrementItem('{{ $key }}')" type="button" aria-label="{{ __('guest.decrease_qty') }}">-</button>
                            <span>{{ $item['quantity'] }}</span>
                            <button wire:click="incrementItem('{{ $key }}')" type="button" aria-label="{{ __('guest.increase_qty') }}">+</button>
                        </div>
                    </article>
                @endforeach
            @endif
        </div>

        <label class="bite-checkout-note">
            <span>{{ __('guest.order_note') }}</span>
            <textarea
                wire:model="orderNote"
                maxlength="500"
                placeholder="{{ $locale === 'ar' ? __('guest.order_note_placeholder') : 'No sugar, extra hot, allergies...' }}"
            ></textarea>
        </label>

        <section class="bite-checkout-total-card">
            <div><span>{{ __('guest.subtotal') }}</span><strong><x-price :amount="$this->subtotal" :shop="$shop" /></strong></div>
            <div><span>{{ __('guest.service') }}</span><strong><x-price :amount="$checkoutServiceFee" :shop="$shop" /></strong></div>
            <div><span>{{ __('guest.vat_5') }}</span><strong><x-price :amount="$this->tax" :shop="$shop" /></strong></div>
            <div><span>{{ __('guest.total') }}</span><strong><x-price :amount="$checkoutDisplayTotal" :shop="$shop" /></strong></div>
        </section>

        @if($showDetails)
            <section class="bite-checkout-details">
                <div class="bite-payment-card">
                    <div
                        class="bite-payment-picker"
                        x-data="{ open: false }"
                        @click.outside="open = false"
                    >
                        <button
                            type="button"
                            class="bite-payment-picker__summary"
                            aria-haspopup="listbox"
                            aria-expanded="false"
                            aria-controls="bite-payment-options"
                            x-bind:aria-expanded="open.toString()"
                            @click="open = ! open"
                        >
                            <span class="bite-payment-picker__icon" aria-hidden="true">
                                {{ $prototypeIcon('card') }}
                            </span>
                            <span class="bite-payment-picker__copy">
                                <span class="bite-payment-picker__label">{{ __('guest.payment_method_label') }}</span>
                                <strong>{{ $selectedPaymentLabel }}</strong>
                            </span>
                            <span class="bite-payment-picker__chevron" aria-hidden="true">
                                {{ $prototypeIcon('chevron-down') }}
                            </span>
                        </button>

                        <div
                            id="bite-payment-options"
                            class="bite-payment-picker__options"
                            role="listbox"
                            aria-label="{{ __('guest.payment_method_label') }}"
                            x-show="open"
                            x-cloak
                            x-transition.opacity.duration.120ms
                        >
                            @foreach($paymentOptions as $method => $label)
                                @php $isSelectedPayment = $paymentMethod === $method; @endphp
                                <button
                                    type="button"
                                    class="bite-payment-picker__option{{ $isSelectedPayment ? ' is-selected' : '' }}"
                                    role="option"
                                    aria-selected="{{ $isSelectedPayment ? 'true' : 'false' }}"
                                    wire:click="$set('paymentMethod', '{{ $method }}')"
                                    @click="open = false"
                                >
                                    <span class="bite-payment-picker__option-check" aria-hidden="true">
                                        @if($isSelectedPayment)
                                            {{ $prototypeIcon('check') }}
                                        @endif
                                    </span>
                                    <span class="bite-payment-picker__option-label">{{ $label }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
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
            </section>
        @endif

        <footer class="bite-checkout-powered" aria-label="{{ __('guest.powered_by') }} Bite">
            <span>{{ __('guest.powered_by') }}</span>
            <img src="{{ asset('customer-ordering/assets/brand/bite-powered-logo.png') }}" alt="Bite">
        </footer>
    @endif
</div>

<div class="guest-actionbar bite-actionbar bite-checkout-actionbar">
    @if($cartIsEmpty)
        <button wire:click="closeCheckout" type="button" class="bite-secondary-btn">{{ __('guest.cancel') }}</button>
    @else
        <button
            wire:click="submitOrder"
            wire:loading.attr="disabled"
            wire:target="submitOrder"
            type="button"
            class="bite-primary-btn"
        >
            <span wire:loading.remove wire:target="submitOrder">{{ __('guest.place_order') }}</span>
            <span wire:loading wire:target="submitOrder" class="loading-spinner"></span>
            <strong><x-price :amount="$checkoutDisplayTotal" :shop="$shop" /></strong>
        </button>
    @endif
</div>
