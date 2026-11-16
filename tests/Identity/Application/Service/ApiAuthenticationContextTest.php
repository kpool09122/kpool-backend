<?php

declare(strict_types=1);

namespace Tests\Identity\Application\Service;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Identity\Application\Service\ApiAuthenticationContext;
use Source\Identity\Application\Service\ValidatedAuthorizationToken;
use Source\Identity\Domain\ValueObject\AccountPrincipalIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Identity\Infrastructure\Factory\AuthorizationConsentFactory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Infrastructure\Service\Uuid\UuidGenerator;

class ApiAuthenticationContextTest extends TestCase
{
    public function testItSnapshotsVerifiedScopesAndTheOriginalConsentIdentityAndAccounts(): void
    {
        $account = new AccountIdentifier('0199f700-0000-7000-8000-000000000001');
        $consent = new AuthorizationConsentFactory(new UuidGenerator())->create(
            new IdentityIdentifier('0199f700-0000-7000-8000-000000000003'),
            new OAuthClientIdentifier('0199f700-0000-4000-8000-000000000010'),
            [AuthorizationScope::WikiRead],
            $account,
            new AccountPrincipalIdentifier('0199f700-0000-7000-8000-000000000004'),
            'https://mcp.example/mcp',
            new DateTimeImmutable()
        );
        $expires = new DateTimeImmutable('+1 hour');
        $token = new ValidatedAuthorizationToken('token', $consent, [AuthorizationScope::WikiRead], $expires);
        $context = new ApiAuthenticationContext($token, $expires);
        self::assertSame('token', $context->sourceTokenIdentifier());
        self::assertSame($consent->identifier(), $context->consentIdentifier());
        self::assertSame($consent->identityIdentifier(), $context->identityIdentifier());
        self::assertSame($consent->clientIdentifier(), $context->clientIdentifier());
        self::assertSame($token->scopes, $context->scopes());
        self::assertSame($consent->originalAccountIdentifier(), $context->originalAccountIdentifier());
        self::assertSame($consent->originalPrincipalIdentifier(), $context->originalPrincipalIdentifier());
        self::assertSame($expires, $context->expiresAt());
    }
}
