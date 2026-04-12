<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Command\RequestImageHide;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Application\UseCase\Command\RequestImageHide\RequestImageHide;
use Source\Wiki\Image\Application\UseCase\Command\RequestImageHide\RequestImageHideInput;
use Source\Wiki\Image\Application\UseCase\Command\RequestImageHide\RequestImageHideInterface;
use Source\Wiki\Image\Application\UseCase\Command\RequestImageHide\RequestImageHideOutput;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\Exception\ImageHideRequestAlreadyPendingException;
use Source\Wiki\Image\Domain\Repository\ImageRepositoryInterface;
use Source\Wiki\Image\Domain\ValueObject\HideRequest;
use Source\Wiki\Image\Domain\ValueObject\ImageHideRequestStatus;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RequestImageHideTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);

        $requestImageHide = $this->app->make(RequestImageHideInterface::class);
        $this->assertInstanceOf(RequestImageHide::class, $requestImageHide);
    }

    /**
     * 正常系：正しくhideRequestが作成されること.
     *
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $image = $this->createTestImage();
        $imageIdentifier = $image->imageIdentifier();

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
            ->andReturn($image);
        $imageRepository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);

        $requestImageHide = $this->app->make(RequestImageHideInterface::class);
        $output = new RequestImageHideOutput();
        $requestImageHide->process($input, $output);

        $result = $output->toArray();
        $this->assertSame((string) $imageIdentifier, $result['imageIdentifier']);
        $this->assertSame('pending', $result['status']);
    }

    /**
     * 異常系：Imageが見つからない場合、ImageNotFoundExceptionがスローされること.
     *
     * @throws BindingResolutionException
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
        $imageRepository->shouldNotReceive('save');

        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);

        $this->expectException(ImageNotFoundException::class);
        $requestImageHide = $this->app->make(RequestImageHideInterface::class);
        $output = new RequestImageHideOutput();
        $requestImageHide->process($input, $output);
    }

    /**
     * 異常系：既にpendingのhideRequestがある場合、ImageHideRequestAlreadyPendingExceptionがスローされること.
     *
     * @throws BindingResolutionException
     */
    public function testProcessAlreadyPending(): void
    {
        $image = $this->createTestImageWithPendingHideRequest();
        $imageIdentifier = $image->imageIdentifier();

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
            ->andReturn($image);
        $imageRepository->shouldNotReceive('save');

        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);

        $this->expectException(ImageHideRequestAlreadyPendingException::class);
        $requestImageHide = $this->app->make(RequestImageHideInterface::class);
        $output = new RequestImageHideOutput();
        $requestImageHide->process($input, $output);
    }

    private function createTestImage(): Image
    {
        return new Image(
            new ImageIdentifier(StrTestHelper::generateUuid()),
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
    }

    private function createTestImageWithPendingHideRequest(): Image
    {
        $hideRequest = new HideRequest(
            'Existing Requester',
            'existing@example.com',
            'Existing reason',
            ImageHideRequestStatus::PENDING,
            new DateTimeImmutable(),
            null,
            null,
            null,
        );

        return new Image(
            new ImageIdentifier(StrTestHelper::generateUuid()),
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
            [$hideRequest],
        );
    }
}
