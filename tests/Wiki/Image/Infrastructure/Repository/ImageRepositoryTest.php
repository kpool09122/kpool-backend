<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\Repository\ImageRepositoryInterface;
use Source\Wiki\Image\Domain\ValueObject\DeletionRequest;
use Source\Wiki\Image\Domain\ValueObject\RightsConfirmationAgreed;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\CreateImage;
use Tests\Helper\CreateImageDeletionRequest;
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
            'translation_set_identifier' => $wikiId,
            'image_path' => '/images/talents/profile.jpg',
            'display_order' => 1,
        ]);

        $repository = $this->app->make(ImageRepositoryInterface::class);
        $image = $repository->findById(new ImageIdentifier($imageId));

        $this->assertInstanceOf(Image::class, $image);
        $this->assertSame($imageId, (string) $image->imageIdentifier());
        $this->assertSame(ResourceType::TALENT, $image->resourceType());
        $this->assertSame($wikiId, (string) $image->translationSetIdentifier());
        $this->assertSame('/images/talents/profile.jpg', (string) $image->imagePath());
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
            'translation_set_identifier' => $wikiId,
            'image_path' => '/images/talents/profile.jpg',
            'display_order' => 1,
        ]);

        CreateImage::create($imageId2, [
            'resource_type' => ResourceType::TALENT->value,
            'translation_set_identifier' => $wikiId,
            'image_path' => '/images/talents/additional.jpg',
            'display_order' => 2,
        ]);

        CreateImage::create($otherImageId, [
            'resource_type' => ResourceType::GROUP->value,
            'translation_set_identifier' => StrTestHelper::generateUuid(),
            'image_path' => '/images/groups/cover.jpg',
            'display_order' => 1,
        ]);

        $repository = $this->app->make(ImageRepositoryInterface::class);
        $images = $repository->findByResource(
            ResourceType::TALENT,
            new TranslationSetIdentifier($wikiId),
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
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
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
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new ImagePath('/images/talents/new-profile.jpg'),
            1,
            'https://example.com/source',
            'Example Source',
            'Profile image of talent',
            false,
            null,
            $uploaderIdentifier,
            $now,
            $approverIdentifier,
            $now,
            null,
            null,
            new RightsConfirmationAgreed(true),
        );

        $repository = $this->app->make(ImageRepositoryInterface::class);
        $repository->save($image);

        $this->assertDatabaseHas('wiki_images', [
            'id' => (string) $image->imageIdentifier(),
            'resource_type' => $image->resourceType()->value,
            'translation_set_identifier' => (string) $image->translationSetIdentifier(),
            'image_path' => (string) $image->imagePath(),
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
            'translation_set_identifier' => $wikiId,
            'image_path' => '/images/talents/old.jpg',
            'display_order' => 1,
        ]);

        $uploaderIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $approverIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $now = new DateTimeImmutable();

        $image = new Image(
            new ImageIdentifier($imageId),
            ResourceType::TALENT,
            new TranslationSetIdentifier($wikiId),
            new ImagePath('/images/talents/updated.jpg'),
            2,
            'https://example.com/updated-source',
            'Updated Source',
            'Updated alt text',
            false,
            null,
            $uploaderIdentifier,
            $now,
            $approverIdentifier,
            $now,
            null,
            null,
            new RightsConfirmationAgreed(true),
        );

        $repository = $this->app->make(ImageRepositoryInterface::class);
        $repository->save($image);

        $this->assertDatabaseHas('wiki_images', [
            'id' => $imageId,
            'image_path' => '/images/talents/updated.jpg',
            'display_order' => 2,
        ]);

        $this->assertDatabaseCount('wiki_images', 1);
    }

    /**
     * 正常系：findByIdでdeletionRequest履歴が読み込まれ、pendingDeletionRequestを取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithPendingDeletionRequest(): void
    {
        $imageId = StrTestHelper::generateUuid();
        $requestId = StrTestHelper::generateUuid();

        CreateImage::create($imageId, [
            'resource_type' => ResourceType::TALENT->value,
        ]);

        CreateImageDeletionRequest::create($requestId, [
            'image_id' => $imageId,
        ]);

        $repository = $this->app->make(ImageRepositoryInterface::class);
        $image = $repository->findById(new ImageIdentifier($imageId));

        $this->assertInstanceOf(Image::class, $image);
        $this->assertCount(1, $image->deletionRequests());
        $this->assertInstanceOf(DeletionRequest::class, $image->pendingDeletionRequest());
        $this->assertNull($image->pendingDeletionRequest()->reviewedAt());
    }

    /**
     * 正常系：findByIdでレビュー済みのdeletionRequest履歴も読み込まれること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithApprovedDeletionRequest(): void
    {
        $imageId = StrTestHelper::generateUuid();
        $requestId = StrTestHelper::generateUuid();
        $reviewerId = StrTestHelper::generateUuid();
        $reviewedAt = now();

        CreateImage::create($imageId, [
            'resource_type' => ResourceType::TALENT->value,
        ]);

        CreateImageDeletionRequest::create($requestId, [
            'image_id' => $imageId,
            'reviewer_id' => $reviewerId,
            'reviewed_at' => $reviewedAt,
            'reviewer_comment' => 'Reviewed',
        ]);

        $repository = $this->app->make(ImageRepositoryInterface::class);
        $image = $repository->findById(new ImageIdentifier($imageId));

        $this->assertInstanceOf(Image::class, $image);
        $this->assertCount(1, $image->deletionRequests());
        $this->assertNull($image->pendingDeletionRequest());
        $this->assertSame($reviewerId, (string) $image->latestDeletionRequest()->reviewerIdentifier());
        $this->assertSame('Reviewed', $image->latestDeletionRequest()->reviewerComment());
    }

    /**
     * 正常系：saveでdeletionRequestが保存されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithDeletionRequest(): void
    {
        $imageId = StrTestHelper::generateUuid();

        CreateImage::create($imageId, [
            'resource_type' => ResourceType::TALENT->value,
        ]);

        $repository = $this->app->make(ImageRepositoryInterface::class);
        $image = $repository->findById(new ImageIdentifier($imageId));

        $image->requestDeletion('Test Requester', 'requester@example.com', 'Privacy concern');
        $repository->save($image);

        $this->assertDatabaseHas('image_deletion_requests', [
            'image_id' => $imageId,
            'requester_name' => 'Test Requester',
            'requester_email' => 'requester@example.com',
            'reason' => 'Privacy concern',
        ]);
    }

    /**
     * 正常系：saveでdeletionRequestのレビュー情報（approve）が永続化されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithDeletionRequestApprove(): void
    {
        $imageId = StrTestHelper::generateUuid();
        $requestId = StrTestHelper::generateUuid();

        CreateImage::create($imageId, [
            'resource_type' => ResourceType::TALENT->value,
        ]);

        CreateImageDeletionRequest::create($requestId, [
            'image_id' => $imageId,
        ]);

        $repository = $this->app->make(ImageRepositoryInterface::class);
        $image = $repository->findById(new ImageIdentifier($imageId));

        $reviewerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $image->approveDeletionRequest($reviewerIdentifier, 'Approved for privacy');
        $repository->save($image);

        $this->assertDatabaseHas('image_deletion_requests', [
            'image_id' => $imageId,
            'reviewer_id' => (string) $reviewerIdentifier,
            'reviewer_comment' => 'Approved for privacy',
        ]);

    }

    /**
     * 正常系：saveでdeletionRequestのレビュー情報（reject）が永続化されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithDeletionRequestReject(): void
    {
        $imageId = StrTestHelper::generateUuid();
        $requestId = StrTestHelper::generateUuid();

        CreateImage::create($imageId, [
            'resource_type' => ResourceType::TALENT->value,
        ]);

        CreateImageDeletionRequest::create($requestId, [
            'image_id' => $imageId,
        ]);

        $repository = $this->app->make(ImageRepositoryInterface::class);
        $image = $repository->findById(new ImageIdentifier($imageId));

        $reviewerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $image->rejectDeletionRequest($reviewerIdentifier, 'Not applicable');
        $repository->save($image);

        $this->assertDatabaseHas('image_deletion_requests', [
            'image_id' => $imageId,
            'reviewer_id' => (string) $reviewerIdentifier,
            'reviewer_comment' => 'Not applicable',
        ]);

        $this->assertDatabaseHas('wiki_images', [
            'id' => $imageId,
            'is_hidden' => false,
        ]);
    }

    /**
     * 正常系：existsPendingDeletionRequestがpendingの場合にtrueを返すこと.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testExistsPendingDeletionRequestTrue(): void
    {
        $imageId = StrTestHelper::generateUuid();
        $requestId = StrTestHelper::generateUuid();

        CreateImage::create($imageId, [
            'resource_type' => ResourceType::TALENT->value,
        ]);

        CreateImageDeletionRequest::create($requestId, [
            'image_id' => $imageId,
        ]);

        $repository = $this->app->make(ImageRepositoryInterface::class);
        $this->assertTrue($repository->existsPendingDeletionRequest(new ImageIdentifier($imageId)));
    }

    /**
     * 正常系：existsPendingDeletionRequestがpendingでない場合にfalseを返すこと.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testExistsPendingDeletionRequestFalse(): void
    {
        $imageId = StrTestHelper::generateUuid();

        CreateImage::create($imageId, [
            'resource_type' => ResourceType::TALENT->value,
        ]);

        $repository = $this->app->make(ImageRepositoryInterface::class);
        $this->assertFalse($repository->existsPendingDeletionRequest(new ImageIdentifier($imageId)));
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
        ]);

        $this->assertDatabaseHas('wiki_images', ['id' => $imageId]);

        $repository = $this->app->make(ImageRepositoryInterface::class);
        $repository->delete(new ImageIdentifier($imageId));

        $this->assertDatabaseMissing('wiki_images', ['id' => $imageId]);
    }
}
