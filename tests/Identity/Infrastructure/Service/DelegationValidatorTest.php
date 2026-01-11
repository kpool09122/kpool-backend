<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Affiliation\Domain\Entity\Affiliation;
use Source\Account\Affiliation\Domain\Repository\AffiliationRepositoryInterface;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationStatus;
use Source\Account\Delegation\Domain\Entity\Delegation;
use Source\Account\Delegation\Domain\Repository\DelegationRepositoryInterface;
use Source\Account\Delegation\Domain\ValueObject\DelegationDirection;
use Source\Account\Delegation\Domain\ValueObject\DelegationStatus;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Identity\Application\Service\DelegationValidatorInterface;
use Source\Identity\Infrastructure\Service\DelegationValidator;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DelegationValidatorTest extends TestCase
{
    /**
     * 正しくDIが動作していること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $delegationRepository = Mockery::mock(DelegationRepositoryInterface::class);
        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);

        $this->app->instance(DelegationRepositoryInterface::class, $delegationRepository);
        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);

        $validator = $this->app->make(DelegationValidatorInterface::class);

        $this->assertInstanceOf(DelegationValidator::class, $validator);
    }

    /**
     * 正常系: 委譲が承認済みかつ所属がアクティブの場合trueを返すこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testIsValidWhenDelegationIsApprovedAndAffiliationIsActive(): void
    {
        $delegationId = new DelegationIdentifier(StrTestHelper::generateUuid());
        $affiliationId = new AffiliationIdentifier(StrTestHelper::generateUuid());

        $delegation = $this->createDelegation($delegationId, $affiliationId, DelegationStatus::APPROVED);
        $affiliation = $this->createAffiliation($affiliationId, AffiliationStatus::ACTIVE);

        $delegationRepository = Mockery::mock(DelegationRepositoryInterface::class);
        $delegationRepository->shouldReceive('findById')
            ->once()
            ->with($delegationId)
            ->andReturn($delegation);

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationRepository->shouldReceive('findById')
            ->once()
            ->with($affiliationId)
            ->andReturn($affiliation);

        $this->app->instance(DelegationRepositoryInterface::class, $delegationRepository);
        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);

        $validator = $this->app->make(DelegationValidatorInterface::class);

        $result = $validator->isValid($delegationId);

        $this->assertTrue($result);
    }

    /**
     * 異常系: 委譲が見つからない場合falseを返すこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testIsValidReturnsFalseWhenDelegationNotFound(): void
    {
        $delegationId = new DelegationIdentifier(StrTestHelper::generateUuid());

        $delegationRepository = Mockery::mock(DelegationRepositoryInterface::class);
        $delegationRepository->shouldReceive('findById')
            ->once()
            ->with($delegationId)
            ->andReturnNull();

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);

        $this->app->instance(DelegationRepositoryInterface::class, $delegationRepository);
        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);

        $validator = $this->app->make(DelegationValidatorInterface::class);

        $result = $validator->isValid($delegationId);

        $this->assertFalse($result);
    }

    /**
     * 異常系: 委譲がPendingの場合falseを返すこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testIsValidReturnsFalseWhenDelegationIsPending(): void
    {
        $delegationId = new DelegationIdentifier(StrTestHelper::generateUuid());
        $affiliationId = new AffiliationIdentifier(StrTestHelper::generateUuid());

        $delegation = $this->createDelegation($delegationId, $affiliationId, DelegationStatus::PENDING);

        $delegationRepository = Mockery::mock(DelegationRepositoryInterface::class);
        $delegationRepository->shouldReceive('findById')
            ->once()
            ->with($delegationId)
            ->andReturn($delegation);

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);

        $this->app->instance(DelegationRepositoryInterface::class, $delegationRepository);
        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);

        $validator = $this->app->make(DelegationValidatorInterface::class);

        $result = $validator->isValid($delegationId);

        $this->assertFalse($result);
    }

    /**
     * 異常系: 委譲が取り消し済みの場合falseを返すこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testIsValidReturnsFalseWhenDelegationIsRevoked(): void
    {
        $delegationId = new DelegationIdentifier(StrTestHelper::generateUuid());
        $affiliationId = new AffiliationIdentifier(StrTestHelper::generateUuid());

        $delegation = $this->createDelegation($delegationId, $affiliationId, DelegationStatus::REVOKED);

        $delegationRepository = Mockery::mock(DelegationRepositoryInterface::class);
        $delegationRepository->shouldReceive('findById')
            ->once()
            ->with($delegationId)
            ->andReturn($delegation);

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);

        $this->app->instance(DelegationRepositoryInterface::class, $delegationRepository);
        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);

        $validator = $this->app->make(DelegationValidatorInterface::class);

        $result = $validator->isValid($delegationId);

        $this->assertFalse($result);
    }

    /**
     * 異常系: 所属が見つからない場合falseを返すこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testIsValidReturnsFalseWhenAffiliationNotFound(): void
    {
        $delegationId = new DelegationIdentifier(StrTestHelper::generateUuid());
        $affiliationId = new AffiliationIdentifier(StrTestHelper::generateUuid());

        $delegation = $this->createDelegation($delegationId, $affiliationId, DelegationStatus::APPROVED);

        $delegationRepository = Mockery::mock(DelegationRepositoryInterface::class);
        $delegationRepository->shouldReceive('findById')
            ->once()
            ->with($delegationId)
            ->andReturn($delegation);

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationRepository->shouldReceive('findById')
            ->once()
            ->with($affiliationId)
            ->andReturnNull();

        $this->app->instance(DelegationRepositoryInterface::class, $delegationRepository);
        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);

        $validator = $this->app->make(DelegationValidatorInterface::class);

        $result = $validator->isValid($delegationId);

        $this->assertFalse($result);
    }

    /**
     * 異常系: 所属がPendingの場合falseを返すこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testIsValidReturnsFalseWhenAffiliationIsPending(): void
    {
        $delegationId = new DelegationIdentifier(StrTestHelper::generateUuid());
        $affiliationId = new AffiliationIdentifier(StrTestHelper::generateUuid());

        $delegation = $this->createDelegation($delegationId, $affiliationId, DelegationStatus::APPROVED);
        $affiliation = $this->createAffiliation($affiliationId, AffiliationStatus::PENDING);

        $delegationRepository = Mockery::mock(DelegationRepositoryInterface::class);
        $delegationRepository->shouldReceive('findById')
            ->once()
            ->with($delegationId)
            ->andReturn($delegation);

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationRepository->shouldReceive('findById')
            ->once()
            ->with($affiliationId)
            ->andReturn($affiliation);

        $this->app->instance(DelegationRepositoryInterface::class, $delegationRepository);
        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);

        $validator = $this->app->make(DelegationValidatorInterface::class);

        $result = $validator->isValid($delegationId);

        $this->assertFalse($result);
    }

    /**
     * 異常系: 所属が終了済みの場合falseを返すこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testIsValidReturnsFalseWhenAffiliationIsTerminated(): void
    {
        $delegationId = new DelegationIdentifier(StrTestHelper::generateUuid());
        $affiliationId = new AffiliationIdentifier(StrTestHelper::generateUuid());

        $delegation = $this->createDelegation($delegationId, $affiliationId, DelegationStatus::APPROVED);
        $affiliation = $this->createAffiliation($affiliationId, AffiliationStatus::TERMINATED);

        $delegationRepository = Mockery::mock(DelegationRepositoryInterface::class);
        $delegationRepository->shouldReceive('findById')
            ->once()
            ->with($delegationId)
            ->andReturn($delegation);

        $affiliationRepository = Mockery::mock(AffiliationRepositoryInterface::class);
        $affiliationRepository->shouldReceive('findById')
            ->once()
            ->with($affiliationId)
            ->andReturn($affiliation);

        $this->app->instance(DelegationRepositoryInterface::class, $delegationRepository);
        $this->app->instance(AffiliationRepositoryInterface::class, $affiliationRepository);

        $validator = $this->app->make(DelegationValidatorInterface::class);

        $result = $validator->isValid($delegationId);

        $this->assertFalse($result);
    }

    private function createDelegation(
        DelegationIdentifier $delegationId,
        AffiliationIdentifier $affiliationId,
        DelegationStatus $status,
    ): Delegation {
        return new Delegation(
            $delegationId,
            $affiliationId,
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            $status,
            DelegationDirection::FROM_AGENCY,
            new DateTimeImmutable(),
            $status->isApproved() ? new DateTimeImmutable() : null,
            $status->isRevoked() ? new DateTimeImmutable() : null,
        );
    }

    private function createAffiliation(
        AffiliationIdentifier $affiliationId,
        AffiliationStatus $status,
    ): Affiliation {
        $agencyAccountId = new AccountIdentifier(StrTestHelper::generateUuid());

        return new Affiliation(
            $affiliationId,
            $agencyAccountId,
            new AccountIdentifier(StrTestHelper::generateUuid()),
            $agencyAccountId,
            $status,
            null,
            new DateTimeImmutable(),
            $status->isActive() ? new DateTimeImmutable() : null,
            $status->isTerminated() ? new DateTimeImmutable() : null,
        );
    }
}
