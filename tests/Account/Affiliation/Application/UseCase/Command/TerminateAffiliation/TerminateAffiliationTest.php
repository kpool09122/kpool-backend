<?php

declare(strict_types=1);

namespace Tests\Account\Affiliation\Application\UseCase\Command\TerminateAffiliation;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Affiliation\Application\Exception\AffiliationNotFoundException;
use Source\Account\Affiliation\Application\Exception\DisallowedAffiliationOperationException;
use Source\Account\Affiliation\Application\UseCase\Command\TerminateAffiliation\TerminateAffiliation;
use Source\Account\Affiliation\Application\UseCase\Command\TerminateAffiliation\TerminateAffiliationInput;
use Source\Account\Affiliation\Application\UseCase\Command\TerminateAffiliation\TerminateAffiliationInterface;
use Source\Account\Affiliation\Domain\Entity\Affiliation;
use Source\Account\Affiliation\Domain\Repository\AffiliationRepositoryInterface;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationStatus;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationTerms;
use Source\Account\Delegation\Domain\Service\DelegationTerminationServiceInterface;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TerminateAffiliationTest extends TestCase
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
        $delegationTerminationService = Mockery::mock(DelegationTerminationServiceInterface::class);
        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);
        $this->app->instance(DelegationTerminationServiceInterface::class, $delegationTerminationService);
        $useCase = $this->app->make(TerminateAffiliationInterface::class);
        $this->assertInstanceOf(TerminateAffiliation::class, $useCase);
    }

    /**
     * 正常系: Agency側がアフィリエーションを終了できること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessByAgency(): void
    {
        $testData = $this->createTestData();
        $input = new TerminateAffiliationInput(
            $testData->affiliationIdentifier,
            $testData->agencyAccountIdentifier,
        );

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationRepository->shouldReceive('findById')
            ->with($testData->affiliationIdentifier)
            ->once()
            ->andReturn($testData->affiliation);
        $affiliationRepository->shouldReceive('save')
            ->once()
            ->with($testData->affiliation);

        $delegationTerminationService = Mockery::mock(DelegationTerminationServiceInterface::class);
        $delegationTerminationService->shouldReceive('revokeAllDelegations')
            ->with($testData->affiliationIdentifier)
            ->once()
            ->andReturn(0);

        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);
        $this->app->instance(DelegationTerminationServiceInterface::class, $delegationTerminationService);

        $useCase = $this->app->make(TerminateAffiliationInterface::class);

        $result = $useCase->process($input);

        $this->assertTrue($result->isTerminated());
        $this->assertNotNull($result->terminatedAt());
    }

    /**
     * 正常系: Talent側がアフィリエーションを終了できること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessByTalent(): void
    {
        $testData = $this->createTestData();
        $input = new TerminateAffiliationInput(
            $testData->affiliationIdentifier,
            $testData->talentAccountIdentifier,
        );

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationRepository->shouldReceive('findById')
            ->with($testData->affiliationIdentifier)
            ->once()
            ->andReturn($testData->affiliation);
        $affiliationRepository->shouldReceive('save')
            ->once()
            ->with($testData->affiliation);

        $delegationTerminationService = Mockery::mock(DelegationTerminationServiceInterface::class);
        $delegationTerminationService->shouldReceive('revokeAllDelegations')
            ->with($testData->affiliationIdentifier)
            ->once()
            ->andReturn(0);

        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);
        $this->app->instance(DelegationTerminationServiceInterface::class, $delegationTerminationService);

        $useCase = $this->app->make(TerminateAffiliationInterface::class);

        $result = $useCase->process($input);

        $this->assertTrue($result->isTerminated());
        $this->assertNotNull($result->terminatedAt());
    }

    /**
     * 異常系: アフィリエーションが存在しない場合、例外がスローされること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsWhenAffiliationNotFound(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $terminatorAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $input = new TerminateAffiliationInput($affiliationIdentifier, $terminatorAccountIdentifier);

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationRepository->shouldReceive('findById')
            ->with($affiliationIdentifier)
            ->once()
            ->andReturnNull();

        $delegationTerminationService = Mockery::mock(DelegationTerminationServiceInterface::class);

        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);
        $this->app->instance(DelegationTerminationServiceInterface::class, $delegationTerminationService);

        $useCase = $this->app->make(TerminateAffiliationInterface::class);

        $this->expectException(AffiliationNotFoundException::class);
        $this->expectExceptionMessage('Affiliation not found.');

        $useCase->process($input);
    }

    /**
     * 異常系: 終了権限のないアカウントが終了しようとした場合、例外がスローされること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsWhenUnauthorizedTerminator(): void
    {
        $testData = $this->createTestData();
        $unauthorizedAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $input = new TerminateAffiliationInput(
            $testData->affiliationIdentifier,
            $unauthorizedAccountIdentifier,
        );

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationRepository->shouldReceive('findById')
            ->with($testData->affiliationIdentifier)
            ->once()
            ->andReturn($testData->affiliation);
        $affiliationRepository->shouldNotReceive('save');

        $delegationTerminationService = Mockery::mock(DelegationTerminationServiceInterface::class);
        $delegationTerminationService->shouldNotReceive('revokeAllDelegations');

        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);
        $this->app->instance(DelegationTerminationServiceInterface::class, $delegationTerminationService);

        $useCase = $this->app->make(TerminateAffiliationInterface::class);

        $this->expectException(DisallowedAffiliationOperationException::class);
        $this->expectExceptionMessage('Only the agency or talent can terminate this affiliation.');

        $useCase->process($input);
    }

    private function createTestData(): TerminateAffiliationTestData
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $requestedBy = $agencyAccountIdentifier;
        $terms = new AffiliationTerms(new Percentage(30), 'Contract notes');

        $affiliation = new Affiliation(
            $affiliationIdentifier,
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            $requestedBy,
            AffiliationStatus::ACTIVE,
            $terms,
            new DateTimeImmutable('-1 day'),
            new DateTimeImmutable(),
            null,
        );

        return new TerminateAffiliationTestData(
            $affiliationIdentifier,
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            $affiliation,
        );
    }
}

readonly class TerminateAffiliationTestData
{
    public function __construct(
        public AffiliationIdentifier $affiliationIdentifier,
        public AccountIdentifier     $agencyAccountIdentifier,
        public AccountIdentifier     $talentAccountIdentifier,
        public Affiliation           $affiliation,
    ) {
    }
}
