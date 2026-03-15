<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Subscription Plans
    |--------------------------------------------------------------------------
    |
    | Define available billing plans. Each plan maps to a Stripe Price ID and
    | includes feature limits that BillingService enforces.
    |
    */

    'plans' => [
        'free' => [
            'name' => 'Free',
            'stripe_price_id' => env('STRIPE_FREE_PRICE_ID'),
            'price' => 0,
            'staff_limit' => 1,
            'product_limit' => 20,
            'features' => [
                'POS Terminal',
                'Guest Menu',
                'Kitchen Display',
            ],
        ],
        'pro' => [
            'name' => 'Pro',
            'stripe_price_id' => env('STRIPE_PRO_PRICE_ID'),
            'price' => 20, // OMR per month
            'staff_limit' => null, // unlimited
            'product_limit' => null, // unlimited
            'features' => [
                'Everything in Free',
                'Unlimited Staff',
                'Unlimited Products',
                'Reports & Analytics',
                'Priority Support',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Trial Period
    |--------------------------------------------------------------------------
    |
    | Number of days new shops get as a free trial of the Pro plan.
    |
    */

    'trial_days' => 14,

    /*
    |--------------------------------------------------------------------------
    | Guest Order Expiry
    |--------------------------------------------------------------------------
    |
    | Minutes before an unpaid guest order is automatically cancelled.
    |
    */

    'order_expiry_minutes' => (int) env('ORDER_EXPIRY_MINUTES', 6),

    /*
    |--------------------------------------------------------------------------
    | Stripe Subscription Webhook Secret
    |--------------------------------------------------------------------------
    |
    | A separate webhook secret for subscription-related Stripe events.
    | This is different from the payment webhook secret in config/payments.php.
    |
    */

    'stripe_webhook_secret' => env('STRIPE_SUBSCRIPTION_WEBHOOK_SECRET'),

];
