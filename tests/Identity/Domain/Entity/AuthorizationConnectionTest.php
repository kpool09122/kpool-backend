<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Identity\Domain\Entity\AuthorizationConnection;
use Source\Identity\Domain\ValueObject\AuthorizationTarget;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class AuthorizationConnectionTest extends TestCase
{
    /** @return array<string, array{list<string>}> */
    public static function invalidScopes(): array
    {
        return ['empty' => [[]], 'unknown' => [['unknown']], 'wildcard' => [['*']], 'duplicate' => [['wiki:read', 'wiki:read']]];
    }

    /** @param list<string> $scopes */
    #[DataProvider('invalidScopes')]
    public function testInvalidConsentScopesAreRejected(array $scopes): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->connection($scopes);
    }

    public function testDelegatedInitialTargetDoesNotAutomaticallyGrantOriginalAccount(): void
    {
        $connection = $this->connection(['wiki:read'], 'delegated');
        $this->assertCount(1, $connection->targets);
        $this->assertNotEquals($connection->originalAccountIdentifier, $connection->targets[0]->accountIdentifier);
        $this->assertNotNull($connection->targets[0]->delegationIdentifier);
        $this->assertSame($connection->initialAccountIdentifier, $connection->targets[0]->accountIdentifier);
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
        $this->connection(['wiki:read'], $shape);
    }

    /** @param list<string> $scopes */
    private function connection(array $scopes, string $shape = 'self'): AuthorizationConnection
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

        return new AuthorizationConnection(
            '0199f700-0000-7000-8000-000000000001',
            new IdentityIdentifier('0199f700-0000-7000-8000-000000000002'),
            'client',
            $scopes,
            $targets,
            $original,
            new PrincipalIdentifier('0199f700-0000-7000-8000-000000000004'),
            $targets[0]->accountIdentifier,
            'https://mcp.example.test/mcp',
            new DateTimeImmutable()
        );
    }

    public function testInitialTargetMustBelongToGrantedTargets(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AuthorizationConnection(
            '0199f700-0000-7000-8000-000000000001',
            new IdentityIdentifier('0199f700-0000-7000-8000-000000000002'),
            'client',
            ['wiki:read'],
            [],
            new AccountIdentifier('0199f700-0000-7000-8000-000000000003'),
            new PrincipalIdentifier('0199f700-0000-7000-8000-000000000004'),
            new AccountIdentifier('0199f700-0000-7000-8000-000000000005'),
            'https://mcp.example.test/mcp',
            new DateTimeImmutable(),
        );
    }
}
