<?php

declare(strict_types=1);

namespace Tests\Identity\Application\Service;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Identity\Application\Service\ValidatedAuthorizationToken;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\AuthorizationTarget;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Identity\Infrastructure\Factory\AuthorizationConsentFactory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Infrastructure\Service\Uuid\UuidGenerator;

class ValidatedAuthorizationTokenTest extends TestCase
{
    public function testItPreservesVerifiedTokenAndConsent(): void
    {
        $account = new AccountIdentifier('0199f700-0000-7000-8000-000000000001');
        $consent = new AuthorizationConsentFactory(new UuidGenerator())->create(
            new IdentityIdentifier('0199f700-0000-7000-8000-000000000003'),
            new OAuthClientIdentifier('0199f700-0000-4000-8000-000000000010'),
            [AuthorizationScope::WikiRead],
            [new AuthorizationTarget($account)],
            $account,
            new PrincipalIdentifier('0199f700-0000-7000-8000-000000000004'),
            $account,
            'https://mcp.example/mcp',
            new DateTimeImmutable()
        );
        $expires = new DateTimeImmutable('+1 hour');
        $token = new ValidatedAuthorizationToken('token', $consent, [AuthorizationScope::WikiRead], $expires);
        $this->assertSame('token', $token->tokenIdentifier);
        $this->assertSame($consent, $token->consent);
        $this->assertSame([AuthorizationScope::WikiRead], $token->scopes);
        $this->assertSame($expires, $token->expiresAt);
    }
}
