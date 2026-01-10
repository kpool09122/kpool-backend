<?php

declare(strict_types=1);

namespace Tests\Account\Application\UseCase\Command\DeleteAccount;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Application\UseCase\Command\DeleteAccount\DeleteAccount;
use Source\Account\Application\UseCase\Command\DeleteAccount\DeleteAccountInput;
use Source\Account\Application\UseCase\Command\DeleteAccount\DeleteAccountInterface;
use Source\Account\Domain\Entity\Account;
use Source\Account\Domain\Exception\AccountDeletionBlockedException;
use Source\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Domain\ValueObject\AccountCategory;
use Source\Account\Domain\ValueObject\AccountName;
use Source\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Domain\ValueObject\AccountType;
use Source\Account\Domain\ValueObject\AddressLine;
use Source\Account\Domain\ValueObject\BillingAddress;
use Source\Account\Domain\ValueObject\BillingContact;
use Source\Account\Domain\ValueObject\BillingCycle;
use Source\Account\Domain\ValueObject\BillingMethod;
use Source\Account\Domain\ValueObject\City;
use Source\Account\Domain\ValueObject\ContractInfo;
use Source\Account\Domain\ValueObject\ContractName;
use Source\Account\Domain\ValueObject\CountryCode;
use Source\Account\Domain\ValueObject\DeletionBlockReason;
use Source\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Domain\ValueObject\Phone;
use Source\Account\Domain\ValueObject\Plan;
use Source\Account\Domain\ValueObject\PlanDescription;
use Source\Account\Domain\ValueObject\PlanName;
use Source\Account\Domain\ValueObject\PostalCode;
use Source\Account\Domain\ValueObject\StateOrProvince;
use Source\Account\Domain\ValueObject\TaxCategory;
use Source\Account\Domain\ValueObject\TaxInfo;
use Source\Account\Domain\ValueObject\TaxRegion;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Money;
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
        $this->assertSame($dummyData->contractInfo, $account->contractInfo());
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
        $address = new BillingAddress(
            countryCode: CountryCode::JAPAN,
            postalCode: new PostalCode('100-0001'),
            stateOrProvince: new StateOrProvince('Tokyo'),
            city: new City('Chiyoda'),
            addressLine1: new AddressLine('1-1-1'),
        );
        $contact = new BillingContact(
            name: new ContractName('Taro Example'),
            email: new Email('taro@example.com'),
            phone: new Phone('+81-3-0000-0000'),
        );
        $plan = new Plan(
            planName: new PlanName('Basic Plan'),
            billingCycle: BillingCycle::MONTHLY,
            planDescription: new PlanDescription(''),
            money: new Money(10000, Currency::KRW),
        );
        $taxInfo = new TaxInfo(TaxRegion::JP, TaxCategory::TAXABLE, 'T1234567890123');
        $contractInfo = new ContractInfo(
            billingAddress: $address,
            billingContact: $contact,
            billingMethod: BillingMethod::INVOICE,
            plan: $plan,
            taxInfo: $taxInfo,
        );

        $status = AccountStatus::ACTIVE;
        $accountCategory = AccountCategory::GENERAL;

        $deletionReadiness ??= DeletionReadinessChecklist::ready();

        $account = new Account(
            $identifier,
            $email,
            $accountType,
            $accountName,
            $contractInfo,
            $status,
            $accountCategory,
            $deletionReadiness,
        );

        return new DeleteAccountTestData(
            $identifier,
            $email,
            $accountType,
            $accountName,
            $contractInfo,
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
        public ContractInfo $contractInfo,
        public AccountCategory $accountCategory,
        public Account $account,
        public DeletionReadinessChecklist $deletionReadiness,
    ) {
    }
}
