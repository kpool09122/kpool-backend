<?php

declare(strict_types=1);

namespace Tests\Wiki\OfficialCertification\Application\UseCase\Command\RejectCertification;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationInvalidStatusException;
use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationNotFoundException;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\RejectCertification\RejectCertification;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\RejectCertification\RejectCertificationInput;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\RejectCertification\RejectCertificationInterface;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\RejectCertification\RejectCertificationOutput;
use Source\Wiki\OfficialCertification\Domain\Entity\OfficialCertification;
use Source\Wiki\OfficialCertification\Domain\Repository\OfficialCertificationRepositoryInterface;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationIdentifier;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationStatus;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RejectCertificationTest extends TestCase
{
    /**
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = Mockery::mock(OfficialCertificationRepositoryInterface::class);
        $this->app->instance(OfficialCertificationRepositoryInterface::class, $repository);

        $useCase = $this->app->make(RejectCertificationInterface::class);

        $this->assertInstanceOf(RejectCertification::class, $useCase);
    }

    public function testProcess(): void
    {
        $certificationId = new CertificationIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $certification = new OfficialCertification(
            $certificationId,
            ResourceType::TALENT,
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            CertificationStatus::PENDING,
            new DateTimeImmutable(),
            null,
            null,
        );

        $repository = Mockery::mock(OfficialCertificationRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with($certificationId)
            ->andReturn($certification);
        $repository->shouldReceive('save')
            ->once()
            ->with($certification)
            ->andReturnNull();

        $this->app->instance(OfficialCertificationRepositoryInterface::class, $repository);
        $this->registerOperatorAuthorization($principalIdentifier, true);

        $useCase = $this->app->make(RejectCertificationInterface::class);

        $input = new RejectCertificationInput($certificationId, $principalIdentifier);
        $output = new RejectCertificationOutput();

        $useCase->process($input, $output);

        $this->assertTrue($certification->isRejected());
        $this->assertNotNull($certification->rejectedAt());
    }

    public function testProcessWhenNotFound(): void
    {
        $certificationId = new CertificationIdentifier(StrTestHelper::generateUuid());

        $repository = Mockery::mock(OfficialCertificationRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with($certificationId)
            ->andReturnNull();

        $this->app->instance(OfficialCertificationRepositoryInterface::class, $repository);

        $useCase = $this->app->make(RejectCertificationInterface::class);

        $input = new RejectCertificationInput($certificationId, new PrincipalIdentifier(StrTestHelper::generateUuid()));

        $output = new RejectCertificationOutput();

        $this->expectException(OfficialCertificationNotFoundException::class);

        $useCase->process($input, $output);
    }

    public function testProcessWhenInvalidStatus(): void
    {
        $certificationId = new CertificationIdentifier(StrTestHelper::generateUuid());
        $certification = new OfficialCertification(
            $certificationId,
            ResourceType::TALENT,
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            CertificationStatus::REJECTED,
            new DateTimeImmutable(),
            null,
            new DateTimeImmutable(),
        );

        $repository = Mockery::mock(OfficialCertificationRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with($certificationId)
            ->andReturn($certification);

        $this->app->instance(OfficialCertificationRepositoryInterface::class, $repository);

        $useCase = $this->app->make(RejectCertificationInterface::class);

        $input = new RejectCertificationInput($certificationId, new PrincipalIdentifier(StrTestHelper::generateUuid()));

        $output = new RejectCertificationOutput();

        $this->expectException(OfficialCertificationInvalidStatusException::class);

        $useCase->process($input, $output);
    }

    public function testProcessWhenOperatorPolicyDenies(): void
    {
        $certificationId = new CertificationIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $certification = new OfficialCertification(
            $certificationId,
            ResourceType::TALENT,
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            CertificationStatus::PENDING,
            new DateTimeImmutable(),
            null,
            null,
        );

        $repository = Mockery::mock(OfficialCertificationRepositoryInterface::class);
        $repository->shouldReceive('findById')->with($certificationId)->andReturn($certification);
        $this->app->instance(OfficialCertificationRepositoryInterface::class, $repository);
        $this->registerOperatorAuthorization($principalIdentifier, false);

        $useCase = $this->app->make(RejectCertificationInterface::class);
        $input = new RejectCertificationInput($certificationId, $principalIdentifier);
        $output = new RejectCertificationOutput();

        $this->expectException(DisallowedException::class);

        $useCase->process($input, $output);
    }

    private function registerOperatorAuthorization(PrincipalIdentifier $principalIdentifier, bool $policyAllowed): void
    {
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()));
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')->with($principalIdentifier)->andReturn($principal);

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->andReturn($policyAllowed);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
    }
}
