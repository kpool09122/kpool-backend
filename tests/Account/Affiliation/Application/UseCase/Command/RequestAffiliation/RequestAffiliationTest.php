<?php

declare(strict_types=1);

namespace Tests\Account\Affiliation\Application\UseCase\Command\RequestAffiliation;

use DateTimeImmutable;
use Mockery;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Affiliation\Application\Exception\AffiliationAlreadyExistsException;
use Source\Account\Affiliation\Application\Exception\InvalidAccountCategoryException;
use Source\Account\Affiliation\Application\UseCase\Command\RequestAffiliation\RequestAffiliationInput;
use Source\Account\Affiliation\Application\UseCase\Command\RequestAffiliation\RequestAffiliationInterface;
use Source\Account\Affiliation\Application\UseCase\Command\RequestAffiliation\RequestAffiliationOutput;
use Source\Account\Affiliation\Domain\Entity\Affiliation;
use Source\Account\Affiliation\Domain\Factory\AffiliationFactoryInterface;
use Source\Account\Affiliation\Domain\Repository\AffiliationRepositoryInterface;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationStatus;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationTerms;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RequestAffiliationTest extends TestCase
{
    /**
     * 正常系: 正しくアフィリエーションを作成できること
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

        $output = new RequestAffiliationOutput();

        $useCase->process($testData->input, $output);

        $this->assertSame((string) $testData->affiliationIdentifier, $output->toArray()['affiliationIdentifier']);
        $this->assertSame((string) $testData->agencyAccountIdentifier, $output->toArray()['agencyAccountIdentifier']);
        $this->assertSame((string) $testData->talentAccountIdentifier, $output->toArray()['talentAccountIdentifier']);
    }

    /**
     * 異常系: Agency Accountが存在しない場合、例外がスローされること
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

        $useCase->process($testData->input, new RequestAffiliationOutput());
    }

    /**
     * 異常系: Talent Accountが存在しない場合、例外がスローされること
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

        $useCase->process($testData->input, new RequestAffiliationOutput());
    }

    /**
     * 異常系: Agency AccountのカテゴリがAGENCYでない場合、例外がスローされること
     */
    public function testThrowsWhenAgencyAccountHasInvalidCategory(): void
    {
        $testData = $this->createTestData();

        $invalidAgencyAccount = new Account(
            $testData->agencyAccountIdentifier,
            new Email('agency@test.com'),
            AccountType::CORPORATION,
            new AccountName('Invalid Agency'),
            AccountStatus::ACTIVE,
            AccountCategory::GENERAL,
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

        $useCase->process($testData->input, new RequestAffiliationOutput());
    }

    /**
     * 異常系: Talent AccountのカテゴリがTALENTでない場合、例外がスローされること
     */
    public function testThrowsWhenTalentAccountHasInvalidCategory(): void
    {
        $testData = $this->createTestData();

        $invalidTalentAccount = new Account(
            $testData->talentAccountIdentifier,
            new Email('talent@test.com'),
            AccountType::INDIVIDUAL,
            new AccountName('Invalid Talent'),
            AccountStatus::ACTIVE,
            AccountCategory::GENERAL,
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

        $useCase->process($testData->input, new RequestAffiliationOutput());
    }

    /**
     * 異常系: 既にアクティブなアフィリエーションが存在する場合、例外がスローされること
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

        $useCase->process($testData->input, new RequestAffiliationOutput());
    }

    private function createTestData(): RequestAffiliationTestData
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $requestedBy = $agencyAccountIdentifier;
        $terms = new AffiliationTerms(new Percentage(30), 'Contract notes');

        $agencyAccount = new Account(
            $agencyAccountIdentifier,
            new Email('agency@test.com'),
            AccountType::CORPORATION,
            new AccountName('Test Agency'),
            AccountStatus::ACTIVE,
            AccountCategory::AGENCY,
            DeletionReadinessChecklist::ready(),
        );

        $talentAccount = new Account(
            $talentAccountIdentifier,
            new Email('talent@test.com'),
            AccountType::INDIVIDUAL,
            new AccountName('Test Talent'),
            AccountStatus::ACTIVE,
            AccountCategory::TALENT,
            DeletionReadinessChecklist::ready(),
        );

        $affiliation = new Affiliation(
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
}

readonly class RequestAffiliationTestData
{
    public function __construct(
        public AffiliationIdentifier   $affiliationIdentifier,
        public AccountIdentifier       $agencyAccountIdentifier,
        public AccountIdentifier       $talentAccountIdentifier,
        public AccountIdentifier       $requestedBy,
        public ?AffiliationTerms       $terms,
        public Account                 $agencyAccount,
        public Account                 $talentAccount,
        public Affiliation             $affiliation,
        public RequestAffiliationInput $input,
    ) {
    }
}
