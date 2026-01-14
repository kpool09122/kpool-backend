<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\Repository\ImageRepositoryInterface;
use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\CreateImage;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ImageRepositoryTest extends TestCase
{
    /**
     * 正常系：指定したIDの画像が取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $imageId = StrTestHelper::generateUuid();
        $resourceId = StrTestHelper::generateUuid();

        CreateImage::create($imageId, [
            'resource_type' => ResourceType::TALENT->value,
            'resource_identifier' => $resourceId,
            'image_path' => '/images/talents/profile.jpg',
            'image_usage' => ImageUsage::PROFILE->value,
            'display_order' => 1,
        ]);

        $repository = $this->app->make(ImageRepositoryInterface::class);
        $image = $repository->findById(new ImageIdentifier($imageId));

        $this->assertInstanceOf(Image::class, $image);
        $this->assertSame($imageId, (string) $image->imageIdentifier());
        $this->assertSame(ResourceType::TALENT, $image->resourceType());
        $this->assertSame($resourceId, (string) $image->resourceIdentifier());
        $this->assertSame('/images/talents/profile.jpg', (string) $image->imagePath());
        $this->assertSame(ImageUsage::PROFILE, $image->imageUsage());
        $this->assertSame(1, $image->displayOrder());
        $this->assertInstanceOf(DateTimeImmutable::class, $image->createdAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $image->updatedAt());
    }

    /**
     * 正常系：指定したIDの画像が存在しない場合、nullが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNotExist(): void
    {
        $repository = $this->app->make(ImageRepositoryInterface::class);
        $image = $repository->findById(new ImageIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($image);
    }

    /**
     * 正常系：指定したリソースタイプとリソースIDに紐づく画像が取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByResource(): void
    {
        $resourceId = StrTestHelper::generateUuid();

        $imageId1 = StrTestHelper::generateUuid();
        $imageId2 = StrTestHelper::generateUuid();
        $otherImageId = StrTestHelper::generateUuid();

        CreateImage::create($imageId1, [
            'resource_type' => ResourceType::TALENT->value,
            'resource_identifier' => $resourceId,
            'image_path' => '/images/talents/profile.jpg',
            'image_usage' => ImageUsage::PROFILE->value,
            'display_order' => 1,
        ]);

        CreateImage::create($imageId2, [
            'resource_type' => ResourceType::TALENT->value,
            'resource_identifier' => $resourceId,
            'image_path' => '/images/talents/additional.jpg',
            'image_usage' => ImageUsage::ADDITIONAL->value,
            'display_order' => 2,
        ]);

        CreateImage::create($otherImageId, [
            'resource_type' => ResourceType::GROUP->value,
            'resource_identifier' => StrTestHelper::generateUuid(),
            'image_path' => '/images/groups/cover.jpg',
            'image_usage' => ImageUsage::COVER->value,
            'display_order' => 1,
        ]);

        $repository = $this->app->make(ImageRepositoryInterface::class);
        $images = $repository->findByResource(
            ResourceType::TALENT,
            new ResourceIdentifier($resourceId),
        );

        $this->assertCount(2, $images);
        $imageIds = array_map(
            static fn (Image $image): string => (string) $image->imageIdentifier(),
            $images,
        );
        $this->assertContains($imageId1, $imageIds);
        $this->assertContains($imageId2, $imageIds);
        $this->assertNotContains($otherImageId, $imageIds);

        // display_orderでソートされていることを確認
        $this->assertSame($imageId1, (string) $images[0]->imageIdentifier());
        $this->assertSame($imageId2, (string) $images[1]->imageIdentifier());
    }

    /**
     * 正常系：指定したリソースに紐づく画像が存在しない場合、空配列が返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByResourceWhenNotExist(): void
    {
        $repository = $this->app->make(ImageRepositoryInterface::class);
        $images = $repository->findByResource(
            ResourceType::TALENT,
            new ResourceIdentifier(StrTestHelper::generateUuid()),
        );

        $this->assertIsArray($images);
        $this->assertEmpty($images);
    }

    /**
     * 正常系：正しく画像を保存できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSave(): void
    {
        $image = new Image(
            new ImageIdentifier(StrTestHelper::generateUuid()),
            ResourceType::TALENT,
            new ResourceIdentifier(StrTestHelper::generateUuid()),
            new ImagePath('/images/talents/new-profile.jpg'),
            ImageUsage::PROFILE,
            1,
            new DateTimeImmutable(),
            new DateTimeImmutable(),
        );

        $repository = $this->app->make(ImageRepositoryInterface::class);
        $repository->save($image);

        $this->assertDatabaseHas('wiki_images', [
            'id' => (string) $image->imageIdentifier(),
            'resource_type' => $image->resourceType()->value,
            'resource_identifier' => (string) $image->resourceIdentifier(),
            'image_path' => (string) $image->imagePath(),
            'image_usage' => $image->imageUsage()->value,
            'display_order' => $image->displayOrder(),
        ]);
    }

    /**
     * 正常系：既存の画像を更新できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveUpdate(): void
    {
        $imageId = StrTestHelper::generateUuid();
        $resourceId = StrTestHelper::generateUuid();

        CreateImage::create($imageId, [
            'resource_type' => ResourceType::TALENT->value,
            'resource_identifier' => $resourceId,
            'image_path' => '/images/talents/old.jpg',
            'image_usage' => ImageUsage::PROFILE->value,
            'display_order' => 1,
        ]);

        $image = new Image(
            new ImageIdentifier($imageId),
            ResourceType::TALENT,
            new ResourceIdentifier($resourceId),
            new ImagePath('/images/talents/updated.jpg'),
            ImageUsage::COVER,
            2,
            new DateTimeImmutable(),
            new DateTimeImmutable(),
        );

        $repository = $this->app->make(ImageRepositoryInterface::class);
        $repository->save($image);

        $this->assertDatabaseHas('wiki_images', [
            'id' => $imageId,
            'image_path' => '/images/talents/updated.jpg',
            'image_usage' => ImageUsage::COVER->value,
            'display_order' => 2,
        ]);

        $this->assertDatabaseCount('wiki_images', 1);
    }

    /**
     * 正常系：正しく画像を削除できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testDelete(): void
    {
        $imageId = StrTestHelper::generateUuid();

        CreateImage::create($imageId, [
            'resource_type' => ResourceType::TALENT->value,
            'image_path' => '/images/talents/to-delete.jpg',
            'image_usage' => ImageUsage::ADDITIONAL->value,
        ]);

        $this->assertDatabaseHas('wiki_images', ['id' => $imageId]);

        $repository = $this->app->make(ImageRepositoryInterface::class);
        $repository->delete(new ImageIdentifier($imageId));

        $this->assertDatabaseMissing('wiki_images', ['id' => $imageId]);
    }
}
