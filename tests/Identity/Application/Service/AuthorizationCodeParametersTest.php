<?php

declare(strict_types=1);

namespace Tests\Identity\Application\Service;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\Service\AuthorizationCodeParameters;

class AuthorizationCodeParametersTest extends TestCase
{
    public function testItPreservesTheRequestedParametersSeparatelyFromConsent(): void
    {
        $parameters = new AuthorizationCodeParameters('client', 'https://client.example/callback', ['wiki:read'], 'https://mcp.example/mcp', 'challenge', 'state');
        $this->assertSame('client', $parameters->clientIdentifier);
        $this->assertSame('https://client.example/callback', $parameters->redirectUri);
        $this->assertSame(['wiki:read'], $parameters->requestedScopes);
        $this->assertSame('https://mcp.example/mcp', $parameters->resource);
        $this->assertSame('challenge', $parameters->codeChallenge);
        $this->assertSame('state', $parameters->state);
    }
}
