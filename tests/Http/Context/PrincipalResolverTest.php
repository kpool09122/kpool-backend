<?php

declare(strict_types=1);

namespace Tests\Http\Context;

use Application\Http\Context\PrincipalResolver;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PrincipalResolverTest extends TestCase
{
    public function testResolveReturnsPrincipalIdentifier(): void
    {
        $identityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $principalId = new PrincipalIdentifier(StrTestHelper::generateUuid());

        /** @var Principal&Mockery\MockInterface $principal */
        $principal = Mockery::mock(Principal::class);
        $principal->shouldReceive('principalIdentifier')->once()->andReturn($principalId);

        /** @var PrincipalRepositoryInterface&Mockery\MockInterface $repository */
        $repository = Mockery::mock(PrincipalRepositoryInterface::class);
        $repository->shouldReceive('findByIdentityIdentifier')
            ->once()
            ->with($identityId)
            ->andReturn($principal);

        $resolver = new PrincipalResolver($repository);
        $result = $resolver->resolve($identityId);

        $this->assertSame($principalId, $result);
    }

    public function testResolveThrowsPrincipalNotFoundExceptionWhenNotFound(): void
    {
        $identityId = new IdentityIdentifier(StrTestHelper::generateUuid());

        /** @var PrincipalRepositoryInterface&Mockery\MockInterface $repository */
        $repository = Mockery::mock(PrincipalRepositoryInterface::class);
        $repository->shouldReceive('findByIdentityIdentifier')
            ->once()
            ->with($identityId)
            ->andReturn(null);

        $resolver = new PrincipalResolver($repository);

        $this->expectException(PrincipalNotFoundException::class);
        $resolver->resolve($identityId);
    }
}
