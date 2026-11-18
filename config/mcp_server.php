<?php

declare(strict_types=1);

return [
    'api_base_url' => env('MCP_INTERNAL_API_BASE_URL', env('APP_URL')),
    'browser_authorization_url' => env('MCP_BROWSER_AUTHORIZATION_URL'),
    'allowed_origins' => array_values(array_filter(explode(',', (string) env('MCP_ALLOWED_ORIGINS', '')))),
];
