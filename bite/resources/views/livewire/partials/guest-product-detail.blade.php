@php
    $detailImage = productImage($customizingProduct, 'card') ?: asset('customer-ordering/assets/hopresso/creamy-latte.png');
    $productDescription = $customizingProduct->translated('description') ?: __('guest.guest_experience');
    $signatureCopy = $locale === 'ar'
        ? 'تحضير هوبريسو الخاص'
        : 'Hopresso crafted signature';
    $detailCopy = $locale === 'ar'
        ? $productDescription
        : ($productDescription === $signatureCopy
            ? 'A crafted Hopresso signature prepared for the QR ordering flow.'
            : $productDescription);
    $ctaLabel = $locale === 'ar' ? __('guest.add_to_order') : 'Add to Cart';
@endphp

<div class="bite-product-page">
    <div class="bite-detail-hero bite-product-page__hero">
        <button wire:click="closeProductPage" type="button" class="bite-product-back" aria-label="{{ __('guest.back_to_menu') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="m15 18-6-6 6-6"/>
            </svg>
            <span class="sr-only">{{ __('guest.back_to_menu') }}</span>
        </button>
        <img src="{{ $detailImage }}" alt="{{ $customizingProduct->translated('name') }}">
    </div>

    @if($modifierError)
        <div class="bite-error">{{ $modifierError }}</div>
    @endif

    <div class="bite-product-page__body">
        <div class="bite-product-title-row">
            <div class="bite-product-title-copy">
                <h1>{{ $customizingProduct->translated('name') }}</h1>
                <p>{{ $signatureCopy }}</p>
            </div>
            <strong class="bite-product-price"><x-price :amount="$this->customizingProductPrice" :shop="$shop" /></strong>
        </div>

        <div class="bite-product-meta" aria-label="{{ $customizingProduct->translated('name') }} details">
            <span>{{ $locale === 'ar' ? '4.9 تقييم' : '4.9 Rating' }}</span>
            <span>{{ $locale === 'ar' ? '300 سعرة' : '300 kcal' }}</span>
            <span>{{ $locale === 'ar' ? '15 دقيقة' : '15 min' }}</span>
        </div>

        <p class="bite-product-description">{{ $detailCopy }}</p>

        @foreach($customizingProduct->modifierGroups as $group)
            <section class="bite-product-choice-group">
                <h2>{{ $group->translated('name') }}</h2>

                <div class="bite-product-options">
                @foreach($group->options as $option)
                    @php
                        $isChecked = $group->max_selection == 1
                            ? (($selectedModifiers[$group->id] ?? null) == $option->id)
                            : in_array((string) $option->id, (array) ($selectedModifiers[$group->id] ?? []));
                    @endphp
                    <label class="bite-product-option {{ $isChecked ? 'is-selected' : '' }}">
                        <input
                            class="bite-product-option__input"
                            type="{{ $group->max_selection == 1 ? 'radio' : 'checkbox' }}"
                            value="{{ $option->id }}"
                            wire:click="selectModifier({{ $group->id }}, {{ $option->id }}, {{ $group->max_selection > 1 ? 'true' : 'false' }})"
                            name="group_{{ $group->id }}"
                            @checked($isChecked)
                        >
                        <span class="bite-product-option__label">
                            {{ $option->translated('name') }}
                            @if((float) $option->price_adjustment > 0)
                                <strong>
                                +<x-price :amount="$option->price_adjustment" :shop="$shop" />
                                </strong>
                            @endif
                        </span>
                    </label>
                @endforeach
                </div>
            </section>
        @endforeach

        <label class="bite-product-note">
            <span>{{ __('guest.item_note') }}</span>
            <textarea
                wire:model="itemNote"
                maxlength="255"
                placeholder="{{ $locale === 'ar' ? __('guest.item_note_placeholder') : 'Less ice, no sugar, allergy note...' }}"
            ></textarea>
        </label>
    </div>
</div>

<div class="guest-actionbar bite-actionbar bite-product-actionbar">
    <button
        wire:click="addToCart({{ $customizingProduct->id }})"
        wire:loading.attr="disabled"
        wire:target="addToCart({{ $customizingProduct->id }})"
        class="bite-primary-btn"
        type="button"
    >
        <span wire:loading.remove wire:target="addToCart({{ $customizingProduct->id }})">{{ $ctaLabel }}</span>
        <span wire:loading wire:target="addToCart({{ $customizingProduct->id }})" class="loading-spinner"></span>
        <span aria-hidden="true">-</span>
        <strong><x-price :amount="$this->customizingProductPrice" :shop="$shop" /></strong>
    </button>
</div>
