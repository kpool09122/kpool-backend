<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\DeleteAccount;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\UseCase\Command\DeleteAccount\DeleteAccount;
use Source\Account\Account\Application\UseCase\Command\DeleteAccount\DeleteAccountInput;
use Source\Account\Account\Application\UseCase\Command\DeleteAccount\DeleteAccountInterface;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Exception\AccountDeletionBlockedException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\DeletionBlockReason;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DeleteAccountTest extends TestCase
{
    /**
     * 正常系: 正しくDIが動作すること
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = Mockery::mock(AccountRepositoryInterface::class);
        $this->app->instance(AccountRepositoryInterface::class, $repository);
        $useCase = $this->app->make(DeleteAccountInterface::class);
        $this->assertInstanceOf(DeleteAccount::class, $useCase);
    }

    /**
     * 正常系: 正しくアカウントが削除できること.
     *
     * @throws BindingResolutionException
     * @throws AccountNotFoundException
     */
    public function testProcess(): void
    {
        $dummyData = $this->createDummyAccountTestData();
        $input = new DeleteAccountInput($dummyData->identifier);

        $repository = Mockery::mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->with($dummyData->identifier)
            ->once()
            ->andReturn($dummyData->account);
        $repository->shouldReceive('delete')
            ->with($dummyData->account)
            ->once()
            ->andReturnNull();

        $this->app->instance(AccountRepositoryInterface::class, $repository);
        $useCase = $this->app->make(DeleteAccountInterface::class);
        $account = $useCase->process($input);

        $this->assertSame((string) $dummyData->identifier, (string) $account->accountIdentifier());
        $this->assertSame((string) $dummyData->email, (string) $account->email());
        $this->assertSame($dummyData->accountType, $account->type());
        $this->assertSame((string) $dummyData->accountName, (string) $account->name());
    }

    /**
     * 異常系: IDに紐づくアカウントが見つからない場合、例外がスローされること.
     *
     * @throws BindingResolutionException
     */
    public function testThrowsAccountNotFoundException(): void
    {
        $dummyData = $this->createDummyAccountTestData();
        $input = new DeleteAccountInput($dummyData->identifier);

        $repository = Mockery::mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->with($dummyData->identifier)
            ->once()
            ->andReturnNull();
        $repository->shouldNotReceive('delete');

        $this->app->instance(AccountRepositoryInterface::class, $repository);
        $useCase = $this->app->make(DeleteAccountInterface::class);

        $this->expectException(AccountNotFoundException::class);
        $useCase->process($input);
    }

    /**
     * 異常系: 削除前提条件を満たしていない場合、例外がスローされること.
     *
     * @throws BindingResolutionException
     * @throws AccountNotFoundException
     */
    public function testThrowsWhenDeletionNotReady(): void
    {
        $deletionReadiness = DeletionReadinessChecklist::fromReasons(
            DeletionBlockReason::UNPAID_INVOICES,
            DeletionBlockReason::EXTERNAL_INTEGRATIONS_ACTIVE,
        );
        $dummyData = $this->createDummyAccountTestData(deletionReadiness: $deletionReadiness);
        $input = new DeleteAccountInput($dummyData->identifier);

        $repository = Mockery::mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->with($dummyData->identifier)
            ->once()
            ->andReturn($dummyData->account);
        $repository->shouldNotReceive('delete');

        $this->app->instance(AccountRepositoryInterface::class, $repository);
        $useCase = $this->app->make(DeleteAccountInterface::class);

        $this->expectException(AccountDeletionBlockedException::class);

        try {
            $useCase->process($input);
            $this->fail('AccountDeletionBlockedException was not thrown.');
        } catch (AccountDeletionBlockedException $exception) {
            $this->assertEquals(
                [
                    DeletionBlockReason::UNPAID_INVOICES,
                    DeletionBlockReason::EXTERNAL_INTEGRATIONS_ACTIVE,
                ],
                $exception->blockers()
            );

            throw $exception;
        }
    }

    private function createDummyAccountTestData(
        ?DeletionReadinessChecklist $deletionReadiness = null
    ): DeleteAccountTestData {
        $identifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $email = new Email('test@test.com');
        $accountType = AccountType::CORPORATION;
        $accountName = new AccountName('Example Inc');

        $status = AccountStatus::ACTIVE;
        $accountCategory = AccountCategory::GENERAL;

        $deletionReadiness ??= DeletionReadinessChecklist::ready();

        $account = new Account(
            $identifier,
            $email,
            $accountType,
            $accountName,
            $status,
            $accountCategory,
            $deletionReadiness,
        );

        return new DeleteAccountTestData(
            $identifier,
            $email,
            $accountType,
            $accountName,
            $accountCategory,
            $account,
            $deletionReadiness,
        );
    }
}

readonly class DeleteAccountTestData
{
    public function __construct(
        public AccountIdentifier $identifier,
        public Email $email,
        public AccountType $accountType,
        public AccountName $accountName,
        public AccountCategory $accountCategory,
        public Account $account,
        public DeletionReadinessChecklist $deletionReadiness,
    ) {
    }
}
