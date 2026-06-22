<?php

return [
    /*
    | Active payment gateway driver: test | moyasar | stripe.
    | 'test' completes payments instantly (no real charge) — use it to exercise
    | the whole flow before live keys are available.
    */
    'gateway' => env('PAYMENT_GATEWAY', 'test'),

    'currency' => env('PAYMENT_CURRENCY', 'SAR'),

    'moyasar' => [
        'secret_key' => env('MOYASAR_SECRET_KEY'),
        'publishable_key' => env('MOYASAR_PUBLISHABLE_KEY'),
        'base_url' => 'https://api.moyasar.com/v1',
    ],

    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],
];
