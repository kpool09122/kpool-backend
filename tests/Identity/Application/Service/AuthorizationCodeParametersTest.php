<?php

declare(strict_types=1);

namespace Tests\Identity\Application\Service;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\Service\AuthorizationCodeParameters;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;

class AuthorizationCodeParametersTest extends TestCase
{
    public function testItPreservesTheRequestedParametersSeparatelyFromConnection(): void
    {
        $clientIdentifier = new OAuthClientIdentifier('0199f700-0000-7000-8000-000000000010');
        $parameters = new AuthorizationCodeParameters($clientIdentifier, 'https://client.example/callback', [AuthorizationScope::WikiRead], 'https://mcp.example/mcp', 'challenge', 'state');
        $this->assertSame($clientIdentifier, $parameters->clientIdentifier);
        $this->assertSame('https://client.example/callback', $parameters->redirectUri);
        $this->assertSame([AuthorizationScope::WikiRead], $parameters->requestedScopes);
        $this->assertSame('https://mcp.example/mcp', $parameters->resource);
        $this->assertSame('challenge', $parameters->codeChallenge);
        $this->assertSame('state', $parameters->state);
    }
}
