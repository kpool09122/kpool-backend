<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Command\DeleteImage;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Application\UseCase\Command\DeleteImage\DeleteImage;
use Source\Wiki\Image\Application\UseCase\Command\DeleteImage\DeleteImageInput;
use Source\Wiki\Image\Application\UseCase\Command\DeleteImage\DeleteImageInterface;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\Repository\ImageRepositoryInterface;
use Source\Wiki\Image\Domain\Service\ImageAuthorizationResourceBuilderInterface;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier as SharedPrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
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
        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);

        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

        $deleteImage = $this->app->make(DeleteImageInterface::class);
        $this->assertInstanceOf(DeleteImage::class, $deleteImage);
    }

    /**
     * 正常系：Imageが正しく削除されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ImageNotFoundException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessDeleteImage(): void
    {
        $testData = $this->createImageTestData();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);
        $resource = new Resource(type: ResourceType::IMAGE);

        $input = new DeleteImageInput($testData->imageIdentifier, $principalIdentifier);

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $imageRepository->shouldReceive('findById')
            ->once()
            ->with($testData->imageIdentifier)
            ->andReturn($testData->image);
        $imageRepository->shouldReceive('delete')
            ->once()
            ->with($testData->imageIdentifier)
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

        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

        $deleteImage = $this->app->make(DeleteImageInterface::class);
        $deleteImage->process($input);
    }

    /**
     * 異常系：Imageが見つからない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessImageNotFound(): void
    {
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);
        $input = new DeleteImageInput($imageIdentifier, $principalIdentifier);

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $imageRepository->shouldReceive('findById')
            ->once()
            ->with($imageIdentifier)
            ->andReturn(null);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->with($principalIdentifier)
            ->andReturn($principal);

        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);

        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

        $this->expectException(ImageNotFoundException::class);
        $deleteImage = $this->app->make(DeleteImageInterface::class);
        $deleteImage->process($input);
    }

    /**
     * 異常系：Image削除時に権限がない場合、DisallowedExceptionがスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ImageNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function testProcessDeleteImageDisallowed(): void
    {
        $testData = $this->createImageTestData();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);
        $resource = new Resource(type: ResourceType::IMAGE);

        $input = new DeleteImageInput($testData->imageIdentifier, $principalIdentifier);

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $imageRepository->shouldReceive('findById')
            ->once()
            ->with($testData->imageIdentifier)
            ->andReturn($testData->image);

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

        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

        $this->expectException(DisallowedException::class);
        $deleteImage = $this->app->make(DeleteImageInterface::class);
        $deleteImage->process($input);
    }

    /**
     * 異常系：Principalが見つからない場合、PrincipalNotFoundExceptionがスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ImageNotFoundException
     * @throws DisallowedException
     */
    public function testProcessPrincipalNotFound(): void
    {
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $input = new DeleteImageInput($imageIdentifier, $principalIdentifier);

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->with($principalIdentifier)
            ->andReturn(null);

        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);

        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

        $this->expectException(PrincipalNotFoundException::class);
        $deleteImage = $this->app->make(DeleteImageInterface::class);
        $deleteImage->process($input);
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
        $uploaderIdentifier = new SharedPrincipalIdentifier(StrTestHelper::generateUuid());
        $uploadedAt = new DateTimeImmutable();
        $approverIdentifier = new SharedPrincipalIdentifier(StrTestHelper::generateUuid());
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
            false,
            null,
            null,
            $uploaderIdentifier,
            $uploadedAt,
            $approverIdentifier,
            $approvedAt,
            null,
            null,
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
readonly class DeleteImageTestData
{
    public function __construct(
        public ImageIdentifier $imageIdentifier,
        public Image $image,
    ) {
    }
}
