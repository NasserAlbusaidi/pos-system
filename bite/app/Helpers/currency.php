<?php

if (! function_exists('formatPrice')) {
    function formatPrice(float $amount, $shop): string
    {
        $decimals = $shop->currency_decimals ?? 3;
        $symbol = $shop->currency_symbol ?? 'OMR';

        return $symbol.' '.number_format($amount, $decimals);
    }
}
