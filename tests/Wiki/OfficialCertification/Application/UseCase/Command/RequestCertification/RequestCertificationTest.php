<?php

declare(strict_types=1);

namespace Tests\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationAlreadyRequestedException;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification\RequestCertification;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification\RequestCertificationInput;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification\RequestCertificationInterface;
use Source\Wiki\OfficialCertification\Domain\Entity\OfficialCertification;
use Source\Wiki\OfficialCertification\Domain\Factory\OfficialCertificationFactoryInterface;
use Source\Wiki\OfficialCertification\Domain\Repository\OfficialCertificationRepositoryInterface;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationIdentifier;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RequestCertificationTest extends TestCase
{
    /**
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = Mockery::mock(OfficialCertificationRepositoryInterface::class);
        $factory = Mockery::mock(OfficialCertificationFactoryInterface::class);
        $this->app->instance(OfficialCertificationRepositoryInterface::class, $repository);
        $this->app->instance(OfficialCertificationFactoryInterface::class, $factory);

        $useCase = $this->app->make(RequestCertificationInterface::class);

        $this->assertInstanceOf(RequestCertification::class, $useCase);
    }

    public function testProcess(): void
    {
        $certificationId = StrTestHelper::generateUuid();
        $resourceId = new ResourceIdentifier(StrTestHelper::generateUuid());
        $ownerAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $certification = new OfficialCertification(
            new CertificationIdentifier($certificationId),
            ResourceType::AGENCY,
            $resourceId,
            $ownerAccountIdentifier,
            CertificationStatus::PENDING,
            new DateTimeImmutable(),
            null,
            null,
        );

        $repository = Mockery::mock(OfficialCertificationRepositoryInterface::class);
        $repository->shouldReceive('findByResource')
            ->once()
            ->with(ResourceType::AGENCY, $resourceId)
            ->andReturnNull();
        $repository->shouldReceive('save')
            ->once()
            ->with($certification)
            ->andReturnNull();

        $factory = Mockery::mock(OfficialCertificationFactoryInterface::class);
        $factory->shouldReceive('create')
            ->once()
            ->with(ResourceType::AGENCY, $resourceId, $ownerAccountIdentifier)
            ->andReturn($certification);

        $this->app->instance(OfficialCertificationRepositoryInterface::class, $repository);
        $this->app->instance(OfficialCertificationFactoryInterface::class, $factory);

        $useCase = $this->app->make(RequestCertificationInterface::class);

        $input = new RequestCertificationInput(
            ResourceType::AGENCY,
            $resourceId,
            $ownerAccountIdentifier,
        );

        $certification = $useCase->process($input);

        $this->assertSame($certificationId, (string) $certification->certificationIdentifier());
        $this->assertTrue($certification->status()->isPending());
    }

    public function testProcessWhenAlreadyRequested(): void
    {
        $resourceId = new ResourceIdentifier(StrTestHelper::generateUuid());
        $existing = new OfficialCertification(
            new CertificationIdentifier(StrTestHelper::generateUuid()),
            ResourceType::AGENCY,
            $resourceId,
            new AccountIdentifier(StrTestHelper::generateUuid()),
            CertificationStatus::PENDING,
            new DateTimeImmutable(),
            null,
            null,
        );

        $repository = Mockery::mock(OfficialCertificationRepositoryInterface::class);
        $repository->shouldReceive('findByResource')
            ->once()
            ->with(ResourceType::AGENCY, $resourceId)
            ->andReturn($existing);

        $factory = Mockery::mock(OfficialCertificationFactoryInterface::class);

        $this->app->instance(OfficialCertificationRepositoryInterface::class, $repository);
        $this->app->instance(OfficialCertificationFactoryInterface::class, $factory);

        $useCase = $this->app->make(RequestCertificationInterface::class);

        $input = new RequestCertificationInput(
            ResourceType::AGENCY,
            $resourceId,
            new AccountIdentifier(StrTestHelper::generateUuid()),
        );

        $this->expectException(OfficialCertificationAlreadyRequestedException::class);

        $useCase->process($input);
    }
}
