<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\CompleteInitialSetup;

use Mockery;
use Source\Account\Account\Application\UseCase\Command\CompleteInitialSetup\CompleteInitialSetup;
use Source\Account\Account\Application\UseCase\Command\CompleteInitialSetup\CompleteInitialSetupInput;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Exception\AccountSetupUnavailableException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountDocuments;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CompleteInitialSetupTest extends TestCase
{
    public function testProcessCompletesAndSavesAccount(): void
    {
        $identifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $account = new Account(
            $identifier,
            new Email('setup@example.com'),
            null,
            new AccountName('Setup Account'),
            AccountStatus::PENDING,
            AccountCategory::GENERAL,
            DeletionReadinessChecklist::ready(),
            new AccountDocuments(),
        );
        /** @var AccountRepositoryInterface&Mockery\MockInterface $accountRepository */
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')->once()->with($identifier)->andReturn($account);
        $accountRepository->shouldReceive('save')->once()->with($account);

        (new CompleteInitialSetup($accountRepository))->process(
            new CompleteInitialSetupInput($identifier, AccountType::CORPORATION),
        );

        $this->assertSame(AccountType::CORPORATION, $account->type());
        $this->assertSame(AccountStatus::ACTIVE, $account->status());
    }

    public function testRetryCannotOverwriteCompletedType(): void
    {
        $identifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $account = new Account(
            $identifier,
            new Email('setup@example.com'),
            AccountType::CORPORATION,
            new AccountName('Setup Account'),
            AccountStatus::ACTIVE,
            AccountCategory::GENERAL,
            DeletionReadinessChecklist::ready(),
            new AccountDocuments(),
        );
        /** @var AccountRepositoryInterface&Mockery\MockInterface $accountRepository */
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')->once()->with($identifier)->andReturn($account);
        $accountRepository->shouldNotReceive('save');

        $this->expectException(AccountSetupUnavailableException::class);

        try {
            (new CompleteInitialSetup($accountRepository))->process(
                new CompleteInitialSetupInput($identifier, AccountType::INDIVIDUAL),
            );
        } finally {
            $this->assertSame(AccountType::CORPORATION, $account->type());
            $this->assertSame(AccountStatus::ACTIVE, $account->status());
        }
    }
}
