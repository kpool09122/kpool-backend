<?php

declare(strict_types=1);

namespace Tests\Account\Application\UseCase\Command\CreateAccount;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Application\Exception\AccountAlreadyExistsException;
use Source\Account\Application\UseCase\Command\CreateAccount\CreateAccount;
use Source\Account\Application\UseCase\Command\CreateAccount\CreateAccountInput;
use Source\Account\Application\UseCase\Command\CreateAccount\CreateAccountInterface;
use Source\Account\Domain\Entity\Account;
use Source\Account\Domain\Entity\AccountMembership;
use Source\Account\Domain\Factory\AccountFactoryInterface;
use Source\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Domain\ValueObject\AccountIdentifier;
use Source\Account\Domain\ValueObject\AccountName;
use Source\Account\Domain\ValueObject\AccountRole;
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
use Source\Account\Domain\ValueObject\Currency;
use Source\Account\Domain\ValueObject\Money;
use Source\Account\Domain\ValueObject\Phone;
use Source\Account\Domain\ValueObject\Plan;
use Source\Account\Domain\ValueObject\PlanDescription;
use Source\Account\Domain\ValueObject\PlanName;
use Source\Account\Domain\ValueObject\PostalCode;
use Source\Account\Domain\ValueObject\StateOrProvince;
use Source\Account\Domain\ValueObject\TaxCategory;
use Source\Account\Domain\ValueObject\TaxInfo;
use Source\Account\Domain\ValueObject\TaxRegion;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\UserIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreateAccountTest extends TestCase
{
    /**
     * 正常系: 正しくDIが動作すること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = Mockery::mock(AccountRepositoryInterface::class);
        $factory = Mockery::mock(AccountFactoryInterface::class);
        $this->app->instance(AccountRepositoryInterface::class, $repository);
        $this->app->instance(AccountFactoryInterface::class, $factory);
        $useCase = $this->app->make(CreateAccountInterface::class);
        $this->assertInstanceOf(CreateAccount::class, $useCase);
    }

    /**
     * 正常系: 正しくアカウントを作成できること.
     *
     * @return void
     * @throws AccountAlreadyExistsException
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $testData = $this->createDummyAccountTestData();

        $repository = Mockery::mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('findByEmail')
            ->with($testData->email)
            ->once()
            ->andReturnNull();
        $repository->shouldReceive('save')
            ->once()
            ->with($testData->account)
            ->andReturnNull();

        $factory = Mockery::mock(AccountFactoryInterface::class);
        $factory->shouldReceive('create')
            ->once()
            ->with($testData->email, $testData->accountType, $testData->accountName, $testData->contractInfo, $testData->memberships)
            ->andReturn($testData->account);

        $this->app->instance(AccountRepositoryInterface::class, $repository);
        $this->app->instance(AccountFactoryInterface::class, $factory);

        $useCase = $this->app->make(CreateAccountInterface::class);

        $account = $useCase->process($testData->input);

        $this->assertSame((string)$testData->identifier, (string)$account->accountIdentifier());
        $this->assertSame((string)$testData->email, (string)$account->email());
        $this->assertSame($testData->accountType, $account->type());
        $this->assertSame((string)$testData->accountName, (string)$account->name());
        $this->assertSame($testData->contractInfo, $account->contractInfo());
        $this->assertSame($testData->memberships, $account->memberships());
    }

    /**
     * 異常系: アカウントが重複した時に、例外がスローされること.
     *
     * @return void
     * @throws AccountAlreadyExistsException
     * @throws BindingResolutionException
     */
    public function testThrowsWhenDuplicate(): void
    {
        $testData = $this->createDummyAccountTestData();
        $input = $testData->input;

        $repository = Mockery::mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('findByEmail')
            ->once()
            ->with($testData->email)
            ->andReturn($testData->account);
        $repository->shouldNotReceive('save');

        $factory = Mockery::mock(AccountFactoryInterface::class);
        $factory->shouldNotReceive('create');

        $this->app->instance(AccountRepositoryInterface::class, $repository);
        $this->app->instance(AccountFactoryInterface::class, $factory);


        $useCase = $this->app->make(CreateAccountInterface::class);

        $this->expectException(AccountAlreadyExistsException::class);

        $useCase->process($input);
    }

    /**
     * テスト用のダミーAccount情報
     *
     * @return CreateAccountTestData
     */
    private function createDummyAccountTestData(): CreateAccountTestData
    {
        $identifier = new AccountIdentifier(StrTestHelper::generateUlid());
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

        $userId = new UserIdentifier(StrTestHelper::generateUlid());
        $memberships = [new AccountMembership($userId, AccountRole::OWNER)];

        $status = AccountStatus::ACTIVE;

        $account = new Account(
            $identifier,
            $email,
            $accountType,
            $accountName,
            $contractInfo,
            $status,
            $memberships,
        );

        $input = new CreateAccountInput($email, $accountType, $accountName, $contractInfo, $memberships);

        return new CreateAccountTestData(
            $identifier,
            $email,
            $accountType,
            $accountName,
            $contractInfo,
            $memberships,
            $account,
            $input,
        );
    }
}

/**
 * テスト用のAccountデータ
 */
readonly class CreateAccountTestData
{
    /**
     * @param AccountMembership[] $memberships
     */
    public function __construct(
        public AccountIdentifier $identifier,
        public Email $email,
        public AccountType $accountType,
        public AccountName $accountName,
        public ContractInfo $contractInfo,
        public array $memberships,
        public Account $account,
        public CreateAccountInput $input,
    ) {
    }
}
