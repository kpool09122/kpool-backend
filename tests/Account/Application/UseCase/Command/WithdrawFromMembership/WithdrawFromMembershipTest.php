<?php

declare(strict_types=1);

namespace Tests\Account\Application\UseCase\Command\WithdrawFromMembership;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Application\UseCase\Command\WithdrawFromMembership\WithdrawFromMembership;
use Source\Account\Application\UseCase\Command\WithdrawFromMembership\WithdrawFromMembershipInput;
use Source\Account\Application\UseCase\Command\WithdrawFromMembership\WithdrawFromMembershipInterface;
use Source\Account\Domain\Entity\Account;
use Source\Account\Domain\Entity\AccountMembership;
use Source\Account\Domain\Exception\AccountMembershipNotFoundException;
use Source\Account\Domain\Exception\DisallowedToWithdrawByOwnerException;
use Source\Account\Domain\Repository\AccountRepositoryInterface;
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
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Money;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class WithdrawFromMembershipTest extends TestCase
{
    /**
     * 正常系: 正しくDIが動作すること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = Mockery::mock(AccountRepositoryInterface::class);
        $this->app->instance(AccountRepositoryInterface::class, $repository);

        $useCase = $this->app->make(WithdrawFromMembershipInterface::class);

        $this->assertInstanceOf(WithdrawFromMembership::class, $useCase);
    }

    /**
     * 正常系: 正しくメンバーを退会できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AccountNotFoundException
     * @throws DisallowedToWithdrawByOwnerException
     */
    public function testProcess(): void
    {
        $testData = $this->createDummyWithdrawFromMembershipTestData();

        $repository = Mockery::mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with($testData->identifier)
            ->andReturn($testData->account);
        $repository->shouldReceive('save')
            ->once()
            ->with($testData->account)
            ->andReturnNull();

        $this->app->instance(AccountRepositoryInterface::class, $repository);

        $useCase = $this->app->make(WithdrawFromMembershipInterface::class);

        $account = $useCase->process($testData->input);

        $this->assertCount(1, $account->memberships());
        $this->assertSame((string)$testData->ownerMembership->identityIdentifier(), (string)$account->memberships()[0]->identityIdentifier());
        $this->assertSame(AccountRole::OWNER, $account->memberships()[0]->role());
    }

    /**
     * 異常系: アカウントが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws DisallowedToWithdrawByOwnerException
     */
    public function testThrowsWhenAccountNotFound(): void
    {
        $testData = $this->createDummyWithdrawFromMembershipTestData();

        $repository = Mockery::mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with($testData->identifier)
            ->andReturnNull();
        $repository->shouldNotReceive('save');

        $this->app->instance(AccountRepositoryInterface::class, $repository);

        $this->expectException(AccountNotFoundException::class);

        $useCase = $this->app->make(WithdrawFromMembershipInterface::class);
        $useCase->process($testData->input);
    }

    /**
     * 異常系: Ownerが退会しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AccountNotFoundException
     */
    public function testThrowsWhenOwnerWithdraws(): void
    {
        $testData = $this->createDummyWithdrawFromMembershipTestData();
        $ownerInput = new WithdrawFromMembershipInput($testData->identifier, $testData->ownerMembership);

        $repository = Mockery::mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with($testData->identifier)
            ->andReturn($testData->account);
        $repository->shouldNotReceive('save');

        $this->app->instance(AccountRepositoryInterface::class, $repository);

        $this->expectException(DisallowedToWithdrawByOwnerException::class);

        $useCase = $this->app->make(WithdrawFromMembershipInterface::class);
        $useCase->process($ownerInput);
    }

    /**
     * 異常系: メンバーシップがアカウントに存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AccountNotFoundException
     * @throws DisallowedToWithdrawByOwnerException
     */
    public function testThrowsWhenMembershipNotFound(): void
    {
        $testData = $this->createDummyWithdrawFromMembershipTestData();
        $nonMemberInput = new WithdrawFromMembershipInput(
            $testData->identifier,
            new AccountMembership(
                new IdentityIdentifier(StrTestHelper::generateUuid()),
                AccountRole::MEMBER
            )
        );

        $repository = Mockery::mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with($testData->identifier)
            ->andReturn($testData->account);
        $repository->shouldNotReceive('save');

        $this->app->instance(AccountRepositoryInterface::class, $repository);

        $this->expectException(AccountMembershipNotFoundException::class);

        $useCase = $this->app->make(WithdrawFromMembershipInterface::class);
        $useCase->process($nonMemberInput);
    }

    /**
     * テスト用のダミーWithdrawFromMembership情報
     *
     * @return WithdrawFromMembershipTestData
     */
    private function createDummyWithdrawFromMembershipTestData(): WithdrawFromMembershipTestData
    {
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

        $ownerMembership = new AccountMembership(
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            AccountRole::OWNER
        );
        $withdrawMembership = new AccountMembership(
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            AccountRole::MEMBER
        );

        $memberships = [$ownerMembership, $withdrawMembership];

        $status = AccountStatus::ACTIVE;

        $account = new Account(
            $identifier,
            $email,
            $accountType,
            $accountName,
            $contractInfo,
            $status,
            $memberships,
            DeletionReadinessChecklist::ready(),
        );

        $input = new WithdrawFromMembershipInput($identifier, $withdrawMembership);

        return new WithdrawFromMembershipTestData(
            $identifier,
            $account,
            $ownerMembership,
            $withdrawMembership,
            $input,
        );
    }
}

/**
 * テスト用のWithdrawFromMembershipデータ
 */
readonly class WithdrawFromMembershipTestData
{
    public function __construct(
        public AccountIdentifier $identifier,
        public Account $account,
        public AccountMembership $ownerMembership,
        public AccountMembership $withdrawMembership,
        public WithdrawFromMembershipInput $input,
    ) {
    }
}
