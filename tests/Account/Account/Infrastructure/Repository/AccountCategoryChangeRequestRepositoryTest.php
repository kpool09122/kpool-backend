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
        $repository = $this->app->make(AccountCategoryChangeRequestRepositoryInterface::class);

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

        $repository = $this->app->make(AccountCategoryChangeRequestRepositoryInterface::class);
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
    public function testFindPendingByAccountId(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $requestId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId, ['type' => 'individual']);

        $repository = $this->app->make(AccountCategoryChangeRequestRepositoryInterface::class);
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

        $found = $repository->findPendingByAccountId(new AccountIdentifier($accountId));

        $this->assertNotNull($found);
        $this->assertSame($requestId, (string) $found->requestIdentifier());
    }
}
