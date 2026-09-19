<?php

declare(strict_types=1);

$origins = array_values(array_filter(array_map(
    static fn (string $origin): string => trim($origin),
    explode(',', (string) env('WEBAUTHN_ALLOWED_ORIGINS', env('FRONTEND_URL', 'http://localhost:3000'))),
)));

return [
    'rp_id' => env('WEBAUTHN_RP_ID', 'localhost'),
    'rp_name' => 'k-pool',
    'allowed_origins' => $origins,
    'challenge_ttl_seconds' => 300,
    'timeout_ms' => 300000,
];
