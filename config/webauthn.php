<?php

return [
    'rp_id' => env('WEBAUTHN_RP_ID', 'localhost'),
    'rp_name' => env('WEBAUTHN_RP_NAME', 'k-pool'),
    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('WEBAUTHN_ALLOWED_ORIGINS', 'http://localhost:3000')),
    ))),
];
