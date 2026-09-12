<?php

declare(strict_types=1);

namespace Tests\Account\Delegation\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Delegation\Domain\Entity\AccountDelegation;
use Source\Account\Delegation\Domain\Exception\AccountDelegationAlreadyExistsException;
use Source\Account\Delegation\Domain\ValueObject\DelegationDirection;
use Source\Account\Delegation\Domain\ValueObject\DelegationStatus;
use Source\Account\Delegation\Infrastructure\Repository\AccountDelegationRepository;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AccountDelegationRepositoryTest extends TestCase
{
    #[Group('useDb')]
    public function testPersistsAccountIdentifiersWithoutTimestamps(): void
    {
        $delegation = $this->delegation();
        $repository = new AccountDelegationRepository();
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
        $repository = new AccountDelegationRepository();
        $repository->save($first);
        $second = $this->delegation($first->affiliationIdentifier());

        $this->expectException(AccountDelegationAlreadyExistsException::class);
        $repository->save($second);
    }

    #[Group('useDb')]
    public function testApprovedDelegationAlsoBlocksAnotherOpenRequest(): void
    {
        $first = $this->delegation(status: DelegationStatus::APPROVED);
        $repository = new AccountDelegationRepository();
        $repository->save($first);

        $this->expectException(AccountDelegationAlreadyExistsException::class);
        $repository->save($this->delegation($first->affiliationIdentifier()));
    }

    #[Group('useDb')]
    public function testRevokedDelegationAllowsANewRequest(): void
    {
        $first = $this->delegation(status: DelegationStatus::REVOKED);
        $repository = new AccountDelegationRepository();
        $repository->save($first);
        $repository->save($this->delegation($first->affiliationIdentifier()));

        $persisted = $repository->findOpenByAffiliationId($first->affiliationIdentifier());

        $this->assertNotNull($persisted);
        $this->assertNotSame((string) $first->delegationIdentifier(), (string) $persisted->delegationIdentifier());
    }

    #[Group('useDb')]
    public function testDeletingRejectedPendingDelegationAllowsANewRequest(): void
    {
        $rejected = $this->delegation();
        $repository = new AccountDelegationRepository();
        $repository->save($rejected);

        $found = $repository->findById($rejected->delegationIdentifier());
        $this->assertNotNull($found);
        $repository->delete($found);
        $repository->save($this->delegation($rejected->affiliationIdentifier()));

        $this->assertNotNull($repository->findOpenByAffiliationId($rejected->affiliationIdentifier()));
    }

    private function delegation(
        ?AffiliationIdentifier $affiliationIdentifier = null,
        DelegationStatus $status = DelegationStatus::PENDING,
    ): AccountDelegation {
        $agency = new AccountIdentifier(StrTestHelper::generateUuid());
        $talent = new AccountIdentifier(StrTestHelper::generateUuid());

        return new AccountDelegation(
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
