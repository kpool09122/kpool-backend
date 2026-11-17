<?php

declare(strict_types=1);

// Explicit allowlist. Input fields and API descriptions come from OpenAPI.
// Scopes are explicit because internal HTTP Bearer security does not encode OAuth scopes.
// The API must also be allowed by AuthenticateApiRequest; registration does not grant permissions.
// Supports OpenAPI 3.0 JSON object bodies and scalar path/query parameters. Unsupported shapes fail closed.
// Without resultProjector, the API JSON object is returned as-is; select a projector when filtering is needed.
// Definitions are cached per container lifetime. Regenerate OpenAPI and restart workers after changing it.
return [
    'get_account_context' => [
        'document' => __DIR__ . '/../doc/openapi/KPool.IdentityApi.openapi.yaml',
        'method' => 'GET',
        'path' => '/auth/me',
        'scopes' => [],
        'instructions' => 'Returns only Account selection information. If accountIdentifier is null, choose a candidate using switch_account.',
        'annotations' => ['readOnlyHint' => true, 'destructiveHint' => false, 'openWorldHint' => false],
        'resultProjector' => 'account_context',
    ],
    'switch_account' => [
        'document' => __DIR__ . '/../doc/openapi/KPool.AccountApi.openapi.yaml',
        'method' => 'POST',
        'path' => '/accounts/switch',
        'scopes' => [],
        'instructions' => 'Switch the Account for this OAuth connection. Use a delegationIdentifier from get_account_context, or explicitly pass null to return to the original Account. Returns switched: true on success.',
        'requireExplicitArguments' => ['delegationIdentifier'],
        'annotations' => ['readOnlyHint' => false, 'destructiveHint' => false, 'idempotentHint' => true, 'openWorldHint' => false],
        'resultProjector' => 'account_switched',
    ],
];
