<?php

use Illuminate\Support\Str;

return [
    'default' => 'redis',

    'stores' => [
        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'lock_connection' => 'default',
        ],
    ],

    'prefix' => env('CACHE_PREFIX', Str::slug((string) config('app.name')) . '-cache-'),
];
