<?php

declare(strict_types=1);

namespace Tests\Wiki\ImageHideRequest\Application\UseCase\Command\ApproveImageHideRequest;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\Repository\ImageRepositoryInterface;
use Source\Wiki\Image\Domain\Service\ImageAuthorizationResourceBuilderInterface;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\ImageHideRequest\Application\Exception\ImageHideRequestInvalidStatusException;
use Source\Wiki\ImageHideRequest\Application\Exception\ImageHideRequestNotFoundException;
use Source\Wiki\ImageHideRequest\Application\UseCase\Command\ApproveImageHideRequest\ApproveImageHideRequest;
use Source\Wiki\ImageHideRequest\Application\UseCase\Command\ApproveImageHideRequest\ApproveImageHideRequestInput;
use Source\Wiki\ImageHideRequest\Application\UseCase\Command\ApproveImageHideRequest\ApproveImageHideRequestInterface;
use Source\Wiki\ImageHideRequest\Domain\Entity\ImageHideRequest;
use Source\Wiki\ImageHideRequest\Domain\Repository\ImageHideRequestRepositoryInterface;
use Source\Wiki\ImageHideRequest\Domain\ValueObject\ImageHideRequestIdentifier;
use Source\Wiki\ImageHideRequest\Domain\ValueObject\ImageHideRequestStatus;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ApproveImageHideRequestTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $imageHideRequestRepository = Mockery::mock(ImageHideRequestRepositoryInterface::class);
        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);

        $this->app->instance(ImageHideRequestRepositoryInterface::class, $imageHideRequestRepository);
        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

        $approveImageHideRequest = $this->app->make(ApproveImageHideRequestInterface::class);
        $this->assertInstanceOf(ApproveImageHideRequest::class, $approveImageHideRequest);
    }

    /**
     * 正常系：正しくImageHideRequestがApprovedステータスに変更され、Imageが非表示になること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ImageHideRequestNotFoundException
     * @throws ImageHideRequestInvalidStatusException
     * @throws ImageNotFoundException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcess(): void
    {
        $testData = $this->createTestData();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);
        $resource = new Resource(type: ResourceType::IMAGE);
        $reviewerComment = 'Approved for valid reason.';

        $input = new ApproveImageHideRequestInput(
            $testData->requestIdentifier,
            $principalIdentifier,
            $reviewerComment,
        );

        $imageHideRequestRepository = Mockery::mock(ImageHideRequestRepositoryInterface::class);
        $imageHideRequestRepository->shouldReceive('findById')
            ->once()
            ->with($testData->requestIdentifier)
            ->andReturn($testData->imageHideRequest);
        $imageHideRequestRepository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $imageRepository->shouldReceive('findById')
            ->once()
            ->with($testData->imageIdentifier)
            ->andReturn($testData->image);
        $imageRepository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->with($principalIdentifier)
            ->andReturn($principal);

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturn(true);

        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);
        $imageAuthorizationResourceBuilder->shouldReceive('buildFromImage')
            ->once()
            ->with($testData->image)
            ->andReturn($resource);

        $this->app->instance(ImageHideRequestRepositoryInterface::class, $imageHideRequestRepository);
        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

        $approveImageHideRequest = $this->app->make(ApproveImageHideRequestInterface::class);
        $result = $approveImageHideRequest->process($input);

        $this->assertSame(ImageHideRequestStatus::APPROVED, $result->status());
        $this->assertSame((string) $principalIdentifier, (string) $result->reviewerIdentifier());
        $this->assertSame($reviewerComment, $result->reviewerComment());
        $this->assertTrue($testData->image->isHidden());
    }

    /**
     * 異常系：ImageHideRequestが見つからない場合、ImageHideRequestNotFoundExceptionがスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ImageHideRequestInvalidStatusException
     * @throws ImageNotFoundException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessImageHideRequestNotFound(): void
    {
        $requestIdentifier = new ImageHideRequestIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $input = new ApproveImageHideRequestInput($requestIdentifier, $principalIdentifier, 'comment');

        $imageHideRequestRepository = Mockery::mock(ImageHideRequestRepositoryInterface::class);
        $imageHideRequestRepository->shouldReceive('findById')
            ->once()
            ->with($requestIdentifier)
            ->andReturn(null);
        $imageHideRequestRepository->shouldNotReceive('save');

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $imageRepository->shouldNotReceive('findById');
        $imageRepository->shouldNotReceive('save');

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);
        $imageAuthorizationResourceBuilder->shouldNotReceive('buildFromImage');

        $this->app->instance(ImageHideRequestRepositoryInterface::class, $imageHideRequestRepository);
        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

        $this->expectException(ImageHideRequestNotFoundException::class);
        $approveImageHideRequest = $this->app->make(ApproveImageHideRequestInterface::class);
        $approveImageHideRequest->process($input);
    }

    /**
     * 異常系：ImageHideRequestがPendingでない場合、ImageHideRequestInvalidStatusExceptionがスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ImageHideRequestNotFoundException
     * @throws ImageNotFoundException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessInvalidStatus(): void
    {
        $testData = $this->createTestData(ImageHideRequestStatus::APPROVED);
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $input = new ApproveImageHideRequestInput($testData->requestIdentifier, $principalIdentifier, 'comment');

        $imageHideRequestRepository = Mockery::mock(ImageHideRequestRepositoryInterface::class);
        $imageHideRequestRepository->shouldReceive('findById')
            ->once()
            ->with($testData->requestIdentifier)
            ->andReturn($testData->imageHideRequest);
        $imageHideRequestRepository->shouldNotReceive('save');

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $imageRepository->shouldNotReceive('findById');
        $imageRepository->shouldNotReceive('save');

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);
        $imageAuthorizationResourceBuilder->shouldNotReceive('buildFromImage');

        $this->app->instance(ImageHideRequestRepositoryInterface::class, $imageHideRequestRepository);
        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

        $this->expectException(ImageHideRequestInvalidStatusException::class);
        $approveImageHideRequest = $this->app->make(ApproveImageHideRequestInterface::class);
        $approveImageHideRequest->process($input);
    }

    /**
     * 異常系：Imageが見つからない場合、ImageNotFoundExceptionがスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ImageHideRequestNotFoundException
     * @throws ImageHideRequestInvalidStatusException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessImageNotFound(): void
    {
        $testData = $this->createTestData();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $input = new ApproveImageHideRequestInput($testData->requestIdentifier, $principalIdentifier, 'comment');

        $imageHideRequestRepository = Mockery::mock(ImageHideRequestRepositoryInterface::class);
        $imageHideRequestRepository->shouldReceive('findById')
            ->once()
            ->with($testData->requestIdentifier)
            ->andReturn($testData->imageHideRequest);
        $imageHideRequestRepository->shouldNotReceive('save');

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $imageRepository->shouldReceive('findById')
            ->once()
            ->with($testData->imageIdentifier)
            ->andReturn(null);
        $imageRepository->shouldNotReceive('save');

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);
        $imageAuthorizationResourceBuilder->shouldNotReceive('buildFromImage');

        $this->app->instance(ImageHideRequestRepositoryInterface::class, $imageHideRequestRepository);
        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

        $this->expectException(ImageNotFoundException::class);
        $approveImageHideRequest = $this->app->make(ApproveImageHideRequestInterface::class);
        $approveImageHideRequest->process($input);
    }

    /**
     * 異常系：Principalが見つからない場合、PrincipalNotFoundExceptionがスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ImageHideRequestNotFoundException
     * @throws ImageHideRequestInvalidStatusException
     * @throws ImageNotFoundException
     * @throws DisallowedException
     */
    public function testProcessPrincipalNotFound(): void
    {
        $testData = $this->createTestData();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $input = new ApproveImageHideRequestInput($testData->requestIdentifier, $principalIdentifier, 'comment');

        $imageHideRequestRepository = Mockery::mock(ImageHideRequestRepositoryInterface::class);
        $imageHideRequestRepository->shouldReceive('findById')
            ->once()
            ->with($testData->requestIdentifier)
            ->andReturn($testData->imageHideRequest);
        $imageHideRequestRepository->shouldNotReceive('save');

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $imageRepository->shouldReceive('findById')
            ->once()
            ->with($testData->imageIdentifier)
            ->andReturn($testData->image);
        $imageRepository->shouldNotReceive('save');

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->with($principalIdentifier)
            ->andReturn(null);

        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);
        $imageAuthorizationResourceBuilder->shouldNotReceive('buildFromImage');

        $this->app->instance(ImageHideRequestRepositoryInterface::class, $imageHideRequestRepository);
        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

        $this->expectException(PrincipalNotFoundException::class);
        $approveImageHideRequest = $this->app->make(ApproveImageHideRequestInterface::class);
        $approveImageHideRequest->process($input);
    }

    /**
     * 異常系：権限がない場合、DisallowedExceptionがスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ImageHideRequestNotFoundException
     * @throws ImageHideRequestInvalidStatusException
     * @throws ImageNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function testProcessDisallowed(): void
    {
        $testData = $this->createTestData();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);
        $resource = new Resource(type: ResourceType::IMAGE);

        $input = new ApproveImageHideRequestInput($testData->requestIdentifier, $principalIdentifier, 'comment');

        $imageHideRequestRepository = Mockery::mock(ImageHideRequestRepositoryInterface::class);
        $imageHideRequestRepository->shouldReceive('findById')
            ->once()
            ->with($testData->requestIdentifier)
            ->andReturn($testData->imageHideRequest);
        $imageHideRequestRepository->shouldNotReceive('save');

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $imageRepository->shouldReceive('findById')
            ->once()
            ->with($testData->imageIdentifier)
            ->andReturn($testData->image);
        $imageRepository->shouldNotReceive('save');

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->with($principalIdentifier)
            ->andReturn($principal);

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturn(false);

        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);
        $imageAuthorizationResourceBuilder->shouldReceive('buildFromImage')
            ->once()
            ->with($testData->image)
            ->andReturn($resource);

        $this->app->instance(ImageHideRequestRepositoryInterface::class, $imageHideRequestRepository);
        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

        $this->expectException(DisallowedException::class);
        $approveImageHideRequest = $this->app->make(ApproveImageHideRequestInterface::class);
        $approveImageHideRequest->process($input);
    }

    /**
     * @param ImageHideRequestStatus $status
     * @return ApproveImageHideRequestTestData
     */
    private function createTestData(ImageHideRequestStatus $status = ImageHideRequestStatus::PENDING): ApproveImageHideRequestTestData
    {
        $requestIdentifier = new ImageHideRequestIdentifier(StrTestHelper::generateUuid());
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());

        $imageHideRequest = new ImageHideRequest(
            $requestIdentifier,
            $imageIdentifier,
            'Test Requester',
            'requester@example.com',
            'Privacy concern',
            $status,
            new DateTimeImmutable(),
            null,
            null,
            null,
        );

        $image = new Image(
            $imageIdentifier,
            ResourceType::TALENT,
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new ImagePath('images/test.png'),
            ImageUsage::PROFILE,
            1,
            'https://example.com/source',
            'Example Source',
            'Profile image of talent',
            false,
            null,
            null,
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new DateTimeImmutable(),
            null,
            null,
            null,
            null,
        );

        return new ApproveImageHideRequestTestData(
            $requestIdentifier,
            $imageIdentifier,
            $imageHideRequest,
            $image,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class ApproveImageHideRequestTestData
{
    public function __construct(
        public ImageHideRequestIdentifier $requestIdentifier,
        public ImageIdentifier $imageIdentifier,
        public ImageHideRequest $imageHideRequest,
        public Image $image,
    ) {
    }
}
