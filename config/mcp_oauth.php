<?php

declare(strict_types=1);

return [
    // No public authorization or token routes are installed before #887–#889 integration.
    'issuer' => env('MCP_OAUTH_ISSUER'),
    'resource' => env('MCP_OAUTH_RESOURCE'),
    'access_token_seconds' => 3600,
    'refresh_token_seconds' => 2592000,
    'authorization_code_seconds' => 300,
];
