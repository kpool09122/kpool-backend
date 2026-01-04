<?php

declare(strict_types=1);

namespace Tests\Account\Application\UseCase\Command\RequestAffiliation;

use DateTimeImmutable;
use Mockery;
use Source\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Application\Exception\AffiliationAlreadyExistsException;
use Source\Account\Application\Exception\InvalidAccountCategoryException;
use Source\Account\Application\UseCase\Command\RequestAffiliation\RequestAffiliationInput;
use Source\Account\Application\UseCase\Command\RequestAffiliation\RequestAffiliationInterface;
use Source\Account\Domain\Entity\Account;
use Source\Account\Domain\Entity\AccountAffiliation;
use Source\Account\Domain\Entity\AccountMembership;
use Source\Account\Domain\Factory\AffiliationFactoryInterface;
use Source\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Domain\Repository\AffiliationRepositoryInterface;
use Source\Account\Domain\ValueObject\AccountCategory;
use Source\Account\Domain\ValueObject\AccountName;
use Source\Account\Domain\ValueObject\AccountRole;
use Source\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Domain\ValueObject\AccountType;
use Source\Account\Domain\ValueObject\AddressLine;
use Source\Account\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Domain\ValueObject\AffiliationStatus;
use Source\Account\Domain\ValueObject\AffiliationTerms;
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
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Money;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RequestAffiliationTest extends TestCase
{
    /**
     * 正常系: 正しくアフィリエーションを作成できること
     *
     * @return void
     */
    public function testProcess(): void
    {
        $testData = $this->createTestData();

        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')
            ->with($testData->agencyAccountIdentifier)
            ->once()
            ->andReturn($testData->agencyAccount);
        $accountRepository->shouldReceive('findById')
            ->with($testData->talentAccountIdentifier)
            ->once()
            ->andReturn($testData->talentAccount);

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationRepository->shouldReceive('existsActiveAffiliation')
            ->with($testData->agencyAccountIdentifier, $testData->talentAccountIdentifier)
            ->once()
            ->andReturnFalse();
        $affiliationRepository->shouldReceive('save')
            ->once()
            ->with($testData->affiliation);

        $affiliationFactory = Mockery::mock(AffiliationFactoryInterface::class);
        $affiliationFactory->shouldReceive('create')
            ->once()
            ->with(
                $testData->agencyAccountIdentifier,
                $testData->talentAccountIdentifier,
                $testData->requestedBy,
                $testData->terms,
            )
            ->andReturn($testData->affiliation);

        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);
        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);
        $this->app->instance(AffiliationFactoryInterface::class, $affiliationFactory);

        $useCase = $this->app->make(RequestAffiliationInterface::class);

        $result = $useCase->process($testData->input);

        $this->assertSame((string) $testData->affiliationIdentifier, (string) $result->affiliationIdentifier());
        $this->assertSame((string) $testData->agencyAccountIdentifier, (string) $result->agencyAccountIdentifier());
        $this->assertSame((string) $testData->talentAccountIdentifier, (string) $result->talentAccountIdentifier());
    }

    /**
     * 異常系: Agency Accountが存在しない場合、例外がスローされること
     *
     * @return void
     */
    public function testThrowsWhenAgencyAccountNotFound(): void
    {
        $testData = $this->createTestData();

        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')
            ->with($testData->agencyAccountIdentifier)
            ->once()
            ->andReturnNull();

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationFactory = Mockery::mock(AffiliationFactoryInterface::class);

        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);
        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);
        $this->app->instance(AffiliationFactoryInterface::class, $affiliationFactory);

        $useCase = $this->app->make(RequestAffiliationInterface::class);

        $this->expectException(AccountNotFoundException::class);
        $this->expectExceptionMessage('Agency account not found.');

        $useCase->process($testData->input);
    }

    /**
     * 異常系: Talent Accountが存在しない場合、例外がスローされること
     *
     * @return void
     */
    public function testThrowsWhenTalentAccountNotFound(): void
    {
        $testData = $this->createTestData();

        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')
            ->with($testData->agencyAccountIdentifier)
            ->once()
            ->andReturn($testData->agencyAccount);
        $accountRepository->shouldReceive('findById')
            ->with($testData->talentAccountIdentifier)
            ->once()
            ->andReturnNull();

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationFactory = Mockery::mock(AffiliationFactoryInterface::class);

        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);
        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);
        $this->app->instance(AffiliationFactoryInterface::class, $affiliationFactory);

        $useCase = $this->app->make(RequestAffiliationInterface::class);

        $this->expectException(AccountNotFoundException::class);
        $this->expectExceptionMessage('Talent account not found.');

        $useCase->process($testData->input);
    }

    /**
     * 異常系: Agency AccountのカテゴリがAGENCYでない場合、例外がスローされること
     *
     * @return void
     */
    public function testThrowsWhenAgencyAccountHasInvalidCategory(): void
    {
        $testData = $this->createTestData();

        $invalidAgencyAccount = new Account(
            $testData->agencyAccountIdentifier,
            new Email('agency@test.com'),
            AccountType::CORPORATION,
            new AccountName('Invalid Agency'),
            $this->createContractInfo(),
            AccountStatus::ACTIVE,
            AccountCategory::GENERAL,
            [$this->createOwnerMembership()],
            DeletionReadinessChecklist::ready(),
        );

        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')
            ->with($testData->agencyAccountIdentifier)
            ->once()
            ->andReturn($invalidAgencyAccount);

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationFactory = Mockery::mock(AffiliationFactoryInterface::class);

        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);
        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);
        $this->app->instance(AffiliationFactoryInterface::class, $affiliationFactory);

        $useCase = $this->app->make(RequestAffiliationInterface::class);

        $this->expectException(InvalidAccountCategoryException::class);
        $this->expectExceptionMessage('Agency account must have agency category.');

        $useCase->process($testData->input);
    }

    /**
     * 異常系: Talent AccountのカテゴリがTALENTでない場合、例外がスローされること
     *
     * @return void
     */
    public function testThrowsWhenTalentAccountHasInvalidCategory(): void
    {
        $testData = $this->createTestData();

        $invalidTalentAccount = new Account(
            $testData->talentAccountIdentifier,
            new Email('talent@test.com'),
            AccountType::INDIVIDUAL,
            new AccountName('Invalid Talent'),
            $this->createContractInfo(),
            AccountStatus::ACTIVE,
            AccountCategory::GENERAL,
            [$this->createOwnerMembership()],
            DeletionReadinessChecklist::ready(),
        );

        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')
            ->with($testData->agencyAccountIdentifier)
            ->once()
            ->andReturn($testData->agencyAccount);
        $accountRepository->shouldReceive('findById')
            ->with($testData->talentAccountIdentifier)
            ->once()
            ->andReturn($invalidTalentAccount);

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationFactory = Mockery::mock(AffiliationFactoryInterface::class);

        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);
        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);
        $this->app->instance(AffiliationFactoryInterface::class, $affiliationFactory);

        $useCase = $this->app->make(RequestAffiliationInterface::class);

        $this->expectException(InvalidAccountCategoryException::class);
        $this->expectExceptionMessage('Talent account must have talent category.');

        $useCase->process($testData->input);
    }

    /**
     * 異常系: 既にアクティブなアフィリエーションが存在する場合、例外がスローされること
     *
     * @return void
     */
    public function testThrowsWhenActiveAffiliationAlreadyExists(): void
    {
        $testData = $this->createTestData();

        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')
            ->with($testData->agencyAccountIdentifier)
            ->once()
            ->andReturn($testData->agencyAccount);
        $accountRepository->shouldReceive('findById')
            ->with($testData->talentAccountIdentifier)
            ->once()
            ->andReturn($testData->talentAccount);

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationRepository->shouldReceive('existsActiveAffiliation')
            ->with($testData->agencyAccountIdentifier, $testData->talentAccountIdentifier)
            ->once()
            ->andReturnTrue();

        $affiliationFactory = Mockery::mock(AffiliationFactoryInterface::class);

        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);
        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);
        $this->app->instance(AffiliationFactoryInterface::class, $affiliationFactory);

        $useCase = $this->app->make(RequestAffiliationInterface::class);

        $this->expectException(AffiliationAlreadyExistsException::class);
        $this->expectExceptionMessage('An active affiliation already exists between these accounts.');

        $useCase->process($testData->input);
    }

    private function createTestData(): RequestAffiliationTestData
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $requestedBy = $agencyAccountIdentifier;
        $terms = new AffiliationTerms(new Percentage(30), 'Contract notes');

        $contractInfo = $this->createContractInfo();
        $ownerMembership = $this->createOwnerMembership();

        $agencyAccount = new Account(
            $agencyAccountIdentifier,
            new Email('agency@test.com'),
            AccountType::CORPORATION,
            new AccountName('Test Agency'),
            $contractInfo,
            AccountStatus::ACTIVE,
            AccountCategory::AGENCY,
            [$ownerMembership],
            DeletionReadinessChecklist::ready(),
        );

        $talentAccount = new Account(
            $talentAccountIdentifier,
            new Email('talent@test.com'),
            AccountType::INDIVIDUAL,
            new AccountName('Test Talent'),
            $contractInfo,
            AccountStatus::ACTIVE,
            AccountCategory::TALENT,
            [$ownerMembership],
            DeletionReadinessChecklist::ready(),
        );

        $affiliation = new AccountAffiliation(
            $affiliationIdentifier,
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            $requestedBy,
            AffiliationStatus::PENDING,
            $terms,
            new DateTimeImmutable(),
            null,
            null,
        );

        $input = new RequestAffiliationInput(
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            $requestedBy,
            $terms,
        );

        return new RequestAffiliationTestData(
            $affiliationIdentifier,
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            $requestedBy,
            $terms,
            $agencyAccount,
            $talentAccount,
            $affiliation,
            $input,
        );
    }

    private function createContractInfo(): ContractInfo
    {
        $address = new BillingAddress(
            countryCode: CountryCode::JAPAN,
            postalCode: new PostalCode('100-0001'),
            stateOrProvince: new StateOrProvince('Tokyo'),
            city: new City('Chiyoda'),
            addressLine1: new AddressLine('1-1-1'),
        );
        $contact = new BillingContact(
            name: new ContractName('Test User'),
            email: new Email('test@example.com'),
            phone: new Phone('+81-3-0000-0000'),
        );
        $plan = new Plan(
            planName: new PlanName('Basic Plan'),
            billingCycle: BillingCycle::MONTHLY,
            planDescription: new PlanDescription(''),
            money: new Money(10000, Currency::KRW),
        );
        $taxInfo = new TaxInfo(TaxRegion::JP, TaxCategory::TAXABLE, 'T1234567890123');

        return new ContractInfo(
            billingAddress: $address,
            billingContact: $contact,
            billingMethod: BillingMethod::INVOICE,
            plan: $plan,
            taxInfo: $taxInfo,
        );
    }

    private function createOwnerMembership(): AccountMembership
    {
        return new AccountMembership(
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            AccountRole::OWNER,
        );
    }
}

readonly class RequestAffiliationTestData
{
    public function __construct(
        public AffiliationIdentifier $affiliationIdentifier,
        public AccountIdentifier $agencyAccountIdentifier,
        public AccountIdentifier $talentAccountIdentifier,
        public AccountIdentifier $requestedBy,
        public ?AffiliationTerms $terms,
        public Account $agencyAccount,
        public Account $talentAccount,
        public AccountAffiliation $affiliation,
        public RequestAffiliationInput $input,
    ) {
    }
}
