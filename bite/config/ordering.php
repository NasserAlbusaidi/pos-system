<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Guest Order Caps
    |--------------------------------------------------------------------------
    |
    | Server-side sanity ceilings applied in GuestMenu::submitOrder() BEFORE an
    | order is created, for both the solo and group paths. They protect against
    | oversized / abusive carts (a tampered group-cart JSON, a stuck "+" button)
    | turning into a runaway order. These are guards, not UX limits — a normal
    | guest never hits them. Tunable per deployment via the env() defaults.
    |
    | - max_quantity_per_line : largest quantity a single cart line may carry.
    | - max_lines_per_order   : largest number of distinct lines in one order.
    | - max_order_total       : largest order total (OMR) accepted, 3-decimal.
    |
    */

    'max_quantity_per_line' => (int) env('ORDER_MAX_QTY_PER_LINE', 99),

    'max_lines_per_order' => (int) env('ORDER_MAX_LINES', 50),

    'max_order_total' => (float) env('ORDER_MAX_TOTAL', 1000),

];
