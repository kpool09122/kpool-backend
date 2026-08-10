<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Repository;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Domain\Entity\AccountTypeChangeRequest;
use Source\Account\Account\Domain\Repository\AccountTypeChangeRequestRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\AccountTypeChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountTypeChangeRequestStatus;
use Source\Account\Account\Infrastructure\Repository\AccountTypeChangeRequestRepository;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AccountTypeChangeRequestRepositoryTest extends TestCase
{
    public function test__construct(): void
    {
        $repository = $this->app->make(AccountTypeChangeRequestRepositoryInterface::class);

        $this->assertInstanceOf(AccountTypeChangeRequestRepository::class, $repository);
    }

    #[Group('useDb')]
    public function testSaveAndFindById(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $requestId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId, ['type' => 'individual']);

        $request = new AccountTypeChangeRequest(
            new AccountTypeChangeRequestIdentifier($requestId),
            new AccountIdentifier($accountId),
            AccountType::INDIVIDUAL,
            AccountType::CORPORATION,
            AccountTypeChangeRequestStatus::PENDING,
            new DateTimeImmutable('2026-08-11 00:00:00'),
            null,
            null,
            null,
        );

        $repository = $this->app->make(AccountTypeChangeRequestRepositoryInterface::class);
        $repository->save($request);
        $found = $repository->findById(new AccountTypeChangeRequestIdentifier($requestId));

        $this->assertDatabaseHas('account_type_change_requests', [
            'id' => $requestId,
            'account_id' => $accountId,
            'current_account_type' => 'individual',
            'requested_account_type' => 'corporation',
            'status' => 'pending',
        ]);
        $this->assertNotNull($found);
        $this->assertSame($requestId, (string) $found->requestIdentifier());
        $this->assertSame(AccountType::INDIVIDUAL, $found->currentAccountType());
        $this->assertSame(AccountType::CORPORATION, $found->requestedAccountType());
        $this->assertSame(AccountTypeChangeRequestStatus::PENDING, $found->status());
    }

    #[Group('useDb')]
    public function testFindPendingByAccountIdAndExistsPending(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $requestId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId, ['type' => 'individual']);

        $repository = $this->app->make(AccountTypeChangeRequestRepositoryInterface::class);
        $repository->save(new AccountTypeChangeRequest(
            new AccountTypeChangeRequestIdentifier($requestId),
            new AccountIdentifier($accountId),
            AccountType::INDIVIDUAL,
            AccountType::CORPORATION,
            AccountTypeChangeRequestStatus::PENDING,
            new DateTimeImmutable(),
            null,
            null,
            null,
        ));

        $found = $repository->findPendingByAccountId(new AccountIdentifier($accountId));

        $this->assertTrue($repository->existsPending(new AccountIdentifier($accountId)));
        $this->assertNotNull($found);
        $this->assertSame($requestId, (string) $found->requestIdentifier());
    }
}
