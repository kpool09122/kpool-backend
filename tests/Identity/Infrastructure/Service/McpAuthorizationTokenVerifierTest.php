<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use Source\Identity\Application\Exception\InvalidAuthorizationTokenException;
use Source\Identity\Application\Service\AuthorizationTokenVerifierInterface;
use Source\Identity\Infrastructure\Service\McpAuthorizationTokenVerifier;
use Tests\TestCase;

class McpAuthorizationTokenVerifierTest extends TestCase
{
    public function testItRejectsAnEmptyBearerToken(): void
    {
        $verifier = $this->app()->make(AuthorizationTokenVerifierInterface::class);
        self::assertInstanceOf(McpAuthorizationTokenVerifier::class, $verifier);

        $this->expectException(InvalidAuthorizationTokenException::class);

        $verifier->verify('');
    }
}
