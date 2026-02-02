<?php

declare(strict_types=1);

namespace Tests\Wiki\ImageHideRequest\Application\UseCase\Command\RequestImageHide;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\Repository\ImageRepositoryInterface;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\ImageHideRequest\Application\Exception\ImageHideRequestAlreadyPendingException;
use Source\Wiki\ImageHideRequest\Application\UseCase\Command\RequestImageHide\RequestImageHide;
use Source\Wiki\ImageHideRequest\Application\UseCase\Command\RequestImageHide\RequestImageHideInput;
use Source\Wiki\ImageHideRequest\Application\UseCase\Command\RequestImageHide\RequestImageHideInterface;
use Source\Wiki\ImageHideRequest\Domain\Entity\ImageHideRequest;
use Source\Wiki\ImageHideRequest\Domain\Factory\ImageHideRequestFactoryInterface;
use Source\Wiki\ImageHideRequest\Domain\Repository\ImageHideRequestRepositoryInterface;
use Source\Wiki\ImageHideRequest\Domain\ValueObject\ImageHideRequestIdentifier;
use Source\Wiki\ImageHideRequest\Domain\ValueObject\ImageHideRequestStatus;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RequestImageHideTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $imageHideRequestRepository = Mockery::mock(ImageHideRequestRepositoryInterface::class);
        $imageHideRequestFactory = Mockery::mock(ImageHideRequestFactoryInterface::class);

        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(ImageHideRequestRepositoryInterface::class, $imageHideRequestRepository);
        $this->app->instance(ImageHideRequestFactoryInterface::class, $imageHideRequestFactory);

        $requestImageHide = $this->app->make(RequestImageHideInterface::class);
        $this->assertInstanceOf(RequestImageHide::class, $requestImageHide);
    }

    /**
     * 正常系：正しくImageHideRequestが作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ImageNotFoundException
     * @throws ImageHideRequestAlreadyPendingException
     */
    public function testProcess(): void
    {
        $testData = $this->createTestData();
        $requesterName = 'Test Requester';
        $requesterEmail = 'requester@example.com';
        $reason = 'Privacy concern';

        $input = new RequestImageHideInput(
            $testData->imageIdentifier,
            $requesterName,
            $requesterEmail,
            $reason,
        );

        $createdImageHideRequest = new ImageHideRequest(
            new ImageHideRequestIdentifier(StrTestHelper::generateUuid()),
            $testData->imageIdentifier,
            $requesterName,
            $requesterEmail,
            $reason,
            ImageHideRequestStatus::PENDING,
            new DateTimeImmutable(),
            null,
            null,
            null,
        );

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $imageRepository->shouldReceive('findById')
            ->once()
            ->with($testData->imageIdentifier)
            ->andReturn($testData->image);

        $imageHideRequestRepository = Mockery::mock(ImageHideRequestRepositoryInterface::class);
        $imageHideRequestRepository->shouldReceive('existsPendingByImageId')
            ->once()
            ->with($testData->imageIdentifier)
            ->andReturn(false);
        $imageHideRequestRepository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $imageHideRequestFactory = Mockery::mock(ImageHideRequestFactoryInterface::class);
        $imageHideRequestFactory->shouldReceive('create')
            ->once()
            ->with($testData->imageIdentifier, $requesterName, $requesterEmail, $reason)
            ->andReturn($createdImageHideRequest);

        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(ImageHideRequestRepositoryInterface::class, $imageHideRequestRepository);
        $this->app->instance(ImageHideRequestFactoryInterface::class, $imageHideRequestFactory);

        $requestImageHide = $this->app->make(RequestImageHideInterface::class);
        $result = $requestImageHide->process($input);

        $this->assertSame(ImageHideRequestStatus::PENDING, $result->status());
        $this->assertSame((string) $testData->imageIdentifier, (string) $result->imageIdentifier());
        $this->assertSame($requesterName, $result->requesterName());
        $this->assertSame($requesterEmail, $result->requesterEmail());
        $this->assertSame($reason, $result->reason());
    }

    /**
     * 異常系：Imageが見つからない場合、ImageNotFoundExceptionがスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ImageHideRequestAlreadyPendingException
     */
    public function testProcessImageNotFound(): void
    {
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $input = new RequestImageHideInput(
            $imageIdentifier,
            'Test Requester',
            'requester@example.com',
            'Privacy concern',
        );

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $imageRepository->shouldReceive('findById')
            ->once()
            ->with($imageIdentifier)
            ->andReturn(null);

        $imageHideRequestRepository = Mockery::mock(ImageHideRequestRepositoryInterface::class);
        $imageHideRequestRepository->shouldNotReceive('existsPendingByImageId');
        $imageHideRequestRepository->shouldNotReceive('save');

        $imageHideRequestFactory = Mockery::mock(ImageHideRequestFactoryInterface::class);
        $imageHideRequestFactory->shouldNotReceive('create');

        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(ImageHideRequestRepositoryInterface::class, $imageHideRequestRepository);
        $this->app->instance(ImageHideRequestFactoryInterface::class, $imageHideRequestFactory);

        $this->expectException(ImageNotFoundException::class);
        $requestImageHide = $this->app->make(RequestImageHideInterface::class);
        $requestImageHide->process($input);
    }

    /**
     * 異常系：既にPendingのリクエストがある場合、ImageHideRequestAlreadyPendingExceptionがスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ImageNotFoundException
     */
    public function testProcessAlreadyPending(): void
    {
        $testData = $this->createTestData();
        $input = new RequestImageHideInput(
            $testData->imageIdentifier,
            'Test Requester',
            'requester@example.com',
            'Privacy concern',
        );

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $imageRepository->shouldReceive('findById')
            ->once()
            ->with($testData->imageIdentifier)
            ->andReturn($testData->image);

        $imageHideRequestRepository = Mockery::mock(ImageHideRequestRepositoryInterface::class);
        $imageHideRequestRepository->shouldReceive('existsPendingByImageId')
            ->once()
            ->with($testData->imageIdentifier)
            ->andReturn(true);
        $imageHideRequestRepository->shouldNotReceive('save');

        $imageHideRequestFactory = Mockery::mock(ImageHideRequestFactoryInterface::class);
        $imageHideRequestFactory->shouldNotReceive('create');

        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(ImageHideRequestRepositoryInterface::class, $imageHideRequestRepository);
        $this->app->instance(ImageHideRequestFactoryInterface::class, $imageHideRequestFactory);

        $this->expectException(ImageHideRequestAlreadyPendingException::class);
        $requestImageHide = $this->app->make(RequestImageHideInterface::class);
        $requestImageHide->process($input);
    }

    /**
     * @return RequestImageHideTestData
     */
    private function createTestData(): RequestImageHideTestData
    {
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());

        $image = new Image(
            $imageIdentifier,
            ResourceType::TALENT,
            new ResourceIdentifier(StrTestHelper::generateUuid()),
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

        return new RequestImageHideTestData(
            $imageIdentifier,
            $image,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class RequestImageHideTestData
{
    public function __construct(
        public ImageIdentifier $imageIdentifier,
        public Image $image,
    ) {
    }
}
