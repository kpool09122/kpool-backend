<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\Entity\AuthorizationConsent;
use Source\Identity\Domain\ValueObject\AccountPrincipalIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Identity\Infrastructure\Factory\AuthorizationConsentFactory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Infrastructure\Service\Uuid\UuidGenerator;

class AuthorizationConsentTest extends TestCase
{
    /** @return array<string, array{list<AuthorizationScope>}> */
    public static function invalidScopes(): array
    {
        return ['empty' => [[]], 'duplicate' => [[AuthorizationScope::WikiRead, AuthorizationScope::WikiRead]]];
    }

    /** @param list<AuthorizationScope> $scopes */
    #[DataProvider('invalidScopes')]
    public function testInvalidConsentScopesAreRejected(array $scopes): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->consent($scopes);
    }

    /** @param list<AuthorizationScope> $scopes */
    private function consent(array $scopes): AuthorizationConsent
    {
        $original = new AccountIdentifier('0199f700-0000-7000-8000-000000000003');

        return new AuthorizationConsentFactory(new UuidGenerator())->create(
            new IdentityIdentifier('0199f700-0000-7000-8000-000000000002'),
            new OAuthClientIdentifier('0199f700-0000-4000-8000-000000000010'),
            $scopes,
            $original,
            new AccountPrincipalIdentifier('0199f700-0000-7000-8000-000000000004'),
            'https://mcp.example.test/mcp',
            new DateTimeImmutable()
        );
    }
}
