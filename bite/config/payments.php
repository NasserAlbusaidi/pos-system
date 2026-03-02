<?php

return [
    'provider' => env('PAYMENT_PROVIDER', 'stripe'),
    'stripe_webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
];
