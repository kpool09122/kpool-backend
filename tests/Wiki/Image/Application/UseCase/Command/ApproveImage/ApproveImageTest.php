<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Command\ApproveImage;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
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
use Source\Wiki\Image\Domain\Service\ImageAuthorizationResourceBuilderInterface;
use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Image\Domain\ValueObject\ImageSnapshotIdentifier;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
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
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);

        $this->app->instance(DraftImageRepositoryInterface::class, $draftImageRepository);
        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(ImageFactoryInterface::class, $imageFactory);
        $this->app->instance(ImageSnapshotFactoryInterface::class, $imageSnapshotFactory);
        $this->app->instance(ImageSnapshotRepositoryInterface::class, $imageSnapshotRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

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
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessNewImage(): void
    {
        $testData = $this->createTestDataForNewImage();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);
        $resource = new Resource(type: ResourceType::IMAGE);

        $input = new ApproveImageInput($testData->draftImageIdentifier, $principalIdentifier);

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
                Mockery::type(PrincipalIdentifier::class),
                Mockery::type(PrincipalIdentifier::class),
                Mockery::type(\DateTimeImmutable::class),
            )
            ->andReturn($testData->image);

        $imageSnapshotFactory = Mockery::mock(ImageSnapshotFactoryInterface::class);
        $imageSnapshotRepository = Mockery::mock(ImageSnapshotRepositoryInterface::class);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->with($principalIdentifier)
            ->andReturn($principal);

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturn(true);

        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);
        $imageAuthorizationResourceBuilder->shouldReceive('buildFromDraftImage')
            ->once()
            ->with($testData->draftImage)
            ->andReturn($resource);

        $this->app->instance(DraftImageRepositoryInterface::class, $draftImageRepository);
        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(ImageFactoryInterface::class, $imageFactory);
        $this->app->instance(ImageSnapshotFactoryInterface::class, $imageSnapshotFactory);
        $this->app->instance(ImageSnapshotRepositoryInterface::class, $imageSnapshotRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

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
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessDraftImageNotFound(): void
    {
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $input = new ApproveImageInput($imageIdentifier, $principalIdentifier);

        $draftImageRepository = Mockery::mock(DraftImageRepositoryInterface::class);
        $draftImageRepository->shouldReceive('findById')
            ->once()
            ->with($imageIdentifier)
            ->andReturn(null);

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $imageFactory = Mockery::mock(ImageFactoryInterface::class);
        $imageSnapshotFactory = Mockery::mock(ImageSnapshotFactoryInterface::class);
        $imageSnapshotRepository = Mockery::mock(ImageSnapshotRepositoryInterface::class);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);

        $this->app->instance(DraftImageRepositoryInterface::class, $draftImageRepository);
        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(ImageFactoryInterface::class, $imageFactory);
        $this->app->instance(ImageSnapshotFactoryInterface::class, $imageSnapshotFactory);
        $this->app->instance(ImageSnapshotRepositoryInterface::class, $imageSnapshotRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

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
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessInvalidStatus(): void
    {
        $testData = $this->createTestDataWithInvalidStatus();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);
        $resource = new Resource(type: ResourceType::IMAGE);
        $input = new ApproveImageInput($testData->draftImageIdentifier, $principalIdentifier);

        $draftImageRepository = Mockery::mock(DraftImageRepositoryInterface::class);
        $draftImageRepository->shouldReceive('findById')
            ->once()
            ->with($testData->draftImageIdentifier)
            ->andReturn($testData->draftImage);

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $imageFactory = Mockery::mock(ImageFactoryInterface::class);
        $imageSnapshotFactory = Mockery::mock(ImageSnapshotFactoryInterface::class);
        $imageSnapshotRepository = Mockery::mock(ImageSnapshotRepositoryInterface::class);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->with($principalIdentifier)
            ->andReturn($principal);

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturn(true);

        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);
        $imageAuthorizationResourceBuilder->shouldReceive('buildFromDraftImage')
            ->once()
            ->with($testData->draftImage)
            ->andReturn($resource);

        $this->app->instance(DraftImageRepositoryInterface::class, $draftImageRepository);
        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(ImageFactoryInterface::class, $imageFactory);
        $this->app->instance(ImageSnapshotFactoryInterface::class, $imageSnapshotFactory);
        $this->app->instance(ImageSnapshotRepositoryInterface::class, $imageSnapshotRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

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
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessUpdateExistingImage(): void
    {
        $testData = $this->createTestDataForExistingImage();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);
        $resource = new Resource(type: ResourceType::IMAGE);

        $input = new ApproveImageInput($testData->draftImageIdentifier, $principalIdentifier);

        $snapshot = new ImageSnapshot(
            new ImageSnapshotIdentifier(StrTestHelper::generateUuid()),
            $testData->existingImage->imageIdentifier(),
            $testData->existingImage->resourceIdentifier(),
            $testData->existingImage->imagePath(),
            $testData->existingImage->imageUsage(),
            $testData->existingImage->displayOrder(),
            $testData->existingImage->sourceUrl(),
            $testData->existingImage->sourceName(),
            $testData->existingImage->altText(),
            $testData->existingImage->uploaderIdentifier(),
            $testData->existingImage->uploadedAt(),
            $testData->existingImage->approverIdentifier(),
            $testData->existingImage->approvedAt(),
            $testData->existingImage->updaterIdentifier(),
            $testData->existingImage->updatedAt(),
        );

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

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->with($principalIdentifier)
            ->andReturn($principal);

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturn(true);

        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);
        $imageAuthorizationResourceBuilder->shouldReceive('buildFromDraftImage')
            ->once()
            ->with($testData->draftImage)
            ->andReturn($resource);

        $this->app->instance(DraftImageRepositoryInterface::class, $draftImageRepository);
        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(ImageFactoryInterface::class, $imageFactory);
        $this->app->instance(ImageSnapshotFactoryInterface::class, $imageSnapshotFactory);
        $this->app->instance(ImageSnapshotRepositoryInterface::class, $imageSnapshotRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

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
     * 異常系：権限がない場合、DisallowedExceptionがスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ImageNotFoundException
     * @throws InvalidStatusException
     * @throws PrincipalNotFoundException
     */
    public function testProcessDisallowed(): void
    {
        $testData = $this->createTestDataForNewImage();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);
        $resource = new Resource(type: ResourceType::IMAGE);

        $input = new ApproveImageInput($testData->draftImageIdentifier, $principalIdentifier);

        $draftImageRepository = Mockery::mock(DraftImageRepositoryInterface::class);
        $draftImageRepository->shouldReceive('findById')
            ->once()
            ->with($testData->draftImageIdentifier)
            ->andReturn($testData->draftImage);

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $imageFactory = Mockery::mock(ImageFactoryInterface::class);
        $imageSnapshotFactory = Mockery::mock(ImageSnapshotFactoryInterface::class);
        $imageSnapshotRepository = Mockery::mock(ImageSnapshotRepositoryInterface::class);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->with($principalIdentifier)
            ->andReturn($principal);

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturn(false);

        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);
        $imageAuthorizationResourceBuilder->shouldReceive('buildFromDraftImage')
            ->once()
            ->with($testData->draftImage)
            ->andReturn($resource);

        $this->app->instance(DraftImageRepositoryInterface::class, $draftImageRepository);
        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(ImageFactoryInterface::class, $imageFactory);
        $this->app->instance(ImageSnapshotFactoryInterface::class, $imageSnapshotFactory);
        $this->app->instance(ImageSnapshotRepositoryInterface::class, $imageSnapshotRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

        $this->expectException(DisallowedException::class);
        $approveImage = $this->app->make(ApproveImageInterface::class);
        $approveImage->process($input);
    }

    /**
     * 異常系：Principalが見つからない場合、PrincipalNotFoundExceptionがスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ImageNotFoundException
     * @throws InvalidStatusException
     * @throws DisallowedException
     */
    public function testProcessPrincipalNotFound(): void
    {
        $testData = $this->createTestDataForNewImage();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new ApproveImageInput($testData->draftImageIdentifier, $principalIdentifier);

        $draftImageRepository = Mockery::mock(DraftImageRepositoryInterface::class);
        $draftImageRepository->shouldReceive('findById')
            ->once()
            ->with($testData->draftImageIdentifier)
            ->andReturn($testData->draftImage);

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $imageFactory = Mockery::mock(ImageFactoryInterface::class);
        $imageSnapshotFactory = Mockery::mock(ImageSnapshotFactoryInterface::class);
        $imageSnapshotRepository = Mockery::mock(ImageSnapshotRepositoryInterface::class);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->with($principalIdentifier)
            ->andReturn(null);

        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);

        $this->app->instance(DraftImageRepositoryInterface::class, $draftImageRepository);
        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(ImageFactoryInterface::class, $imageFactory);
        $this->app->instance(ImageSnapshotFactoryInterface::class, $imageSnapshotFactory);
        $this->app->instance(ImageSnapshotRepositoryInterface::class, $imageSnapshotRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

        $this->expectException(PrincipalNotFoundException::class);
        $approveImage = $this->app->make(ApproveImageInterface::class);
        $approveImage->process($input);
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
        $uploaderIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $uploadedAt = new DateTimeImmutable();

        $draftImage = new DraftImage(
            $draftImageIdentifier,
            null, // publishedImageIdentifier - 新規作成
            $resourceType,
            $resourceIdentifier,
            $uploaderIdentifier,
            $imagePath,
            $imageUsage,
            $displayOrder,
            $sourceUrl,
            $sourceName,
            $altText,
            ApprovalStatus::UnderReview,
            $agreedToTermsAt,
            $uploadedAt,
        );

        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $approverIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $approvedAt = new DateTimeImmutable();
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
            $uploaderIdentifier,
            $uploadedAt,
            $approverIdentifier,
            $approvedAt,
            null,
            null,
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
        $uploaderIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $uploadedAt = new DateTimeImmutable();

        $draftImage = new DraftImage(
            $draftImageIdentifier,
            null,
            $resourceType,
            $resourceIdentifier,
            $uploaderIdentifier,
            $imagePath,
            $imageUsage,
            $displayOrder,
            $sourceUrl,
            $sourceName,
            $altText,
            ApprovalStatus::Pending, // UnderReviewではない
            $agreedToTermsAt,
            $uploadedAt,
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
        $uploaderIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $agreedToTermsAt = new DateTimeImmutable('2024-01-01 00:00:00');
        $uploadedAt = new DateTimeImmutable();

        // 既存Imageのデータ（更新前）
        $existingImagePath = new ImagePath('images/existing.png');
        $existingImageUsage = ImageUsage::PROFILE;
        $existingDisplayOrder = 1;
        $existingSourceUrl = 'https://example.com/existing';
        $existingSourceName = 'Existing Source';
        $existingAltText = 'Existing alt text';
        $existingApproverIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $existingApprovedAt = new DateTimeImmutable();

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
            $uploaderIdentifier,
            $uploadedAt,
            $existingApproverIdentifier,
            $existingApprovedAt,
            null,
            null,
        );

        $draftImage = new DraftImage(
            $draftImageIdentifier,
            $publishedImageIdentifier, // 既存Imageを参照
            $resourceType,
            $resourceIdentifier,
            $uploaderIdentifier,
            $newImagePath,
            $newImageUsage,
            $newDisplayOrder,
            $newSourceUrl,
            $newSourceName,
            $newAltText,
            ApprovalStatus::UnderReview,
            $agreedToTermsAt,
            $uploadedAt,
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
