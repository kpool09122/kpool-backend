<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Repository;

use PHPUnit\Framework\Attributes\Group;
use Source\Identity\Domain\Repository\AuthorizationConsentRepositoryInterface;
use Source\Identity\Domain\ValueObject\AuthorizationConsentIdentifier;
use Source\Identity\Infrastructure\Repository\AuthorizationConsentRepository;
use Tests\Helper\CreateAuthorizationConsent;
use Tests\TestCase;

class AuthorizationConsentRepositoryTest extends TestCase
{
    public function testItIsBound(): void
    {
        $this->assertInstanceOf(AuthorizationConsentRepository::class, $this->app()->make(AuthorizationConsentRepositoryInterface::class));
    }

    #[Group('useDb')]
    public function testItRestoresConsentAndRevokesWithoutChangingItsGrants(): void
    {
        $consent = CreateAuthorizationConsent::create();
        $repository = $this->app()->make(AuthorizationConsentRepositoryInterface::class);
        $restored = $repository->find($consent->identifier());
        $this->assertNotNull($restored);
        $this->assertEquals($consent->identifier(), $restored->identifier());
        $this->assertEquals($consent->identityIdentifier(), $restored->identityIdentifier());
        $this->assertEquals($consent->clientIdentifier(), $restored->clientIdentifier());
        $this->assertSame($consent->scopes(), $restored->scopes());
        $this->assertEquals($consent->originalAccountIdentifier(), $restored->originalAccountIdentifier());
        $this->assertEquals($consent->originalPrincipalIdentifier(), $restored->originalPrincipalIdentifier());
        $this->assertSame($consent->resource(), $restored->resource());
        $this->assertSame($consent->consentedAt()->getTimestamp(), $restored->consentedAt()->getTimestamp());
        $this->assertNull($restored->revokedAt());
        $revokedAt = $restored->consentedAt()->modify('+1 minute');
        $restored->revoke($revokedAt);
        $repository->save($restored);
        $revoked = $repository->find($consent->identifier());
        $this->assertEquals($restored, $revoked);

        $restored->revoke($revokedAt->modify('+1 minute'));
        $this->assertSame($revokedAt, $restored->revokedAt());
        $repository->save($restored);

        $this->assertEquals($revoked, $repository->find($consent->identifier()));
        $this->assertNull($repository->find(new AuthorizationConsentIdentifier('0199f700-0000-7000-8000-000000000099')));
    }
}
