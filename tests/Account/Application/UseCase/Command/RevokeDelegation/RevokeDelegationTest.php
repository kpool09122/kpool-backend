<?php

declare(strict_types=1);

namespace Tests\Account\Application\UseCase\Command\RevokeDelegation;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Application\Exception\DelegationNotFoundException;
use Source\Account\Application\Exception\DisallowedDelegationOperationException;
use Source\Account\Application\UseCase\Command\RevokeDelegation\RevokeDelegation;
use Source\Account\Application\UseCase\Command\RevokeDelegation\RevokeDelegationInput;
use Source\Account\Application\UseCase\Command\RevokeDelegation\RevokeDelegationInterface;
use Source\Account\Domain\Entity\OperationDelegation;
use Source\Account\Domain\Event\DelegationRevoked;
use Source\Account\Domain\Repository\DelegationRepositoryInterface;
use Source\Account\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Domain\ValueObject\DelegationDirection;
use Source\Account\Domain\ValueObject\DelegationStatus;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RevokeDelegationTest extends TestCase
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
        $useCase = $this->app->make(RevokeDelegationInterface::class);
        $this->assertInstanceOf(RevokeDelegation::class, $useCase);
    }

    /**
     * 正常系: delegatorがデリゲーションを取り消しできること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessByDelegator(): void
    {
        $testData = $this->createTestData();
        $input = new RevokeDelegationInput(
            $testData->delegationIdentifier,
            $testData->delegatorIdentifier,
        );

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
            ->with(Mockery::type(DelegationRevoked::class));

        $this->app->instance(DelegationRepositoryInterface::class, $delegationRepository);
        $this->app->instance(EventDispatcherInterface::class, $eventDispatcher);

        $useCase = $this->app->make(RevokeDelegationInterface::class);

        $result = $useCase->process($input);

        $this->assertTrue($result->isRevoked());
        $this->assertNotNull($result->revokedAt());
    }

    /**
     * 正常系: delegateがデリゲーションを取り消しできること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessByDelegate(): void
    {
        $testData = $this->createTestData();
        $input = new RevokeDelegationInput(
            $testData->delegationIdentifier,
            $testData->delegateIdentifier,
        );

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
            ->with(Mockery::type(DelegationRevoked::class));

        $this->app->instance(DelegationRepositoryInterface::class, $delegationRepository);
        $this->app->instance(EventDispatcherInterface::class, $eventDispatcher);

        $useCase = $this->app->make(RevokeDelegationInterface::class);

        $result = $useCase->process($input);

        $this->assertTrue($result->isRevoked());
        $this->assertNotNull($result->revokedAt());
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
        $revokerIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $input = new RevokeDelegationInput($delegationIdentifier, $revokerIdentifier);

        $delegationRepository = Mockery::mock(DelegationRepositoryInterface::class);
        $delegationRepository->shouldReceive('findById')
            ->with($delegationIdentifier)
            ->once()
            ->andReturnNull();

        $this->app->instance(DelegationRepositoryInterface::class, $delegationRepository);

        $useCase = $this->app->make(RevokeDelegationInterface::class);

        $this->expectException(DelegationNotFoundException::class);
        $this->expectExceptionMessage('Delegation not found.');

        $useCase->process($input);
    }

    /**
     * 異常系: delegatorでもdelegateでもない場合、例外がスローされること
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsWhenUnauthorizedRevoker(): void
    {
        $testData = $this->createTestData();
        $unauthorizedRevokerIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $input = new RevokeDelegationInput(
            $testData->delegationIdentifier,
            $unauthorizedRevokerIdentifier,
        );

        $delegationRepository = Mockery::mock(DelegationRepositoryInterface::class);
        $delegationRepository->shouldReceive('findById')
            ->with($testData->delegationIdentifier)
            ->once()
            ->andReturn($testData->delegation);
        $delegationRepository->shouldNotReceive('save');

        $this->app->instance(DelegationRepositoryInterface::class, $delegationRepository);

        $useCase = $this->app->make(RevokeDelegationInterface::class);

        $this->expectException(DisallowedDelegationOperationException::class);
        $this->expectExceptionMessage('Only the delegator or delegate can revoke this delegation.');

        $useCase->process($input);
    }

    private function createTestData(): RevokeDelegationTestData
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
            DelegationStatus::APPROVED,
            DelegationDirection::FROM_AGENCY,
            new DateTimeImmutable('-1 day'),
            new DateTimeImmutable(),
            null,
        );

        return new RevokeDelegationTestData(
            $delegationIdentifier,
            $affiliationIdentifier,
            $delegateIdentifier,
            $delegatorIdentifier,
            $delegation,
        );
    }
}

readonly class RevokeDelegationTestData
{
    public function __construct(
        public DelegationIdentifier $delegationIdentifier,
        public AffiliationIdentifier $affiliationIdentifier,
        public IdentityIdentifier $delegateIdentifier,
        public IdentityIdentifier $delegatorIdentifier,
        public OperationDelegation $delegation,
    ) {
    }
}
