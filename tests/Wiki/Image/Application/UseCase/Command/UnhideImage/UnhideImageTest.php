<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Command\UnhideImage;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Application\UseCase\Command\UnhideImage\UnhideImage;
use Source\Wiki\Image\Application\UseCase\Command\UnhideImage\UnhideImageInput;
use Source\Wiki\Image\Application\UseCase\Command\UnhideImage\UnhideImageInterface;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\Repository\ImageRepositoryInterface;
use Source\Wiki\Image\Domain\Service\ImageAuthorizationResourceBuilderInterface;
use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class UnhideImageTest extends TestCase
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

        $unhideImage = $this->app->make(UnhideImageInterface::class);
        $this->assertInstanceOf(UnhideImage::class, $unhideImage);
    }

    /**
     * 正常系：正しくImageが非表示解除されること.
     *
     * @return void
     * @throws BindingResolutionException
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

        $input = new UnhideImageInput($testData->imageIdentifier, $principalIdentifier);

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

        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

        $unhideImage = $this->app->make(UnhideImageInterface::class);
        $result = $unhideImage->process($input);

        $this->assertFalse($result->isHidden());
        $this->assertNull($result->hiddenBy());
        $this->assertNull($result->hiddenAt());
    }

    /**
     * 異常系：Imageが見つからない場合、ImageNotFoundExceptionがスローされること.
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
        $input = new UnhideImageInput($imageIdentifier, $principalIdentifier);

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $imageRepository->shouldReceive('findById')
            ->once()
            ->with($imageIdentifier)
            ->andReturn(null);
        $imageRepository->shouldNotReceive('save');

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);
        $imageAuthorizationResourceBuilder->shouldNotReceive('buildFromImage');

        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

        $this->expectException(ImageNotFoundException::class);
        $unhideImage = $this->app->make(UnhideImageInterface::class);
        $unhideImage->process($input);
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
        $testData = $this->createTestData();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $input = new UnhideImageInput($testData->imageIdentifier, $principalIdentifier);

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

        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

        $this->expectException(PrincipalNotFoundException::class);
        $unhideImage = $this->app->make(UnhideImageInterface::class);
        $unhideImage->process($input);
    }

    /**
     * 異常系：権限がない場合、DisallowedExceptionがスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ImageNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function testProcessDisallowed(): void
    {
        $testData = $this->createTestData();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);
        $resource = new Resource(type: ResourceType::IMAGE);

        $input = new UnhideImageInput($testData->imageIdentifier, $principalIdentifier);

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

        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

        $this->expectException(DisallowedException::class);
        $unhideImage = $this->app->make(UnhideImageInterface::class);
        $unhideImage->process($input);
    }

    /**
     * @return UnhideImageTestData
     */
    private function createTestData(): UnhideImageTestData
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
        $uploaderIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $uploadedAt = new DateTimeImmutable();
        $hiddenBy = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $hiddenAt = new DateTimeImmutable();

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
            true,
            $hiddenBy,
            $hiddenAt,
            $uploaderIdentifier,
            $uploadedAt,
            null,
            null,
            null,
            null,
        );

        return new UnhideImageTestData(
            $imageIdentifier,
            $image,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class UnhideImageTestData
{
    public function __construct(
        public ImageIdentifier $imageIdentifier,
        public Image $image,
    ) {
    }
}
