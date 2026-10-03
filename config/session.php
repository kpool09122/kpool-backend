<?php

use Illuminate\Support\Str;

return [
    'driver' => 'redis',
    'lifetime' => 120,
    'expire_on_close' => false,
    'encrypt' => false,
    'files' => storage_path('framework/sessions'),
    'connection' => 'default',
    'table' => 'sessions',
    'store' => 'redis',
    'lottery' => [2, 100],
    'cookie' => Str::snake((string) config('app.name')) . '_session',
    'path' => '/',
    'domain' => env('SESSION_DOMAIN'),
    'secure' => env('SESSION_SECURE_COOKIE', true),
    'http_only' => true,
    'same_site' => 'lax',
    'partitioned' => false,
];
