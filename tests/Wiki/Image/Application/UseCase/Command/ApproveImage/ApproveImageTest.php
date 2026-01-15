<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Command\ApproveImage;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Application\UseCase\Command\ApproveImage\ApproveImage;
use Source\Wiki\Image\Application\UseCase\Command\ApproveImage\ApproveImageInput;
use Source\Wiki\Image\Application\UseCase\Command\ApproveImage\ApproveImageInterface;
use Source\Wiki\Image\Domain\Entity\DraftImage;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\Entity\ImageSnapshot;
use Source\Wiki\Image\Domain\Factory\ImageFactoryInterface;
use Source\Wiki\Image\Domain\Factory\ImageSnapshotFactoryInterface;
use Source\Wiki\Image\Domain\Repository\DraftImageRepositoryInterface;
use Source\Wiki\Image\Domain\Repository\ImageRepositoryInterface;
use Source\Wiki\Image\Domain\Repository\ImageSnapshotRepositoryInterface;
use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ApproveImageTest extends TestCase
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
        $imageFactory = Mockery::mock(ImageFactoryInterface::class);
        $imageSnapshotFactory = Mockery::mock(ImageSnapshotFactoryInterface::class);
        $imageSnapshotRepository = Mockery::mock(ImageSnapshotRepositoryInterface::class);

        $this->app->instance(DraftImageRepositoryInterface::class, $draftImageRepository);
        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(ImageFactoryInterface::class, $imageFactory);
        $this->app->instance(ImageSnapshotFactoryInterface::class, $imageSnapshotFactory);
        $this->app->instance(ImageSnapshotRepositoryInterface::class, $imageSnapshotRepository);

        $approveImage = $this->app->make(ApproveImageInterface::class);
        $this->assertInstanceOf(ApproveImage::class, $approveImage);
    }

    /**
     * 正常系：新規Image作成時に正しくImage Entityが作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ImageNotFoundException
     * @throws InvalidStatusException
     */
    public function testProcessNewImage(): void
    {
        $testData = $this->createTestDataForNewImage();

        $input = new ApproveImageInput($testData->draftImageIdentifier);

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
        $imageRepository->shouldReceive('save')
            ->once()
            ->with($testData->image)
            ->andReturn(null);

        $imageFactory = Mockery::mock(ImageFactoryInterface::class);
        $imageFactory->shouldReceive('create')
            ->once()
            ->with(
                $testData->resourceType,
                $testData->resourceIdentifier,
                $testData->imagePath,
                $testData->imageUsage,
                $testData->displayOrder,
                $testData->sourceUrl,
                $testData->sourceName,
                $testData->altText,
            )
            ->andReturn($testData->image);

        $imageSnapshotFactory = Mockery::mock(ImageSnapshotFactoryInterface::class);
        $imageSnapshotRepository = Mockery::mock(ImageSnapshotRepositoryInterface::class);

        $this->app->instance(DraftImageRepositoryInterface::class, $draftImageRepository);
        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(ImageFactoryInterface::class, $imageFactory);
        $this->app->instance(ImageSnapshotFactoryInterface::class, $imageSnapshotFactory);
        $this->app->instance(ImageSnapshotRepositoryInterface::class, $imageSnapshotRepository);

        $approveImage = $this->app->make(ApproveImageInterface::class);
        $result = $approveImage->process($input);

        $this->assertTrue(UuidValidator::isValid((string)$result->imageIdentifier()));
        $this->assertSame($testData->resourceType, $result->resourceType());
        $this->assertSame((string)$testData->resourceIdentifier, (string)$result->resourceIdentifier());
        $this->assertSame((string)$testData->imagePath, (string)$result->imagePath());
        $this->assertSame($testData->imageUsage, $result->imageUsage());
        $this->assertSame($testData->displayOrder, $result->displayOrder());
        $this->assertSame($testData->sourceUrl, $result->sourceUrl());
        $this->assertSame($testData->sourceName, $result->sourceName());
        $this->assertSame($testData->altText, $result->altText());
    }

    /**
     * 異常系：DraftImageが見つからない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     */
    public function testProcessDraftImageNotFound(): void
    {
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $input = new ApproveImageInput($imageIdentifier);

        $draftImageRepository = Mockery::mock(DraftImageRepositoryInterface::class);
        $draftImageRepository->shouldReceive('findById')
            ->once()
            ->with($imageIdentifier)
            ->andReturn(null);

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $imageFactory = Mockery::mock(ImageFactoryInterface::class);
        $imageSnapshotFactory = Mockery::mock(ImageSnapshotFactoryInterface::class);
        $imageSnapshotRepository = Mockery::mock(ImageSnapshotRepositoryInterface::class);

        $this->app->instance(DraftImageRepositoryInterface::class, $draftImageRepository);
        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(ImageFactoryInterface::class, $imageFactory);
        $this->app->instance(ImageSnapshotFactoryInterface::class, $imageSnapshotFactory);
        $this->app->instance(ImageSnapshotRepositoryInterface::class, $imageSnapshotRepository);

        $this->expectException(ImageNotFoundException::class);
        $approveImage = $this->app->make(ApproveImageInterface::class);
        $approveImage->process($input);
    }

    /**
     * 異常系：ステータスがUnderReviewでない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ImageNotFoundException
     */
    public function testProcessInvalidStatus(): void
    {
        $testData = $this->createTestDataWithInvalidStatus();
        $input = new ApproveImageInput($testData->draftImageIdentifier);

        $draftImageRepository = Mockery::mock(DraftImageRepositoryInterface::class);
        $draftImageRepository->shouldReceive('findById')
            ->once()
            ->with($testData->draftImageIdentifier)
            ->andReturn($testData->draftImage);

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $imageFactory = Mockery::mock(ImageFactoryInterface::class);
        $imageSnapshotFactory = Mockery::mock(ImageSnapshotFactoryInterface::class);
        $imageSnapshotRepository = Mockery::mock(ImageSnapshotRepositoryInterface::class);

        $this->app->instance(DraftImageRepositoryInterface::class, $draftImageRepository);
        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(ImageFactoryInterface::class, $imageFactory);
        $this->app->instance(ImageSnapshotFactoryInterface::class, $imageSnapshotFactory);
        $this->app->instance(ImageSnapshotRepositoryInterface::class, $imageSnapshotRepository);

        $this->expectException(InvalidStatusException::class);
        $approveImage = $this->app->make(ApproveImageInterface::class);
        $approveImage->process($input);
    }

    /**
     * 正常系：既存Imageの更新時に正しくImageが更新されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ImageNotFoundException
     * @throws InvalidStatusException
     */
    public function testProcessUpdateExistingImage(): void
    {
        $testData = $this->createTestDataForExistingImage();

        $input = new ApproveImageInput($testData->draftImageIdentifier);

        $snapshot = Mockery::mock(ImageSnapshot::class);

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
        $imageRepository->shouldReceive('findById')
            ->once()
            ->with($testData->publishedImageIdentifier)
            ->andReturn($testData->existingImage);
        $imageRepository->shouldReceive('save')
            ->once()
            ->with($testData->existingImage)
            ->andReturn(null);

        $imageFactory = Mockery::mock(ImageFactoryInterface::class);

        $imageSnapshotFactory = Mockery::mock(ImageSnapshotFactoryInterface::class);
        $imageSnapshotFactory->shouldReceive('create')
            ->once()
            ->with($testData->existingImage, $testData->existingImage->resourceIdentifier())
            ->andReturn($snapshot);

        $imageSnapshotRepository = Mockery::mock(ImageSnapshotRepositoryInterface::class);
        $imageSnapshotRepository->shouldReceive('save')
            ->once()
            ->with($snapshot)
            ->andReturn(null);

        $this->app->instance(DraftImageRepositoryInterface::class, $draftImageRepository);
        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(ImageFactoryInterface::class, $imageFactory);
        $this->app->instance(ImageSnapshotFactoryInterface::class, $imageSnapshotFactory);
        $this->app->instance(ImageSnapshotRepositoryInterface::class, $imageSnapshotRepository);

        $approveImage = $this->app->make(ApproveImageInterface::class);
        $result = $approveImage->process($input);

        // 更新後の値が反映されていることを確認
        $this->assertSame((string)$testData->publishedImageIdentifier, (string)$result->imageIdentifier());
        $this->assertSame((string)$testData->newImagePath, (string)$result->imagePath());
        $this->assertSame($testData->newImageUsage, $result->imageUsage());
        $this->assertSame($testData->newDisplayOrder, $result->displayOrder());
        $this->assertSame($testData->newSourceUrl, $result->sourceUrl());
        $this->assertSame($testData->newSourceName, $result->sourceName());
        $this->assertSame($testData->newAltText, $result->altText());
    }

    /**
     * @return ApproveImageTestData
     */
    private function createTestDataForNewImage(): ApproveImageTestData
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
            null, // publishedImageIdentifier - 新規作成
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

        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
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

        return new ApproveImageTestData(
            $draftImageIdentifier,
            $resourceType,
            $resourceIdentifier,
            $imagePath,
            $imageUsage,
            $displayOrder,
            $sourceUrl,
            $sourceName,
            $altText,
            $draftImage,
            $image,
        );
    }

    /**
     * @return ApproveImageTestData
     */
    private function createTestDataWithInvalidStatus(): ApproveImageTestData
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
            ApprovalStatus::Pending, // UnderReviewではない
            $agreedToTermsAt,
            $createdAt,
        );

        return new ApproveImageTestData(
            $draftImageIdentifier,
            $resourceType,
            $resourceIdentifier,
            $imagePath,
            $imageUsage,
            $displayOrder,
            $sourceUrl,
            $sourceName,
            $altText,
            $draftImage,
            null,
        );
    }

    /**
     * @return ApproveImageUpdateTestData
     */
    private function createTestDataForExistingImage(): ApproveImageUpdateTestData
    {
        $draftImageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $publishedImageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $resourceType = ResourceType::TALENT;
        $resourceIdentifier = new ResourceIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $agreedToTermsAt = new DateTimeImmutable('2024-01-01 00:00:00');
        $createdAt = new DateTimeImmutable();

        // 既存Imageのデータ（更新前）
        $existingImagePath = new ImagePath('images/existing.png');
        $existingImageUsage = ImageUsage::PROFILE;
        $existingDisplayOrder = 1;
        $existingSourceUrl = 'https://example.com/existing';
        $existingSourceName = 'Existing Source';
        $existingAltText = 'Existing alt text';

        // DraftImageのデータ（更新後の値）
        $newImagePath = new ImagePath('images/updated.png');
        $newImageUsage = ImageUsage::ADDITIONAL;
        $newDisplayOrder = 2;
        $newSourceUrl = 'https://example.com/updated';
        $newSourceName = 'Updated Source';
        $newAltText = 'Updated alt text';

        $existingImage = new Image(
            $publishedImageIdentifier,
            $resourceType,
            $resourceIdentifier,
            $existingImagePath,
            $existingImageUsage,
            $existingDisplayOrder,
            $existingSourceUrl,
            $existingSourceName,
            $existingAltText,
            $createdAt,
            $createdAt,
        );

        $draftImage = new DraftImage(
            $draftImageIdentifier,
            $publishedImageIdentifier, // 既存Imageを参照
            $resourceType,
            $resourceIdentifier,
            $principalIdentifier,
            $newImagePath,
            $newImageUsage,
            $newDisplayOrder,
            $newSourceUrl,
            $newSourceName,
            $newAltText,
            ApprovalStatus::UnderReview,
            $agreedToTermsAt,
            $createdAt,
        );

        return new ApproveImageUpdateTestData(
            $draftImageIdentifier,
            $publishedImageIdentifier,
            $draftImage,
            $existingImage,
            $newImagePath,
            $newImageUsage,
            $newDisplayOrder,
            $newSourceUrl,
            $newSourceName,
            $newAltText,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class ApproveImageTestData
{
    public function __construct(
        public ImageIdentifier $draftImageIdentifier,
        public ResourceType $resourceType,
        public ResourceIdentifier $resourceIdentifier,
        public ImagePath $imagePath,
        public ImageUsage $imageUsage,
        public int $displayOrder,
        public string $sourceUrl,
        public string $sourceName,
        public string $altText,
        public DraftImage $draftImage,
        public ?Image $image,
    ) {
    }
}

/**
 * 既存Image更新テスト用のテストデータを保持するクラス
 */
readonly class ApproveImageUpdateTestData
{
    public function __construct(
        public ImageIdentifier $draftImageIdentifier,
        public ImageIdentifier $publishedImageIdentifier,
        public DraftImage $draftImage,
        public Image $existingImage,
        public ImagePath $newImagePath,
        public ImageUsage $newImageUsage,
        public int $newDisplayOrder,
        public string $newSourceUrl,
        public string $newSourceName,
        public string $newAltText,
    ) {
    }
}
