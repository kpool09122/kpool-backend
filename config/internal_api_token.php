<?php

declare(strict_types=1);

return [
    'resource' => env('INTERNAL_API_TOKEN_RESOURCE'),
    'lifetime_seconds' => (int) env('INTERNAL_API_TOKEN_LIFETIME_SECONDS', 60),
];
