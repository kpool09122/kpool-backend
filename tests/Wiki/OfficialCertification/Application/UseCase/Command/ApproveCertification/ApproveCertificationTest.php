<?php

declare(strict_types=1);

namespace Tests\Wiki\OfficialCertification\Application\UseCase\Command\ApproveCertification;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationInvalidStatusException;
use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationNotFoundException;
use Source\Wiki\OfficialCertification\Application\Service\OfficialResourceUpdaterInterface;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\ApproveCertification\ApproveCertification;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\ApproveCertification\ApproveCertificationInput;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\ApproveCertification\ApproveCertificationInterface;
use Source\Wiki\OfficialCertification\Domain\Entity\OfficialCertification;
use Source\Wiki\OfficialCertification\Domain\Repository\OfficialCertificationRepositoryInterface;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationIdentifier;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ApproveCertificationTest extends TestCase
{
    /**
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = Mockery::mock(OfficialCertificationRepositoryInterface::class);
        $resourceUpdater = Mockery::mock(OfficialResourceUpdaterInterface::class);
        $this->app->instance(OfficialCertificationRepositoryInterface::class, $repository);
        $this->app->instance(OfficialResourceUpdaterInterface::class, $resourceUpdater);

        $useCase = $this->app->make(ApproveCertificationInterface::class);

        $this->assertInstanceOf(ApproveCertification::class, $useCase);
    }

    public function testProcess(): void
    {
        $certificationId = new CertificationIdentifier(StrTestHelper::generateUuid());
        $resourceId = new ResourceIdentifier(StrTestHelper::generateUuid());
        $ownerAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $certification = new OfficialCertification(
            $certificationId,
            ResourceType::SONG,
            $resourceId,
            $ownerAccountIdentifier,
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

        $resourceUpdater = Mockery::mock(OfficialResourceUpdaterInterface::class);
        $resourceUpdater->shouldReceive('markOfficial')
            ->once()
            ->with(ResourceType::SONG, $resourceId, $ownerAccountIdentifier)
            ->andReturnNull();

        $this->app->instance(OfficialCertificationRepositoryInterface::class, $repository);
        $this->app->instance(OfficialResourceUpdaterInterface::class, $resourceUpdater);

        $useCase = $this->app->make(ApproveCertificationInterface::class);

        $input = new ApproveCertificationInput($certificationId);

        $result = $useCase->process($input);

        $this->assertTrue($result->isApproved());
        $this->assertNotNull($result->approvedAt());
    }

    public function testProcessWhenNotFound(): void
    {
        $certificationId = new CertificationIdentifier(StrTestHelper::generateUuid());

        $repository = Mockery::mock(OfficialCertificationRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with($certificationId)
            ->andReturnNull();

        $resourceUpdater = Mockery::mock(OfficialResourceUpdaterInterface::class);

        $this->app->instance(OfficialCertificationRepositoryInterface::class, $repository);
        $this->app->instance(OfficialResourceUpdaterInterface::class, $resourceUpdater);

        $useCase = $this->app->make(ApproveCertificationInterface::class);

        $input = new ApproveCertificationInput($certificationId);

        $this->expectException(OfficialCertificationNotFoundException::class);

        $useCase->process($input);
    }

    public function testProcessWhenInvalidStatus(): void
    {
        $certificationId = new CertificationIdentifier(StrTestHelper::generateUuid());
        $certification = new OfficialCertification(
            $certificationId,
            ResourceType::GROUP,
            new ResourceIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            CertificationStatus::APPROVED,
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            null,
        );

        $repository = Mockery::mock(OfficialCertificationRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with($certificationId)
            ->andReturn($certification);

        $resourceUpdater = Mockery::mock(OfficialResourceUpdaterInterface::class);

        $this->app->instance(OfficialCertificationRepositoryInterface::class, $repository);
        $this->app->instance(OfficialResourceUpdaterInterface::class, $resourceUpdater);

        $useCase = $this->app->make(ApproveCertificationInterface::class);

        $input = new ApproveCertificationInput($certificationId);

        $this->expectException(OfficialCertificationInvalidStatusException::class);

        $useCase->process($input);
    }
}
