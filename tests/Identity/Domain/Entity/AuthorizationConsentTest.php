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
use Source\Identity\Domain\ValueObject\AuthorizationTarget;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Identity\Infrastructure\Factory\AuthorizationConsentFactory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
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

    public function testDelegatedInitialTargetDoesNotAutomaticallyGrantOriginalAccount(): void
    {
        $consent = $this->consent([AuthorizationScope::WikiRead], 'delegated');
        $this->assertCount(1, $consent->targets());
        $this->assertNotEquals($consent->originalAccountIdentifier(), $consent->targets()[0]->accountIdentifier);
        $this->assertNotNull($consent->targets()[0]->delegationIdentifier);
        $this->assertSame($consent->initialAccountIdentifier(), $consent->targets()[0]->accountIdentifier);
    }

    /** @return array<string, array{string}> */
    public static function invalidTargets(): array
    {
        return ['duplicate' => ['duplicate'], 'delegation missing' => ['missing'], 'own account delegated' => ['own']];
    }

    #[DataProvider('invalidTargets')]
    public function testInvalidTargetStructureIsRejected(string $shape): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->consent([AuthorizationScope::WikiRead], $shape);
    }

    /** @param list<AuthorizationScope> $scopes */
    private function consent(array $scopes, string $shape = 'self'): AuthorizationConsent
    {
        $original = new AccountIdentifier('0199f700-0000-7000-8000-000000000003');
        $other = new AccountIdentifier('0199f700-0000-7000-8000-000000000005');
        $delegation = new DelegationIdentifier('0199f700-0000-7000-8000-000000000006');
        $targets = match ($shape) {
            'delegated' => [new AuthorizationTarget($other, $delegation)],
            'duplicate' => [new AuthorizationTarget($original), new AuthorizationTarget($original)],
            'missing' => [new AuthorizationTarget($other)],
            'own' => [new AuthorizationTarget($original, $delegation)],
            default => [new AuthorizationTarget($original)],
        };

        return new AuthorizationConsentFactory(new UuidGenerator())->create(
            new IdentityIdentifier('0199f700-0000-7000-8000-000000000002'),
            new OAuthClientIdentifier('0199f700-0000-4000-8000-000000000010'),
            $scopes,
            $targets,
            $original,
            new AccountPrincipalIdentifier('0199f700-0000-7000-8000-000000000004'),
            $targets[0]->accountIdentifier,
            'https://mcp.example.test/mcp',
            new DateTimeImmutable()
        );
    }

    public function testInitialTargetMustBelongToGrantedTargets(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AuthorizationConsentFactory(new UuidGenerator())->create(
            new IdentityIdentifier('0199f700-0000-7000-8000-000000000002'),
            new OAuthClientIdentifier('0199f700-0000-4000-8000-000000000010'),
            [AuthorizationScope::WikiRead],
            [],
            new AccountIdentifier('0199f700-0000-7000-8000-000000000003'),
            new AccountPrincipalIdentifier('0199f700-0000-7000-8000-000000000004'),
            new AccountIdentifier('0199f700-0000-7000-8000-000000000005'),
            'https://mcp.example.test/mcp',
            new DateTimeImmutable(),
        );
    }
}
