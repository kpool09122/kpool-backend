<?php

declare(strict_types=1);

namespace Tests\Http\Context;

use Application\Http\Context\SiteManagementPrincipalResolver;
use Mockery;
use Mockery\MockInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Principal\Domain\Entity\Principal;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Tests\Helper\CreateAccountContext;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SiteManagementPrincipalResolverTest extends TestCase
{
    public function testResolvesFromAuthenticatedIdentity(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $identifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        /** @var PrincipalRepositoryInterface&MockInterface $repository */
        $repository = Mockery::mock(PrincipalRepositoryInterface::class);
        $repository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()->with($identity, $accountIdentifier)->andReturn(new Principal($identifier, $identity, $accountIdentifier));
        $resolver = new SiteManagementPrincipalResolver($repository);

        $this->assertSame($identifier, $resolver->resolve(CreateAccountContext::create($identity, $accountIdentifier)));
    }

    public function testRejectsIdentityWithoutPrincipal(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        /** @var PrincipalRepositoryInterface&MockInterface $repository */
        $repository = Mockery::mock(PrincipalRepositoryInterface::class);
        $repository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()->with($identity, $accountIdentifier)->andReturnNull();
        $this->expectException(UnauthorizedException::class);

        (new SiteManagementPrincipalResolver($repository))->resolve(CreateAccountContext::create($identity, $accountIdentifier));
    }
}
