<?php

declare(strict_types=1);

return [
    'decay_seconds' => (int) env('API_RATE_LIMIT_DECAY_SECONDS', 60),

    'global' => [
        'query' => (int) env('API_RATE_LIMIT_GLOBAL_QUERY', 3000),
        'command' => (int) env('API_RATE_LIMIT_GLOBAL_COMMAND', 600),
    ],

    'surfaces' => [
        'screen' => [
            'account' => [
                'query' => (int) env('API_RATE_LIMIT_SCREEN_ACCOUNT_QUERY', 600),
                'command' => (int) env('API_RATE_LIMIT_SCREEN_ACCOUNT_COMMAND', 120),
            ],
            'ip' => [
                'query' => (int) env('API_RATE_LIMIT_SCREEN_IP_QUERY', 300),
                'command' => (int) env('API_RATE_LIMIT_SCREEN_IP_COMMAND', 30),
            ],
        ],
        'public_api' => [
            'account' => [
                'query' => (int) env('API_RATE_LIMIT_PUBLIC_ACCOUNT_QUERY', 600),
                'command' => (int) env('API_RATE_LIMIT_PUBLIC_ACCOUNT_COMMAND', 120),
            ],
            'ip' => [
                'query' => (int) env('API_RATE_LIMIT_PUBLIC_IP_QUERY', 300),
                'command' => (int) env('API_RATE_LIMIT_PUBLIC_IP_COMMAND', 30),
            ],
        ],
    ],
];
