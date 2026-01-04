<?php

declare(strict_types=1);

namespace Tests\Account\Application\UseCase\Command\RequestDelegation;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Application\Exception\AffiliationNotFoundException;
use Source\Account\Application\Exception\InvalidAffiliationStatusException;
use Source\Account\Application\UseCase\Command\RequestDelegation\RequestDelegation;
use Source\Account\Application\UseCase\Command\RequestDelegation\RequestDelegationInput;
use Source\Account\Application\UseCase\Command\RequestDelegation\RequestDelegationInterface;
use Source\Account\Domain\Entity\AccountAffiliation;
use Source\Account\Domain\Entity\OperationDelegation;
use Source\Account\Domain\Factory\DelegationFactoryInterface;
use Source\Account\Domain\Repository\AffiliationRepositoryInterface;
use Source\Account\Domain\Repository\DelegationRepositoryInterface;
use Source\Account\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Domain\ValueObject\AffiliationStatus;
use Source\Account\Domain\ValueObject\AffiliationTerms;
use Source\Account\Domain\ValueObject\DelegationStatus;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RequestDelegationTest extends TestCase
{
    /**
     * 正常系: 正しくDIが動作すること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $delegationRepository = Mockery::mock(DelegationRepositoryInterface::class);
        $delegationFactory = Mockery::mock(DelegationFactoryInterface::class);
        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);
        $this->app->instance(DelegationRepositoryInterface::class, $delegationRepository);
        $this->app->instance(DelegationFactoryInterface::class, $delegationFactory);
        $useCase = $this->app->make(RequestDelegationInterface::class);
        $this->assertInstanceOf(RequestDelegation::class, $useCase);
    }

    /**
     * 正常系: 正しくデリゲーションを作成できること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $testData = $this->createTestData();

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationRepository->shouldReceive('findById')
            ->with($testData->affiliationIdentifier)
            ->once()
            ->andReturn($testData->affiliation);

        $delegationRepository = Mockery::mock(DelegationRepositoryInterface::class);
        $delegationRepository->shouldReceive('save')
            ->once()
            ->with($testData->delegation);

        $delegationFactory = Mockery::mock(DelegationFactoryInterface::class);
        $delegationFactory->shouldReceive('create')
            ->once()
            ->with(
                $testData->affiliationIdentifier,
                $testData->delegateIdentifier,
                $testData->delegatorIdentifier,
            )
            ->andReturn($testData->delegation);

        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);
        $this->app->instance(DelegationRepositoryInterface::class, $delegationRepository);
        $this->app->instance(DelegationFactoryInterface::class, $delegationFactory);

        $useCase = $this->app->make(RequestDelegationInterface::class);

        $result = $useCase->process($testData->input);

        $this->assertSame((string) $testData->delegationIdentifier, (string) $result->delegationIdentifier());
        $this->assertSame((string) $testData->affiliationIdentifier, (string) $result->affiliationIdentifier());
        $this->assertSame((string) $testData->delegateIdentifier, (string) $result->delegateIdentifier());
        $this->assertSame((string) $testData->delegatorIdentifier, (string) $result->delegatorIdentifier());
        $this->assertTrue($result->isPending());
    }

    /**
     * 異常系: アフィリエーションが存在しない場合、例外がスローされること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsWhenAffiliationNotFound(): void
    {
        $testData = $this->createTestData();

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationRepository->shouldReceive('findById')
            ->with($testData->affiliationIdentifier)
            ->once()
            ->andReturnNull();

        $delegationRepository = Mockery::mock(DelegationRepositoryInterface::class);
        $delegationFactory = Mockery::mock(DelegationFactoryInterface::class);

        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);
        $this->app->instance(DelegationRepositoryInterface::class, $delegationRepository);
        $this->app->instance(DelegationFactoryInterface::class, $delegationFactory);

        $useCase = $this->app->make(RequestDelegationInterface::class);

        $this->expectException(AffiliationNotFoundException::class);
        $this->expectExceptionMessage('Affiliation not found.');

        $useCase->process($testData->input);
    }

    /**
     * 異常系: アフィリエーションがアクティブでない場合、例外がスローされること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsWhenAffiliationNotActive(): void
    {
        $testData = $this->createTestDataWithPendingAffiliation();

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationRepository->shouldReceive('findById')
            ->with($testData->affiliationIdentifier)
            ->once()
            ->andReturn($testData->affiliation);

        $delegationRepository = Mockery::mock(DelegationRepositoryInterface::class);
        $delegationFactory = Mockery::mock(DelegationFactoryInterface::class);

        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);
        $this->app->instance(DelegationRepositoryInterface::class, $delegationRepository);
        $this->app->instance(DelegationFactoryInterface::class, $delegationFactory);

        $useCase = $this->app->make(RequestDelegationInterface::class);

        $this->expectException(InvalidAffiliationStatusException::class);
        $this->expectExceptionMessage('Delegation can only be requested for active affiliations.');

        $useCase->process($testData->input);
    }

    private function createTestData(): RequestDelegationTestData
    {
        $delegationIdentifier = new DelegationIdentifier(StrTestHelper::generateUuid());
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $delegateIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegatorIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $terms = new AffiliationTerms(new Percentage(30), 'Contract notes');

        $affiliation = new AccountAffiliation(
            $affiliationIdentifier,
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            $agencyAccountIdentifier,
            AffiliationStatus::ACTIVE,
            $terms,
            new DateTimeImmutable('-1 day'),
            new DateTimeImmutable(),
            null,
        );

        $delegation = new OperationDelegation(
            $delegationIdentifier,
            $affiliationIdentifier,
            $delegateIdentifier,
            $delegatorIdentifier,
            DelegationStatus::PENDING,
            new DateTimeImmutable(),
            null,
            null,
        );

        $input = new RequestDelegationInput(
            $affiliationIdentifier,
            $delegateIdentifier,
            $delegatorIdentifier,
        );

        return new RequestDelegationTestData(
            $delegationIdentifier,
            $affiliationIdentifier,
            $delegateIdentifier,
            $delegatorIdentifier,
            $affiliation,
            $delegation,
            $input,
        );
    }

    private function createTestDataWithPendingAffiliation(): RequestDelegationTestData
    {
        $delegationIdentifier = new DelegationIdentifier(StrTestHelper::generateUuid());
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $delegateIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegatorIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $terms = new AffiliationTerms(new Percentage(30), 'Contract notes');

        $affiliation = new AccountAffiliation(
            $affiliationIdentifier,
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            $agencyAccountIdentifier,
            AffiliationStatus::PENDING,
            $terms,
            new DateTimeImmutable(),
            null,
            null,
        );

        $delegation = new OperationDelegation(
            $delegationIdentifier,
            $affiliationIdentifier,
            $delegateIdentifier,
            $delegatorIdentifier,
            DelegationStatus::PENDING,
            new DateTimeImmutable(),
            null,
            null,
        );

        $input = new RequestDelegationInput(
            $affiliationIdentifier,
            $delegateIdentifier,
            $delegatorIdentifier,
        );

        return new RequestDelegationTestData(
            $delegationIdentifier,
            $affiliationIdentifier,
            $delegateIdentifier,
            $delegatorIdentifier,
            $affiliation,
            $delegation,
            $input,
        );
    }
}

readonly class RequestDelegationTestData
{
    public function __construct(
        public DelegationIdentifier $delegationIdentifier,
        public AffiliationIdentifier $affiliationIdentifier,
        public IdentityIdentifier $delegateIdentifier,
        public IdentityIdentifier $delegatorIdentifier,
        public AccountAffiliation $affiliation,
        public OperationDelegation $delegation,
        public RequestDelegationInput $input,
    ) {
    }
}
