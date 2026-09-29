<?php

declare(strict_types=1);

namespace Tests\Account\Delegation\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Delegation\Domain\Entity\Delegation;
use Source\Account\Delegation\Domain\Exception\DelegationAlreadyExistsException;
use Source\Account\Delegation\Domain\ValueObject\DelegationDirection;
use Source\Account\Delegation\Domain\ValueObject\DelegationStatus;
use Source\Account\Delegation\Infrastructure\Repository\DelegationRepository;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DelegationRepositoryTest extends TestCase
{
    #[Group('useDb')]
    public function testPersistsAccountIdentifiersWithoutTimestamps(): void
    {
        $delegation = $this->delegation();
        $repository = new DelegationRepository();
        $repository->save($delegation);

        $persisted = $repository->findOpenByAffiliationId($delegation->affiliationIdentifier());

        $this->assertNotNull($persisted);
        $this->assertSame((string) $delegation->delegationIdentifier(), (string) $persisted->delegationIdentifier());
        $this->assertDatabaseHas('account_delegations', [
            'id' => (string) $delegation->delegationIdentifier(),
            'delegate_account_id' => (string) $delegation->delegateAccountIdentifier(),
            'delegator_account_id' => (string) $delegation->delegatorAccountIdentifier(),
            'requested_by_account_id' => (string) $delegation->requestedByAccountIdentifier(),
            'status' => 'pending',
        ]);
        $this->assertFalse(DB::getSchemaBuilder()->hasColumn('account_delegations', 'created_at'));
        $this->assertFalse(DB::getSchemaBuilder()->hasColumn('account_delegations', 'updated_at'));
    }

    #[Group('useDb')]
    public function testDatabaseConstraintRejectsConcurrentOpenDelegation(): void
    {
        $first = $this->delegation();
        $repository = new DelegationRepository();
        $repository->save($first);
        $second = $this->delegation($first->affiliationIdentifier());

        $this->expectException(DelegationAlreadyExistsException::class);
        $repository->save($second);
    }

    #[Group('useDb')]
    public function testApprovedDelegationAlsoBlocksAnotherOpenRequest(): void
    {
        $first = $this->delegation(status: DelegationStatus::APPROVED);
        $repository = new DelegationRepository();
        $repository->save($first);

        $this->expectException(DelegationAlreadyExistsException::class);
        $repository->save($this->delegation($first->affiliationIdentifier()));
    }

    #[Group('useDb')]
    public function testRejectedDelegationAllowsANewRequest(): void
    {
        $first = $this->delegation(status: DelegationStatus::REJECTED);
        $repository = new DelegationRepository();
        $repository->save($first);
        $repository->save($this->delegation($first->affiliationIdentifier()));

        $persisted = $repository->findOpenByAffiliationId($first->affiliationIdentifier());

        $this->assertNotNull($persisted);
        $this->assertNotSame((string) $first->delegationIdentifier(), (string) $persisted->delegationIdentifier());
    }

    #[Group('useDb')]
    public function testRejectedPendingDelegationAllowsANewRequest(): void
    {
        $rejected = $this->delegation();
        $repository = new DelegationRepository();
        $repository->save($rejected);

        $found = $repository->findById($rejected->delegationIdentifier());
        $this->assertNotNull($found);
        $found->reject();
        $repository->save($found);
        $repository->save($this->delegation($rejected->affiliationIdentifier()));

        $this->assertNotNull($repository->findOpenByAffiliationId($rejected->affiliationIdentifier()));
        $this->assertDatabaseHas('account_delegations', [
            'id' => (string) $rejected->delegationIdentifier(),
            'status' => 'rejected',
        ]);
    }

    private function delegation(
        ?AffiliationIdentifier $affiliationIdentifier = null,
        DelegationStatus $status = DelegationStatus::PENDING,
    ): Delegation {
        $agency = new AccountIdentifier(StrTestHelper::generateUuid());
        $talent = new AccountIdentifier(StrTestHelper::generateUuid());

        return new Delegation(
            new DelegationIdentifier(StrTestHelper::generateUuid()),
            $affiliationIdentifier ?? new AffiliationIdentifier(StrTestHelper::generateUuid()),
            $agency,
            $talent,
            $agency,
            $status,
            DelegationDirection::FROM_AGENCY,
            new DateTimeImmutable(),
            null,
            null,
        );
    }
}
