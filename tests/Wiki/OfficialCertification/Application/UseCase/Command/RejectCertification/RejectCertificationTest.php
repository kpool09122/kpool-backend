<?php

declare(strict_types=1);

namespace Tests\Wiki\OfficialCertification\Application\UseCase\Command\RejectCertification;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationInvalidStatusException;
use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationNotFoundException;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\RejectCertification\RejectCertification;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\RejectCertification\RejectCertificationInput;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\RejectCertification\RejectCertificationInterface;
use Source\Wiki\OfficialCertification\Domain\Entity\OfficialCertification;
use Source\Wiki\OfficialCertification\Domain\Repository\OfficialCertificationRepositoryInterface;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationIdentifier;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
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
        $certification = new OfficialCertification(
            $certificationId,
            ResourceType::TALENT,
            new ResourceIdentifier(StrTestHelper::generateUuid()),
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

        $useCase = $this->app->make(RejectCertificationInterface::class);

        $input = new RejectCertificationInput($certificationId);

        $result = $useCase->process($input);

        $this->assertTrue($result->isRejected());
        $this->assertNotNull($result->rejectedAt());
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

        $input = new RejectCertificationInput($certificationId);

        $this->expectException(OfficialCertificationNotFoundException::class);

        $useCase->process($input);
    }

    public function testProcessWhenInvalidStatus(): void
    {
        $certificationId = new CertificationIdentifier(StrTestHelper::generateUuid());
        $certification = new OfficialCertification(
            $certificationId,
            ResourceType::TALENT,
            new ResourceIdentifier(StrTestHelper::generateUuid()),
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

        $input = new RejectCertificationInput($certificationId);

        $this->expectException(OfficialCertificationInvalidStatusException::class);

        $useCase->process($input);
    }
}
