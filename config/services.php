<?php

declare(strict_types=1);

return [
    'youtube' => [
        'api_key' => env('YOUTUBE_API_KEY', ''),
    ],

    'stripe' => [
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],
];
