<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\Repository\ImageRepositoryInterface;
use Source\Wiki\Image\Domain\ValueObject\HideRequest;
use Source\Wiki\Image\Domain\ValueObject\ImageHideRequestStatus;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\CreateImage;
use Tests\Helper\CreateImageHideRequest;
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
        $wikiId = StrTestHelper::generateUuid();

        CreateImage::create($imageId, [
            'resource_type' => ResourceType::TALENT->value,
            'wiki_id' => $wikiId,
            'image_path' => '/images/talents/profile.jpg',
            'image_usage' => ImageUsage::PROFILE->value,
            'display_order' => 1,
        ]);

        $repository = $this->app->make(ImageRepositoryInterface::class);
        $image = $repository->findById(new ImageIdentifier($imageId));

        $this->assertInstanceOf(Image::class, $image);
        $this->assertSame($imageId, (string) $image->imageIdentifier());
        $this->assertSame(ResourceType::TALENT, $image->resourceType());
        $this->assertSame($wikiId, (string) $image->wikiIdentifier());
        $this->assertSame('/images/talents/profile.jpg', (string) $image->imagePath());
        $this->assertSame(ImageUsage::PROFILE, $image->imageUsage());
        $this->assertSame(1, $image->displayOrder());
        $this->assertInstanceOf(DateTimeImmutable::class, $image->uploadedAt());
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
        $wikiId = StrTestHelper::generateUuid();

        $imageId1 = StrTestHelper::generateUuid();
        $imageId2 = StrTestHelper::generateUuid();
        $otherImageId = StrTestHelper::generateUuid();

        CreateImage::create($imageId1, [
            'resource_type' => ResourceType::TALENT->value,
            'wiki_id' => $wikiId,
            'image_path' => '/images/talents/profile.jpg',
            'image_usage' => ImageUsage::PROFILE->value,
            'display_order' => 1,
        ]);

        CreateImage::create($imageId2, [
            'resource_type' => ResourceType::TALENT->value,
            'wiki_id' => $wikiId,
            'image_path' => '/images/talents/additional.jpg',
            'image_usage' => ImageUsage::ADDITIONAL->value,
            'display_order' => 2,
        ]);

        CreateImage::create($otherImageId, [
            'resource_type' => ResourceType::GROUP->value,
            'wiki_id' => StrTestHelper::generateUuid(),
            'image_path' => '/images/groups/cover.jpg',
            'image_usage' => ImageUsage::COVER->value,
            'display_order' => 1,
        ]);

        $repository = $this->app->make(ImageRepositoryInterface::class);
        $images = $repository->findByResource(
            ResourceType::TALENT,
            new WikiIdentifier($wikiId),
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
            new WikiIdentifier(StrTestHelper::generateUuid()),
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
        $uploaderIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $approverIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $now = new DateTimeImmutable();

        $image = new Image(
            new ImageIdentifier(StrTestHelper::generateUuid()),
            ResourceType::TALENT,
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new ImagePath('/images/talents/new-profile.jpg'),
            ImageUsage::PROFILE,
            1,
            'https://example.com/source',
            'Example Source',
            'Profile image of talent',
            false,
            null,
            null,
            $uploaderIdentifier,
            $now,
            $approverIdentifier,
            $now,
            null,
            null,
        );

        $repository = $this->app->make(ImageRepositoryInterface::class);
        $repository->save($image);

        $this->assertDatabaseHas('wiki_images', [
            'id' => (string) $image->imageIdentifier(),
            'resource_type' => $image->resourceType()->value,
            'wiki_id' => (string) $image->wikiIdentifier(),
            'image_path' => (string) $image->imagePath(),
            'image_usage' => $image->imageUsage()->value,
            'display_order' => $image->displayOrder(),
            'source_url' => $image->sourceUrl(),
            'source_name' => $image->sourceName(),
            'alt_text' => $image->altText(),
            'uploader_id' => (string) $uploaderIdentifier,
            'approver_id' => (string) $approverIdentifier,
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
        $wikiId = StrTestHelper::generateUuid();

        CreateImage::create($imageId, [
            'resource_type' => ResourceType::TALENT->value,
            'wiki_id' => $wikiId,
            'image_path' => '/images/talents/old.jpg',
            'image_usage' => ImageUsage::PROFILE->value,
            'display_order' => 1,
        ]);

        $uploaderIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $approverIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $now = new DateTimeImmutable();

        $image = new Image(
            new ImageIdentifier($imageId),
            ResourceType::TALENT,
            new WikiIdentifier($wikiId),
            new ImagePath('/images/talents/updated.jpg'),
            ImageUsage::COVER,
            2,
            'https://example.com/updated-source',
            'Updated Source',
            'Updated alt text',
            false,
            null,
            null,
            $uploaderIdentifier,
            $now,
            $approverIdentifier,
            $now,
            null,
            null,
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
     * 正常系：findByIdでhideRequest履歴が読み込まれ、pendingHideRequestを取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithPendingHideRequest(): void
    {
        $imageId = StrTestHelper::generateUuid();
        $requestId = StrTestHelper::generateUuid();

        CreateImage::create($imageId, [
            'resource_type' => ResourceType::TALENT->value,
            'image_usage' => ImageUsage::PROFILE->value,
        ]);

        CreateImageHideRequest::create($requestId, [
            'image_id' => $imageId,
            'status' => ImageHideRequestStatus::PENDING->value,
        ]);

        $repository = $this->app->make(ImageRepositoryInterface::class);
        $image = $repository->findById(new ImageIdentifier($imageId));

        $this->assertInstanceOf(Image::class, $image);
        $this->assertCount(1, $image->hideRequests());
        $this->assertInstanceOf(HideRequest::class, $image->pendingHideRequest());
        $this->assertSame(ImageHideRequestStatus::PENDING, $image->pendingHideRequest()->status());
    }

    /**
     * 正常系：findByIdでapprovedのhideRequest履歴も読み込まれること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithApprovedHideRequest(): void
    {
        $imageId = StrTestHelper::generateUuid();
        $requestId = StrTestHelper::generateUuid();

        CreateImage::create($imageId, [
            'resource_type' => ResourceType::TALENT->value,
            'image_usage' => ImageUsage::PROFILE->value,
        ]);

        CreateImageHideRequest::create($requestId, [
            'image_id' => $imageId,
            'status' => ImageHideRequestStatus::APPROVED->value,
        ]);

        $repository = $this->app->make(ImageRepositoryInterface::class);
        $image = $repository->findById(new ImageIdentifier($imageId));

        $this->assertInstanceOf(Image::class, $image);
        $this->assertCount(1, $image->hideRequests());
        $this->assertNull($image->pendingHideRequest());
        $this->assertSame(ImageHideRequestStatus::APPROVED, $image->latestHideRequest()->status());
    }

    /**
     * 正常系：saveでhideRequestが保存されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithHideRequest(): void
    {
        $imageId = StrTestHelper::generateUuid();

        CreateImage::create($imageId, [
            'resource_type' => ResourceType::TALENT->value,
            'image_usage' => ImageUsage::PROFILE->value,
        ]);

        $repository = $this->app->make(ImageRepositoryInterface::class);
        $image = $repository->findById(new ImageIdentifier($imageId));

        $image->requestHide('Test Requester', 'requester@example.com', 'Privacy concern');
        $repository->save($image);

        $this->assertDatabaseHas('image_hide_requests', [
            'image_id' => $imageId,
            'requester_name' => 'Test Requester',
            'requester_email' => 'requester@example.com',
            'reason' => 'Privacy concern',
            'status' => 'pending',
        ]);
    }

    /**
     * 正常系：saveでhideRequestのステータス更新（approve）が永続化されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithHideRequestApprove(): void
    {
        $imageId = StrTestHelper::generateUuid();
        $requestId = StrTestHelper::generateUuid();

        CreateImage::create($imageId, [
            'resource_type' => ResourceType::TALENT->value,
            'image_usage' => ImageUsage::PROFILE->value,
        ]);

        CreateImageHideRequest::create($requestId, [
            'image_id' => $imageId,
            'status' => ImageHideRequestStatus::PENDING->value,
        ]);

        $repository = $this->app->make(ImageRepositoryInterface::class);
        $image = $repository->findById(new ImageIdentifier($imageId));

        $reviewerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $image->approveHideRequest($reviewerIdentifier, 'Approved for privacy');
        $repository->save($image);

        $this->assertDatabaseHas('image_hide_requests', [
            'image_id' => $imageId,
            'status' => 'approved',
            'reviewer_id' => (string) $reviewerIdentifier,
            'reviewer_comment' => 'Approved for privacy',
        ]);

        $this->assertDatabaseHas('wiki_images', [
            'id' => $imageId,
            'is_hidden' => true,
        ]);
    }

    /**
     * 正常系：saveでhideRequestのステータス更新（reject）が永続化されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithHideRequestReject(): void
    {
        $imageId = StrTestHelper::generateUuid();
        $requestId = StrTestHelper::generateUuid();

        CreateImage::create($imageId, [
            'resource_type' => ResourceType::TALENT->value,
            'image_usage' => ImageUsage::PROFILE->value,
        ]);

        CreateImageHideRequest::create($requestId, [
            'image_id' => $imageId,
            'status' => ImageHideRequestStatus::PENDING->value,
        ]);

        $repository = $this->app->make(ImageRepositoryInterface::class);
        $image = $repository->findById(new ImageIdentifier($imageId));

        $reviewerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $image->rejectHideRequest($reviewerIdentifier, 'Not applicable');
        $repository->save($image);

        $this->assertDatabaseHas('image_hide_requests', [
            'image_id' => $imageId,
            'status' => 'rejected',
            'reviewer_id' => (string) $reviewerIdentifier,
            'reviewer_comment' => 'Not applicable',
        ]);

        $this->assertDatabaseHas('wiki_images', [
            'id' => $imageId,
            'is_hidden' => false,
        ]);
    }

    /**
     * 正常系：existsPendingHideRequestがpendingの場合にtrueを返すこと.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testExistsPendingHideRequestTrue(): void
    {
        $imageId = StrTestHelper::generateUuid();
        $requestId = StrTestHelper::generateUuid();

        CreateImage::create($imageId, [
            'resource_type' => ResourceType::TALENT->value,
            'image_usage' => ImageUsage::PROFILE->value,
        ]);

        CreateImageHideRequest::create($requestId, [
            'image_id' => $imageId,
            'status' => ImageHideRequestStatus::PENDING->value,
        ]);

        $repository = $this->app->make(ImageRepositoryInterface::class);
        $this->assertTrue($repository->existsPendingHideRequest(new ImageIdentifier($imageId)));
    }

    /**
     * 正常系：existsPendingHideRequestがpendingでない場合にfalseを返すこと.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testExistsPendingHideRequestFalse(): void
    {
        $imageId = StrTestHelper::generateUuid();

        CreateImage::create($imageId, [
            'resource_type' => ResourceType::TALENT->value,
            'image_usage' => ImageUsage::PROFILE->value,
        ]);

        $repository = $this->app->make(ImageRepositoryInterface::class);
        $this->assertFalse($repository->existsPendingHideRequest(new ImageIdentifier($imageId)));
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
