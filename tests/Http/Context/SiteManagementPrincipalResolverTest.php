<?php

declare(strict_types=1);

namespace Tests\Http\Context;

use Application\Http\Context\ActorContext;
use Application\Http\Context\SiteManagementPrincipalResolver;
use Mockery;
use Mockery\MockInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Principal\Domain\Entity\Principal;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SiteManagementPrincipalResolverTest extends TestCase
{
    public function testResolvesFromAuthenticatedIdentity(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $identifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        /** @var PrincipalRepositoryInterface&MockInterface $repository */
        $repository = Mockery::mock(PrincipalRepositoryInterface::class);
        $repository->shouldReceive('findByIdentityId')->once()->with($identity)->andReturn(new Principal($identifier, $identity));
        $resolver = new SiteManagementPrincipalResolver($repository);

        $this->assertSame($identifier, $resolver->resolve(new ActorContext($identity, Language::JAPANESE)));
    }

    public function testRejectsIdentityWithoutPrincipal(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        /** @var PrincipalRepositoryInterface&MockInterface $repository */
        $repository = Mockery::mock(PrincipalRepositoryInterface::class);
        $repository->shouldReceive('findByIdentityId')->once()->with($identity)->andReturnNull();
        $this->expectException(UnauthorizedException::class);

        (new SiteManagementPrincipalResolver($repository))->resolve(new ActorContext($identity, Language::JAPANESE));
    }
}
