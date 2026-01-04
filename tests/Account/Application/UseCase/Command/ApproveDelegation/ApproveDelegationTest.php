<?php

declare(strict_types=1);

namespace Tests\Account\Application\UseCase\Command\ApproveDelegation;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Application\Exception\DelegationNotFoundException;
use Source\Account\Application\Exception\DisallowedDelegationOperationException;
use Source\Account\Application\UseCase\Command\ApproveDelegation\ApproveDelegation;
use Source\Account\Application\UseCase\Command\ApproveDelegation\ApproveDelegationInput;
use Source\Account\Application\UseCase\Command\ApproveDelegation\ApproveDelegationInterface;
use Source\Account\Domain\Entity\OperationDelegation;
use Source\Account\Domain\Event\DelegationApproved;
use Source\Account\Domain\Repository\DelegationRepositoryInterface;
use Source\Account\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Domain\ValueObject\DelegationStatus;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ApproveDelegationTest extends TestCase
{
    /**
     * 正常系: 正しくDIが動作すること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $delegationRepository = Mockery::mock(DelegationRepositoryInterface::class);
        $this->app->instance(DelegationRepositoryInterface::class, $delegationRepository);
        $useCase = $this->app->make(ApproveDelegationInterface::class);
        $this->assertInstanceOf(ApproveDelegation::class, $useCase);
    }

    /**
     * 正常系: delegatorがデリゲーションを承認できること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $testData = $this->createTestData();

        $delegationRepository = Mockery::mock(DelegationRepositoryInterface::class);
        $delegationRepository->shouldReceive('findById')
            ->with($testData->delegationIdentifier)
            ->once()
            ->andReturn($testData->delegation);
        $delegationRepository->shouldReceive('save')
            ->once()
            ->with($testData->delegation);

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::type(DelegationApproved::class));

        $this->app->instance(DelegationRepositoryInterface::class, $delegationRepository);
        $this->app->instance(EventDispatcherInterface::class, $eventDispatcher);

        $useCase = $this->app->make(ApproveDelegationInterface::class);

        $result = $useCase->process($testData->input);

        $this->assertTrue($result->isApproved());
        $this->assertNotNull($result->approvedAt());
    }

    /**
     * 異常系: デリゲーションが存在しない場合、例外がスローされること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsWhenDelegationNotFound(): void
    {
        $delegationIdentifier = new DelegationIdentifier(StrTestHelper::generateUuid());
        $approverIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $input = new ApproveDelegationInput($delegationIdentifier, $approverIdentifier);

        $delegationRepository = Mockery::mock(DelegationRepositoryInterface::class);
        $delegationRepository->shouldReceive('findById')
            ->with($delegationIdentifier)
            ->once()
            ->andReturnNull();

        $this->app->instance(DelegationRepositoryInterface::class, $delegationRepository);

        $useCase = $this->app->make(ApproveDelegationInterface::class);

        $this->expectException(DelegationNotFoundException::class);
        $this->expectExceptionMessage('Delegation not found.');

        $useCase->process($input);
    }

    /**
     * 異常系: delegator以外が承認しようとした場合、例外がスローされること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsWhenUnauthorizedApprover(): void
    {
        $testData = $this->createTestData();
        $unauthorizedApproverIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $input = new ApproveDelegationInput(
            $testData->delegationIdentifier,
            $unauthorizedApproverIdentifier,
        );

        $delegationRepository = Mockery::mock(DelegationRepositoryInterface::class);
        $delegationRepository->shouldReceive('findById')
            ->with($testData->delegationIdentifier)
            ->once()
            ->andReturn($testData->delegation);
        $delegationRepository->shouldNotReceive('save');

        $this->app->instance(DelegationRepositoryInterface::class, $delegationRepository);

        $useCase = $this->app->make(ApproveDelegationInterface::class);

        $this->expectException(DisallowedDelegationOperationException::class);
        $this->expectExceptionMessage('Only the delegator can approve this delegation.');

        $useCase->process($input);
    }

    private function createTestData(): ApproveDelegationTestData
    {
        $delegationIdentifier = new DelegationIdentifier(StrTestHelper::generateUuid());
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $delegateIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegatorIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

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

        $input = new ApproveDelegationInput(
            $delegationIdentifier,
            $delegatorIdentifier,
        );

        return new ApproveDelegationTestData(
            $delegationIdentifier,
            $affiliationIdentifier,
            $delegateIdentifier,
            $delegatorIdentifier,
            $delegation,
            $input,
        );
    }
}

readonly class ApproveDelegationTestData
{
    public function __construct(
        public DelegationIdentifier $delegationIdentifier,
        public AffiliationIdentifier $affiliationIdentifier,
        public IdentityIdentifier $delegateIdentifier,
        public IdentityIdentifier $delegatorIdentifier,
        public OperationDelegation $delegation,
        public ApproveDelegationInput $input,
    ) {
    }
}
