<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Repository;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Domain\Entity\AccountCategoryChangeRequest;
use Source\Account\Account\Domain\Repository\AccountCategoryChangeRequestRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestStatus;
use Source\Account\Account\Infrastructure\Repository\AccountCategoryChangeRequestRepository;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AccountCategoryChangeRequestRepositoryTest extends TestCase
{
    public function test__construct(): void
    {
        $repository = $this->app()->make(AccountCategoryChangeRequestRepositoryInterface::class);

        $this->assertInstanceOf(AccountCategoryChangeRequestRepository::class, $repository);
    }

    #[Group('useDb')]
    public function testSaveAndFindById(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $requestId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId, ['type' => 'individual']);

        $request = new AccountCategoryChangeRequest(
            new AccountCategoryChangeRequestIdentifier($requestId),
            new AccountIdentifier($accountId),
            AccountCategory::GENERAL,
            AccountCategory::AGENCY,
            AccountCategoryChangeRequestStatus::PENDING,
            new DateTimeImmutable('2026-08-11 00:00:00'),
            null,
            null,
            null,
        );

        $repository = $this->app()->make(AccountCategoryChangeRequestRepositoryInterface::class);
        $repository->save($request);
        $found = $repository->findById(new AccountCategoryChangeRequestIdentifier($requestId));

        $this->assertDatabaseHas('account_category_change_requests', [
            'id' => $requestId,
            'account_id' => $accountId,
            'current_account_category' => 'general',
            'requested_account_category' => 'agency',
            'status' => 'pending',
        ]);
        $this->assertNotNull($found);
        $this->assertSame($requestId, (string) $found->requestIdentifier());
        $this->assertSame(AccountCategory::GENERAL, $found->currentAccountCategory());
        $this->assertSame(AccountCategory::AGENCY, $found->requestedAccountCategory());
        $this->assertSame(AccountCategoryChangeRequestStatus::PENDING, $found->status());
    }

    #[Group('useDb')]
    public function testFindByAccountIdAndStatuses(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $requestId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId, ['type' => 'individual']);

        $repository = $this->app()->make(AccountCategoryChangeRequestRepositoryInterface::class);
        $repository->save(new AccountCategoryChangeRequest(
            new AccountCategoryChangeRequestIdentifier($requestId),
            new AccountIdentifier($accountId),
            AccountCategory::GENERAL,
            AccountCategory::AGENCY,
            AccountCategoryChangeRequestStatus::PENDING,
            new DateTimeImmutable(),
            null,
            null,
            null,
        ));

        $found = $repository->findByAccountIdAndStatuses(new AccountIdentifier($accountId), [AccountCategoryChangeRequestStatus::PENDING]);

        $this->assertCount(1, $found);
        $this->assertSame($requestId, (string) $found[0]->requestIdentifier());
    }

    #[Group('useDb')]
    public function testWithdrawnRoundTripPreservesHistoryAndReleasesPendingSlot(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountId);
        $repository = new AccountCategoryChangeRequestRepository();
        $request = new AccountCategoryChangeRequest(new AccountCategoryChangeRequestIdentifier(StrTestHelper::generateUuid()), $accountId, AccountCategory::GENERAL, AccountCategory::AGENCY, AccountCategoryChangeRequestStatus::PENDING, new DateTimeImmutable('2026-08-11'), null, null, null);
        $repository->save($request);
        $request->withdraw();
        $repository->save($request);
        $found = $repository->findById($request->requestIdentifier());
        $this->assertNotNull($found);
        $this->assertSame(AccountCategoryChangeRequestStatus::WITHDRAWN, $found->status());
        $this->assertEquals($request->requestedAt(), $found->requestedAt());
        $this->assertNull($found->reviewedBy());
        $this->assertNull($found->reviewedAt());
        $this->assertNull($found->rejectionReason());
        $this->assertSame([], $repository->findByAccountIdAndStatuses($accountId, [AccountCategoryChangeRequestStatus::PENDING]));
        $this->assertSame([], $repository->findByAccountIdAndStatuses($accountId, [AccountCategoryChangeRequestStatus::APPROVED]));
        $next = new AccountCategoryChangeRequest(new AccountCategoryChangeRequestIdentifier(StrTestHelper::generateUuid()), $accountId, AccountCategory::GENERAL, AccountCategory::TALENT, AccountCategoryChangeRequestStatus::PENDING, new DateTimeImmutable(), null, null, null);
        $repository->save($next);
        $this->assertDatabaseHas('accounts', ['id' => (string) $accountId, 'category' => 'general']);
        $this->assertDatabaseCount('account_category_change_requests', 2);
    }

    #[Group('useDb')]
    public function testFindApprovedRequestChecksAllHistoryAndAccountScope(): void
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountId);
        $repository = new AccountCategoryChangeRequestRepository();
        foreach ([AccountCategoryChangeRequestStatus::REJECTED, AccountCategoryChangeRequestStatus::WITHDRAWN, AccountCategoryChangeRequestStatus::PENDING] as $status) {
            $repository->save(new AccountCategoryChangeRequest(new AccountCategoryChangeRequestIdentifier(StrTestHelper::generateUuid()), $accountId, AccountCategory::GENERAL, AccountCategory::AGENCY, $status, new DateTimeImmutable('2026-08-12'), null, null, null));
        }
        $this->assertSame([], $repository->findByAccountIdAndStatuses($accountId, [AccountCategoryChangeRequestStatus::APPROVED]));
        $repository->save(new AccountCategoryChangeRequest(new AccountCategoryChangeRequestIdentifier(StrTestHelper::generateUuid()), $accountId, AccountCategory::GENERAL, AccountCategory::AGENCY, AccountCategoryChangeRequestStatus::APPROVED, new DateTimeImmutable('2026-08-11'), null, null, null));
        $found = $repository->findByAccountIdAndStatuses($accountId, [AccountCategoryChangeRequestStatus::APPROVED]);
        $this->assertCount(1, $found);
        $this->assertSame(AccountCategoryChangeRequestStatus::APPROVED, $found[0]->status());
        $this->assertSame((string) $accountId, (string) $found[0]->accountIdentifier());
        $requests = $repository->findByAccountIdAndStatuses($accountId, [AccountCategoryChangeRequestStatus::APPROVED, AccountCategoryChangeRequestStatus::PENDING]);
        $this->assertCount(2, $requests);
        $this->assertEqualsCanonicalizing([AccountCategoryChangeRequestStatus::APPROVED, AccountCategoryChangeRequestStatus::PENDING], array_map(static fn (AccountCategoryChangeRequest $request): AccountCategoryChangeRequestStatus => $request->status(), $requests));
        $this->assertSame([], $repository->findByAccountIdAndStatuses($accountId, []));
        $this->assertSame([], $repository->findByAccountIdAndStatuses(new AccountIdentifier(StrTestHelper::generateUuid()), [AccountCategoryChangeRequestStatus::APPROVED]));
    }
}
