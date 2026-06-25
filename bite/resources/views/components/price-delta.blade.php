@props(['amount', 'shop', 'hideZero' => true])
@php
    $value = (float) $amount;
    $isZero = abs($value) < 0.0005;
@endphp

@unless($hideZero && $isZero)
    <span {{ $attributes->class('price-delta') }}>
        {{ $value < 0 ? '-' : '+' }}<x-price :amount="abs($value)" :shop="$shop" />
    </span>
@endunless
