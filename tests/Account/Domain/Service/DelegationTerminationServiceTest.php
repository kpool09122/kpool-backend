<?php

declare(strict_types=1);

namespace Tests\Account\Domain\Service;

use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use Source\Account\Domain\Entity\OperationDelegation;
use Source\Account\Domain\Repository\DelegationRepositoryInterface;
use Source\Account\Domain\Service\DelegationTerminationServiceInterface;
use Source\Account\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Domain\ValueObject\DelegationDirection;
use Source\Account\Domain\ValueObject\DelegationStatus;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DelegationTerminationServiceTest extends TestCase
{
    public function testRevokeAllDelegations(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());

        $delegation1 = $this->createApprovedDelegation($affiliationIdentifier);
        $delegation2 = $this->createApprovedDelegation($affiliationIdentifier);

        /** @var DelegationRepositoryInterface&MockInterface $repository */
        $repository = Mockery::mock(DelegationRepositoryInterface::class);
        $repository->shouldReceive('findApprovedByAffiliation')
            ->once()
            ->with($affiliationIdentifier)
            ->andReturn([$delegation1, $delegation2]);
        $repository->shouldReceive('save')
            ->twice();

        $this->app->instance(DelegationRepositoryInterface::class, $repository);
        $service = $this->app->make(DelegationTerminationServiceInterface::class);
        $revokedCount = $service->revokeAllDelegations($affiliationIdentifier);

        $this->assertSame(2, $revokedCount);
        $this->assertTrue($delegation1->isRevoked());
        $this->assertTrue($delegation2->isRevoked());
    }

    public function testRevokeAllDelegationsWithNoDelegations(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());

        /** @var DelegationRepositoryInterface&MockInterface $repository */
        $repository = Mockery::mock(DelegationRepositoryInterface::class);
        $repository->shouldReceive('findApprovedByAffiliation')
            ->once()
            ->with($affiliationIdentifier)
            ->andReturn([]);
        $repository->shouldNotReceive('save');

        $this->app->instance(DelegationRepositoryInterface::class, $repository);
        $service = $this->app->make(DelegationTerminationServiceInterface::class);
        $revokedCount = $service->revokeAllDelegations($affiliationIdentifier);

        $this->assertSame(0, $revokedCount);
    }

    private function createApprovedDelegation(AffiliationIdentifier $affiliationIdentifier): OperationDelegation
    {
        return new OperationDelegation(
            new DelegationIdentifier(StrTestHelper::generateUuid()),
            $affiliationIdentifier,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            DelegationStatus::APPROVED,
            DelegationDirection::FROM_AGENCY,
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            null,
        );
    }
}
