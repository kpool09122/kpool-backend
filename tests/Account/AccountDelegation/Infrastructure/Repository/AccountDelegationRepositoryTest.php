<?php

declare(strict_types=1);

namespace Tests\Account\AccountDelegation\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\AccountDelegation\Domain\Entity\AccountDelegation;
use Source\Account\AccountDelegation\Domain\Exception\AccountDelegationAlreadyExistsException;
use Source\Account\AccountDelegation\Infrastructure\Repository\AccountDelegationRepository;
use Source\Account\Delegation\Domain\ValueObject\DelegationDirection;
use Source\Account\Delegation\Domain\ValueObject\DelegationStatus;
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

        $this->assertTrue($repository->existsOpenByAffiliation($delegation->affiliationIdentifier()));
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

        $this->assertTrue($repository->existsOpenByAffiliation($first->affiliationIdentifier()));
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
