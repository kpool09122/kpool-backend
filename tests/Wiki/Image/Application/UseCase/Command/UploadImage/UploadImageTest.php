<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Command\UploadImage;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Application\DTO\ImageUploadResult;
use Source\Shared\Application\Exception\InvalidBase64ImageException;
use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Application\UseCase\Command\UploadImage\UploadImage;
use Source\Wiki\Image\Application\UseCase\Command\UploadImage\UploadImageInput;
use Source\Wiki\Image\Application\UseCase\Command\UploadImage\UploadImageInterface;
use Source\Wiki\Image\Domain\Entity\DraftImage;
use Source\Wiki\Image\Domain\Factory\DraftImageFactoryInterface;
use Source\Wiki\Image\Domain\Repository\DraftImageRepositoryInterface;
use Source\Wiki\Image\Domain\Service\ImageAuthorizationResourceBuilderInterface;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class UploadImageTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $imageService = Mockery::mock(ImageServiceInterface::class);
        $draftImageFactory = Mockery::mock(DraftImageFactoryInterface::class);
        $draftImageRepository = Mockery::mock(DraftImageRepositoryInterface::class);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);

        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftImageFactoryInterface::class, $draftImageFactory);
        $this->app->instance(DraftImageRepositoryInterface::class, $draftImageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

        $uploadImage = $this->app->make(UploadImageInterface::class);
        $this->assertInstanceOf(UploadImage::class, $uploadImage);
    }

    /**
     * 正常系：正しくDraftImage Entityが作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidBase64ImageException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcess(): void
    {
        $testData = $this->createTestData();
        $principal = new Principal($testData->principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);
        $resource = new Resource(type: ResourceType::IMAGE);

        $input = new UploadImageInput(
            $testData->principalIdentifier,
            $testData->publishedImageIdentifier,
            $testData->resourceType,
            $testData->draftResourceIdentifier,
            $testData->base64EncodedImage,
            $testData->imageUsage,
            $testData->displayOrder,
            $testData->sourceUrl,
            $testData->sourceName,
            $testData->altText,
            $testData->agreedToTermsAt,
        );

        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldReceive('upload')
            ->once()
            ->with($testData->base64EncodedImage)
            ->andReturn($testData->uploadResult);

        $draftImageFactory = Mockery::mock(DraftImageFactoryInterface::class);
        $draftImageFactory->shouldReceive('create')
            ->once()
            ->with(
                $testData->publishedImageIdentifier,
                $testData->resourceType,
                $testData->draftResourceIdentifier,
                $testData->principalIdentifier,
                $testData->imagePath,
                $testData->imageUsage,
                $testData->displayOrder,
                $testData->sourceUrl,
                $testData->sourceName,
                $testData->altText,
                $testData->agreedToTermsAt,
            )
            ->andReturn($testData->draftImage);

        $draftImageRepository = Mockery::mock(DraftImageRepositoryInterface::class);
        $draftImageRepository->shouldReceive('save')
            ->once()
            ->with($testData->draftImage)
            ->andReturn(null);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->with($testData->principalIdentifier)
            ->andReturn($principal);

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturn(true);

        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);
        $imageAuthorizationResourceBuilder->shouldReceive('buildFromDraftResource')
            ->once()
            ->with($testData->resourceType, $testData->draftResourceIdentifier)
            ->andReturn($resource);

        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftImageFactoryInterface::class, $draftImageFactory);
        $this->app->instance(DraftImageRepositoryInterface::class, $draftImageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

        $uploadImage = $this->app->make(UploadImageInterface::class);
        $result = $uploadImage->process($input);

        $this->assertTrue(UuidValidator::isValid((string)$result->imageIdentifier()));
        $this->assertSame((string)$testData->publishedImageIdentifier, (string)$result->publishedImageIdentifier());
        $this->assertSame($testData->resourceType, $result->resourceType());
        $this->assertSame((string)$testData->draftResourceIdentifier, (string)$result->draftResourceIdentifier());
        $this->assertSame((string)$testData->principalIdentifier, (string)$result->uploaderIdentifier());
        $this->assertSame((string)$testData->imagePath, (string)$result->imagePath());
        $this->assertSame($testData->imageUsage, $result->imageUsage());
        $this->assertSame($testData->displayOrder, $result->displayOrder());
        $this->assertSame($testData->sourceUrl, $result->sourceUrl());
        $this->assertSame($testData->sourceName, $result->sourceName());
        $this->assertSame($testData->altText, $result->altText());
        $this->assertSame(ApprovalStatus::UnderReview, $result->status());
    }

    /**
     * 異常系：権限がない場合、DisallowedExceptionがスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidBase64ImageException
     * @throws PrincipalNotFoundException
     */
    public function testProcessDisallowed(): void
    {
        $testData = $this->createTestData();
        $principal = new Principal($testData->principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);
        $resource = new Resource(type: ResourceType::IMAGE);

        $input = new UploadImageInput(
            $testData->principalIdentifier,
            $testData->publishedImageIdentifier,
            $testData->resourceType,
            $testData->draftResourceIdentifier,
            $testData->base64EncodedImage,
            $testData->imageUsage,
            $testData->displayOrder,
            $testData->sourceUrl,
            $testData->sourceName,
            $testData->altText,
            $testData->agreedToTermsAt,
        );

        $imageService = Mockery::mock(ImageServiceInterface::class);
        $draftImageFactory = Mockery::mock(DraftImageFactoryInterface::class);
        $draftImageRepository = Mockery::mock(DraftImageRepositoryInterface::class);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->with($testData->principalIdentifier)
            ->andReturn($principal);

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturn(false);

        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);
        $imageAuthorizationResourceBuilder->shouldReceive('buildFromDraftResource')
            ->once()
            ->with($testData->resourceType, $testData->draftResourceIdentifier)
            ->andReturn($resource);

        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftImageFactoryInterface::class, $draftImageFactory);
        $this->app->instance(DraftImageRepositoryInterface::class, $draftImageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

        $this->expectException(DisallowedException::class);
        $uploadImage = $this->app->make(UploadImageInterface::class);
        $uploadImage->process($input);
    }

    /**
     * 異常系：Principalが見つからない場合、PrincipalNotFoundExceptionがスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidBase64ImageException
     * @throws DisallowedException
     */
    public function testProcessPrincipalNotFound(): void
    {
        $testData = $this->createTestData();

        $input = new UploadImageInput(
            $testData->principalIdentifier,
            $testData->publishedImageIdentifier,
            $testData->resourceType,
            $testData->draftResourceIdentifier,
            $testData->base64EncodedImage,
            $testData->imageUsage,
            $testData->displayOrder,
            $testData->sourceUrl,
            $testData->sourceName,
            $testData->altText,
            $testData->agreedToTermsAt,
        );

        $imageService = Mockery::mock(ImageServiceInterface::class);
        $draftImageFactory = Mockery::mock(DraftImageFactoryInterface::class);
        $draftImageRepository = Mockery::mock(DraftImageRepositoryInterface::class);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->with($testData->principalIdentifier)
            ->andReturn(null);

        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);

        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftImageFactoryInterface::class, $draftImageFactory);
        $this->app->instance(DraftImageRepositoryInterface::class, $draftImageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

        $this->expectException(PrincipalNotFoundException::class);
        $uploadImage = $this->app->make(UploadImageInterface::class);
        $uploadImage->process($input);
    }

    /**
     * @return UploadImageTestData
     */
    private function createTestData(): UploadImageTestData
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $publishedImageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $resourceType = ResourceType::TALENT;
        $draftResourceIdentifier = new ResourceIdentifier(StrTestHelper::generateUuid());
        $base64EncodedImage = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=';
        $imageUsage = ImageUsage::PROFILE;
        $displayOrder = 1;
        $sourceUrl = 'https://example.com/source';
        $sourceName = 'Example Source';
        $altText = 'Profile image of talent';
        $agreedToTermsAt = new DateTimeImmutable('2024-01-01 00:00:00');

        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $imagePath = new ImagePath('images/test.webp');
        $uploadResult = new ImageUploadResult(
            new ImagePath('images/test_original.webp'),
            $imagePath,
        );
        $uploadedAt = new DateTimeImmutable();

        $draftImage = new DraftImage(
            $imageIdentifier,
            $publishedImageIdentifier,
            $resourceType,
            $draftResourceIdentifier,
            $principalIdentifier,
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

        return new UploadImageTestData(
            $principalIdentifier,
            $publishedImageIdentifier,
            $resourceType,
            $draftResourceIdentifier,
            $base64EncodedImage,
            $imageUsage,
            $displayOrder,
            $sourceUrl,
            $sourceName,
            $altText,
            $agreedToTermsAt,
            $imageIdentifier,
            $imagePath,
            $uploadResult,
            $uploadedAt,
            $draftImage,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class UploadImageTestData
{
    public function __construct(
        public PrincipalIdentifier $principalIdentifier,
        public ImageIdentifier $publishedImageIdentifier,
        public ResourceType $resourceType,
        public ResourceIdentifier $draftResourceIdentifier,
        public string $base64EncodedImage,
        public ImageUsage $imageUsage,
        public int $displayOrder,
        public string $sourceUrl,
        public string $sourceName,
        public string $altText,
        public DateTimeImmutable $agreedToTermsAt,
        public ImageIdentifier $imageIdentifier,
        public ImagePath $imagePath,
        public ImageUploadResult $uploadResult,
        public DateTimeImmutable $uploadedAt,
        public DraftImage $draftImage,
    ) {
    }
}
