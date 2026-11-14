<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use Source\Identity\Domain\Entity\AuthorizationConnection;
use Source\Identity\Domain\Repository\AuthorizationConnectionRepositoryInterface;
use Source\Identity\Domain\ValueObject\AuthorizationTarget;
use Source\Identity\Infrastructure\Repository\AuthorizationConnectionRepository;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Tests\Helper\CreateAuthorizationConnection;
use Tests\TestCase;

class AuthorizationConnectionRepositoryTest extends TestCase
{
    #[Group('useDb')]
    public function testDelegationTargetIsPersistedWithoutAddingTheOriginalAccount(): void
    {
        $first = CreateAuthorizationConnection::create();
        $account = new AccountIdentifier((string) Str::uuid7());
        $delegation = new DelegationIdentifier((string) Str::uuid7());
        $connection = new AuthorizationConnection(
            (string) Str::uuid7(),
            $first->identityIdentifier,
            $first->clientIdentifier,
            $first->scopes,
            [new AuthorizationTarget($account, $delegation)],
            $first->originalAccountIdentifier,
            $first->originalPrincipalIdentifier,
            $account,
            $first->resource,
            new DateTimeImmutable()
        );
        $repository = $this->app()->make(AuthorizationConnectionRepositoryInterface::class);
        $repository->save($connection);
        $found = $repository->find($connection->identifier);
        $this->assertNotNull($found);
        $this->assertCount(1, $found->targets);
        $this->assertEquals($account, $found->targets[0]->accountIdentifier);
        $this->assertEquals($delegation, $found->targets[0]->delegationIdentifier);
        $this->assertEquals($account, $found->initialAccountIdentifier);
    }

    public function testItIsBound(): void
    {
        $this->assertInstanceOf(AuthorizationConnectionRepository::class, $this->app()->make(AuthorizationConnectionRepositoryInterface::class));
    }

    #[Group('useDb')]
    public function testItRestoresConsentAndRevokesWithoutChangingItsGrants(): void
    {
        $connection = CreateAuthorizationConnection::create();
        $repository = $this->app()->make(AuthorizationConnectionRepositoryInterface::class);
        $restored = $repository->find($connection->identifier);
        $this->assertNotNull($restored);
        $this->assertSame($connection->identifier, $restored->identifier);
        $this->assertEquals($connection->identityIdentifier, $restored->identityIdentifier);
        $this->assertSame($connection->clientIdentifier, $restored->clientIdentifier);
        $this->assertSame($connection->scopes, $restored->scopes);
        $this->assertEquals($connection->targets, $restored->targets);
        $this->assertEquals($connection->originalAccountIdentifier, $restored->originalAccountIdentifier);
        $this->assertEquals($connection->originalPrincipalIdentifier, $restored->originalPrincipalIdentifier);
        $this->assertEquals($connection->initialAccountIdentifier, $restored->initialAccountIdentifier);
        $this->assertSame($connection->resource, $restored->resource);
        $this->assertSame($connection->consentedAt->getTimestamp(), $restored->consentedAt->getTimestamp());
        $this->assertNull($restored->revokedAt);
        $repository->revoke($connection->identifier);
        $revoked = $repository->find($connection->identifier);
        $this->assertNotNull($revoked);
        $this->assertNotNull($revoked->revokedAt);
        $repository->revoke($connection->identifier);
        $this->assertEquals($revoked, $repository->find($connection->identifier));
        $this->assertNull($repository->find('0199f700-0000-7000-8000-000000000099'));
    }
}
