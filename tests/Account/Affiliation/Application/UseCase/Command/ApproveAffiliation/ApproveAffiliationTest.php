<?php

declare(strict_types=1);

namespace Tests\Account\Affiliation\Application\UseCase\Command\ApproveAffiliation;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Affiliation\Application\Exception\AffiliationNotFoundException;
use Source\Account\Affiliation\Application\Exception\DisallowedAffiliationOperationException;
use Source\Account\Affiliation\Application\UseCase\Command\ApproveAffiliation\ApproveAffiliation;
use Source\Account\Affiliation\Application\UseCase\Command\ApproveAffiliation\ApproveAffiliationInput;
use Source\Account\Affiliation\Application\UseCase\Command\ApproveAffiliation\ApproveAffiliationInterface;
use Source\Account\Affiliation\Domain\Entity\Affiliation;
use Source\Account\Affiliation\Domain\Event\AffiliationActivated;
use Source\Account\Affiliation\Domain\Repository\AffiliationRepositoryInterface;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationStatus;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationTerms;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ApproveAffiliationTest extends TestCase
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
        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);
        $useCase = $this->app->make(ApproveAffiliationInterface::class);
        $this->assertInstanceOf(ApproveAffiliation::class, $useCase);
    }

    /**
     * 正常系: Agency側がリクエストした場合、Talent側が承認できること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessWhenRequestedByAgency(): void
    {
        $testData = $this->createTestDataRequestedByAgency();

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationRepository->shouldReceive('findById')
            ->with($testData->affiliationIdentifier)
            ->once()
            ->andReturn($testData->affiliation);
        $affiliationRepository->shouldReceive('save')
            ->once()
            ->with($testData->affiliation);

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(
                fn ($event) => $event instanceof AffiliationActivated
                && (string) $event->affiliationIdentifier() === (string) $testData->affiliationIdentifier
                && (string) $event->agencyAccountIdentifier() === (string) $testData->agencyAccountIdentifier
                && (string) $event->talentAccountIdentifier() === (string) $testData->talentAccountIdentifier
            ));

        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);
        $this->app->instance(EventDispatcherInterface::class, $eventDispatcher);

        $useCase = $this->app->make(ApproveAffiliationInterface::class);

        $result = $useCase->process($testData->input);

        $this->assertTrue($result->isActive());
        $this->assertNotNull($result->activatedAt());
    }

    /**
     * 正常系: Talent側がリクエストした場合、Agency側が承認できること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessWhenRequestedByTalent(): void
    {
        $testData = $this->createTestDataRequestedByTalent();

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationRepository->shouldReceive('findById')
            ->with($testData->affiliationIdentifier)
            ->once()
            ->andReturn($testData->affiliation);
        $affiliationRepository->shouldReceive('save')
            ->once()
            ->with($testData->affiliation);

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(
                fn ($event) => $event instanceof AffiliationActivated
                && (string) $event->affiliationIdentifier() === (string) $testData->affiliationIdentifier
                && (string) $event->agencyAccountIdentifier() === (string) $testData->agencyAccountIdentifier
                && (string) $event->talentAccountIdentifier() === (string) $testData->talentAccountIdentifier
            ));

        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);
        $this->app->instance(EventDispatcherInterface::class, $eventDispatcher);

        $useCase = $this->app->make(ApproveAffiliationInterface::class);

        $result = $useCase->process($testData->input);

        $this->assertTrue($result->isActive());
        $this->assertNotNull($result->activatedAt());
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
        $approverAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $input = new ApproveAffiliationInput($affiliationIdentifier, $approverAccountIdentifier);

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationRepository->shouldReceive('findById')
            ->with($affiliationIdentifier)
            ->once()
            ->andReturnNull();

        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);

        $useCase = $this->app->make(ApproveAffiliationInterface::class);

        $this->expectException(AffiliationNotFoundException::class);
        $this->expectExceptionMessage('Affiliation not found.');

        $useCase->process($input);
    }

    /**
     * 異常系: 承認権限のないアカウントが承認しようとした場合、例外がスローされること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsWhenUnauthorizedApprover(): void
    {
        $testData = $this->createTestDataRequestedByAgency();
        $unauthorizedAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $input = new ApproveAffiliationInput(
            $testData->affiliationIdentifier,
            $unauthorizedAccountIdentifier,
        );

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationRepository->shouldReceive('findById')
            ->with($testData->affiliationIdentifier)
            ->once()
            ->andReturn($testData->affiliation);
        $affiliationRepository->shouldNotReceive('save');

        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);

        $useCase = $this->app->make(ApproveAffiliationInterface::class);

        $this->expectException(DisallowedAffiliationOperationException::class);
        $this->expectExceptionMessage('Only the designated approver can approve this affiliation.');

        $useCase->process($input);
    }

    /**
     * Agency側がリクエストしたテストデータを作成
     */
    private function createTestDataRequestedByAgency(): ApproveAffiliationTestData
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
            AffiliationStatus::PENDING,
            $terms,
            new DateTimeImmutable(),
            null,
            null,
        );

        $input = new ApproveAffiliationInput(
            $affiliationIdentifier,
            $talentAccountIdentifier,
        );

        return new ApproveAffiliationTestData(
            $affiliationIdentifier,
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            $affiliation,
            $input,
        );
    }

    /**
     * Talent側がリクエストしたテストデータを作成
     */
    private function createTestDataRequestedByTalent(): ApproveAffiliationTestData
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $requestedBy = $talentAccountIdentifier;
        $terms = new AffiliationTerms(new Percentage(30), 'Contract notes');

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

        $input = new ApproveAffiliationInput(
            $affiliationIdentifier,
            $agencyAccountIdentifier,
        );

        return new ApproveAffiliationTestData(
            $affiliationIdentifier,
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            $affiliation,
            $input,
        );
    }
}

readonly class ApproveAffiliationTestData
{
    public function __construct(
        public AffiliationIdentifier   $affiliationIdentifier,
        public AccountIdentifier       $agencyAccountIdentifier,
        public AccountIdentifier       $talentAccountIdentifier,
        public Affiliation             $affiliation,
        public ApproveAffiliationInput $input,
    ) {
    }
}
