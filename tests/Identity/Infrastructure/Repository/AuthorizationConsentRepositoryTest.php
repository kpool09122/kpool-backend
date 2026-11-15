<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use Source\Identity\Domain\Factory\AuthorizationConsentFactoryInterface;
use Source\Identity\Domain\Repository\AuthorizationConsentRepositoryInterface;
use Source\Identity\Domain\ValueObject\AuthorizationConsentIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationTarget;
use Source\Identity\Infrastructure\Repository\AuthorizationConsentRepository;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Tests\Helper\CreateAuthorizationConsent;
use Tests\TestCase;

class AuthorizationConsentRepositoryTest extends TestCase
{
    #[Group('useDb')]
    public function testDelegationTargetIsPersistedWithoutAddingTheOriginalAccount(): void
    {
        $first = CreateAuthorizationConsent::create();
        $account = new AccountIdentifier((string) Str::uuid7());
        $delegation = new DelegationIdentifier((string) Str::uuid7());
        $consent = $this->app()->make(AuthorizationConsentFactoryInterface::class)->create(
            $first->identityIdentifier(),
            $first->clientIdentifier(),
            $first->scopes(),
            [new AuthorizationTarget($account, $delegation)],
            $first->originalAccountIdentifier(),
            $first->originalPrincipalIdentifier(),
            $account,
            $first->resource(),
            new DateTimeImmutable()
        );
        $repository = $this->app()->make(AuthorizationConsentRepositoryInterface::class);
        $repository->save($consent);
        $found = $repository->find($consent->identifier());
        $this->assertNotNull($found);
        $this->assertCount(1, $found->targets());
        $this->assertEquals($account, $found->targets()[0]->accountIdentifier);
        $this->assertEquals($delegation, $found->targets()[0]->delegationIdentifier);
        $this->assertEquals($account, $found->initialAccountIdentifier());
    }

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
        $this->assertEquals($consent->targets(), $restored->targets());
        $this->assertEquals($consent->originalAccountIdentifier(), $restored->originalAccountIdentifier());
        $this->assertEquals($consent->originalPrincipalIdentifier(), $restored->originalPrincipalIdentifier());
        $this->assertEquals($consent->initialAccountIdentifier(), $restored->initialAccountIdentifier());
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
