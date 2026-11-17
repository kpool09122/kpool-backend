<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Repository;

use PHPUnit\Framework\Attributes\Group;
use Source\Identity\Domain\Repository\AuthorizationConnectionRepositoryInterface;
use Source\Identity\Domain\ValueObject\AuthorizationConnectionIdentifier;
use Source\Identity\Infrastructure\Repository\AuthorizationConnectionRepository;
use Tests\Helper\CreateAuthorizationConnection;
use Tests\TestCase;

class AuthorizationConnectionRepositoryTest extends TestCase
{
    public function testItIsBound(): void
    {
        $this->assertInstanceOf(AuthorizationConnectionRepository::class, $this->app()->make(AuthorizationConnectionRepositoryInterface::class));
    }

    #[Group('useDb')]
    public function testItRestoresConnectionAndRevokesWithoutChangingItsGrants(): void
    {
        $connection = CreateAuthorizationConnection::create();
        $repository = $this->app()->make(AuthorizationConnectionRepositoryInterface::class);
        $restored = $repository->find($connection->identifier());
        $this->assertNotNull($restored);
        $this->assertEquals($connection->identifier(), $restored->identifier());
        $this->assertEquals($connection->identityIdentifier(), $restored->identityIdentifier());
        $this->assertEquals($connection->clientIdentifier(), $restored->clientIdentifier());
        $this->assertSame($connection->scopes(), $restored->scopes());
        $this->assertEquals($connection->originalAccountIdentifier(), $restored->originalAccountIdentifier());
        $this->assertEquals($connection->originalPrincipalIdentifier(), $restored->originalPrincipalIdentifier());
        $this->assertSame($connection->resource(), $restored->resource());
        $this->assertSame($connection->consentedAt()->getTimestamp(), $restored->consentedAt()->getTimestamp());
        $this->assertNull($restored->revokedAt());
        $revokedAt = $restored->consentedAt()->modify('+1 minute');
        $restored->revoke($revokedAt);
        $repository->save($restored);
        $revoked = $repository->find($connection->identifier());
        $this->assertEquals($restored, $revoked);

        $restored->revoke($revokedAt->modify('+1 minute'));
        $this->assertSame($revokedAt, $restored->revokedAt());
        $repository->save($restored);

        $this->assertEquals($revoked, $repository->find($connection->identifier()));
        $this->assertNull($repository->find(new AuthorizationConnectionIdentifier('0199f700-0000-7000-8000-000000000099')));
    }
}
