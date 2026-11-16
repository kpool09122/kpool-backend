<?php

declare(strict_types=1);

return [
    // Remains closed until #890 enforces consent-scoped Account selection.
    'http_enabled' => false,
    'resource' => env('INTERNAL_API_TOKEN_RESOURCE'),
    'lifetime_seconds' => (int) env('INTERNAL_API_TOKEN_LIFETIME_SECONDS', 60),
];
