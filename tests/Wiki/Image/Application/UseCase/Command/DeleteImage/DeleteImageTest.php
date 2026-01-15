<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Command\DeleteImage;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Application\UseCase\Command\DeleteImage\DeleteImage;
use Source\Wiki\Image\Application\UseCase\Command\DeleteImage\DeleteImageInput;
use Source\Wiki\Image\Application\UseCase\Command\DeleteImage\DeleteImageInterface;
use Source\Wiki\Image\Domain\Entity\DraftImage;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\Repository\DraftImageRepositoryInterface;
use Source\Wiki\Image\Domain\Repository\ImageRepositoryInterface;
use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DeleteImageTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $draftImageRepository = Mockery::mock(DraftImageRepositoryInterface::class);
        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);

        $this->app->instance(DraftImageRepositoryInterface::class, $draftImageRepository);
        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);

        $deleteImage = $this->app->make(DeleteImageInterface::class);
        $this->assertInstanceOf(DeleteImage::class, $deleteImage);
    }

    /**
     * 正常系：DraftImageが正しく削除されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ImageNotFoundException
     */
    public function testProcessDeleteDraftImage(): void
    {
        $testData = $this->createDraftImageTestData();

        $input = new DeleteImageInput($testData->draftImageIdentifier, true);

        $draftImageRepository = Mockery::mock(DraftImageRepositoryInterface::class);
        $draftImageRepository->shouldReceive('findById')
            ->once()
            ->with($testData->draftImageIdentifier)
            ->andReturn($testData->draftImage);
        $draftImageRepository->shouldReceive('delete')
            ->once()
            ->with($testData->draftImageIdentifier)
            ->andReturn(null);

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);

        $this->app->instance(DraftImageRepositoryInterface::class, $draftImageRepository);
        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);

        $deleteImage = $this->app->make(DeleteImageInterface::class);
        $deleteImage->process($input);
    }

    /**
     * 正常系：Imageが正しく削除されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ImageNotFoundException
     */
    public function testProcessDeleteImage(): void
    {
        $testData = $this->createImageTestData();

        $input = new DeleteImageInput($testData->imageIdentifier, false);

        $draftImageRepository = Mockery::mock(DraftImageRepositoryInterface::class);

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $imageRepository->shouldReceive('findById')
            ->once()
            ->with($testData->imageIdentifier)
            ->andReturn($testData->image);
        $imageRepository->shouldReceive('delete')
            ->once()
            ->with($testData->imageIdentifier)
            ->andReturn(null);

        $this->app->instance(DraftImageRepositoryInterface::class, $draftImageRepository);
        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);

        $deleteImage = $this->app->make(DeleteImageInterface::class);
        $deleteImage->process($input);
    }

    /**
     * 異常系：DraftImageが見つからない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessDraftImageNotFound(): void
    {
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $input = new DeleteImageInput($imageIdentifier, true);

        $draftImageRepository = Mockery::mock(DraftImageRepositoryInterface::class);
        $draftImageRepository->shouldReceive('findById')
            ->once()
            ->with($imageIdentifier)
            ->andReturn(null);

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);

        $this->app->instance(DraftImageRepositoryInterface::class, $draftImageRepository);
        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);

        $this->expectException(ImageNotFoundException::class);
        $deleteImage = $this->app->make(DeleteImageInterface::class);
        $deleteImage->process($input);
    }

    /**
     * 異常系：Imageが見つからない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessImageNotFound(): void
    {
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $input = new DeleteImageInput($imageIdentifier, false);

        $draftImageRepository = Mockery::mock(DraftImageRepositoryInterface::class);

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $imageRepository->shouldReceive('findById')
            ->once()
            ->with($imageIdentifier)
            ->andReturn(null);

        $this->app->instance(DraftImageRepositoryInterface::class, $draftImageRepository);
        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);

        $this->expectException(ImageNotFoundException::class);
        $deleteImage = $this->app->make(DeleteImageInterface::class);
        $deleteImage->process($input);
    }

    /**
     * @return DeleteImageDraftTestData
     */
    private function createDraftImageTestData(): DeleteImageDraftTestData
    {
        $draftImageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $resourceType = ResourceType::TALENT;
        $resourceIdentifier = new ResourceIdentifier(StrTestHelper::generateUuid());
        $imagePath = new ImagePath('images/test.png');
        $imageUsage = ImageUsage::PROFILE;
        $displayOrder = 1;
        $sourceUrl = 'https://example.com/source';
        $sourceName = 'Example Source';
        $altText = 'Profile image of talent';
        $agreedToTermsAt = new DateTimeImmutable('2024-01-01 00:00:00');
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $createdAt = new DateTimeImmutable();

        $draftImage = new DraftImage(
            $draftImageIdentifier,
            null,
            $resourceType,
            $resourceIdentifier,
            $principalIdentifier,
            $imagePath,
            $imageUsage,
            $displayOrder,
            $sourceUrl,
            $sourceName,
            $altText,
            ApprovalStatus::UnderReview,
            $agreedToTermsAt,
            $createdAt,
        );

        return new DeleteImageDraftTestData(
            $draftImageIdentifier,
            $draftImage,
        );
    }

    /**
     * @return DeleteImageTestData
     */
    private function createImageTestData(): DeleteImageTestData
    {
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $resourceType = ResourceType::TALENT;
        $resourceIdentifier = new ResourceIdentifier(StrTestHelper::generateUuid());
        $imagePath = new ImagePath('images/test.png');
        $imageUsage = ImageUsage::PROFILE;
        $displayOrder = 1;
        $sourceUrl = 'https://example.com/source';
        $sourceName = 'Example Source';
        $altText = 'Profile image of talent';
        $createdAt = new DateTimeImmutable();

        $image = new Image(
            $imageIdentifier,
            $resourceType,
            $resourceIdentifier,
            $imagePath,
            $imageUsage,
            $displayOrder,
            $sourceUrl,
            $sourceName,
            $altText,
            $createdAt,
            $createdAt,
        );

        return new DeleteImageTestData(
            $imageIdentifier,
            $image,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class DeleteImageDraftTestData
{
    public function __construct(
        public ImageIdentifier $draftImageIdentifier,
        public DraftImage $draftImage,
    ) {
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class DeleteImageTestData
{
    public function __construct(
        public ImageIdentifier $imageIdentifier,
        public Image $image,
    ) {
    }
}
