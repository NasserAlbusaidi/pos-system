<?php

return [
    'provider' => env('PAYMENT_PROVIDER', 'counter'),
    'stripe_webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
];
