<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Domain\Entity\DraftImage;
use Source\Wiki\Image\Domain\Repository\DraftImageRepositoryInterface;
use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\CreateDraftImage;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DraftImageRepositoryTest extends TestCase
{
    /**
     * 正常系：指定したIDの下書き画像が取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $draftId = StrTestHelper::generateUuid();
        $publishedId = StrTestHelper::generateUuid();
        $draftResourceId = StrTestHelper::generateUuid();
        $editorId = StrTestHelper::generateUuid();

        CreateDraftImage::create($draftId, [
            'published_id' => $publishedId,
            'resource_type' => ResourceType::TALENT->value,
            'draft_resource_identifier' => $draftResourceId,
            'editor_id' => $editorId,
            'image_path' => '/images/talents/profile.jpg',
            'image_usage' => ImageUsage::PROFILE->value,
            'display_order' => 1,
        ]);

        $repository = $this->app->make(DraftImageRepositoryInterface::class);
        $draft = $repository->findById(new ImageIdentifier($draftId));

        $this->assertInstanceOf(DraftImage::class, $draft);
        $this->assertSame($draftId, (string) $draft->imageIdentifier());
        $this->assertSame($publishedId, (string) $draft->publishedImageIdentifier());
        $this->assertSame(ResourceType::TALENT, $draft->resourceType());
        $this->assertSame($draftResourceId, (string) $draft->draftResourceIdentifier());
        $this->assertSame($editorId, (string) $draft->editorIdentifier());
        $this->assertSame('/images/talents/profile.jpg', (string) $draft->imagePath());
        $this->assertSame(ImageUsage::PROFILE, $draft->imageUsage());
        $this->assertSame(1, $draft->displayOrder());
        $this->assertInstanceOf(DateTimeImmutable::class, $draft->createdAt());
    }

    /**
     * 正常系：publishedImageIdentifierがnullの場合、nullが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenPublishedIdIsNull(): void
    {
        $draftId = StrTestHelper::generateUuid();

        CreateDraftImage::create($draftId, [
            'published_id' => null,
            'resource_type' => ResourceType::GROUP->value,
            'image_path' => '/images/groups/logo.png',
            'image_usage' => ImageUsage::LOGO->value,
        ]);

        $repository = $this->app->make(DraftImageRepositoryInterface::class);
        $draft = $repository->findById(new ImageIdentifier($draftId));

        $this->assertInstanceOf(DraftImage::class, $draft);
        $this->assertNull($draft->publishedImageIdentifier());
    }

    /**
     * 正常系：指定したIDの下書き画像が存在しない場合、nullが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNotExist(): void
    {
        $repository = $this->app->make(DraftImageRepositoryInterface::class);
        $draft = $repository->findById(new ImageIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($draft);
    }

    /**
     * 正常系：指定したリソースタイプとリソースIDに紐づく下書き画像が取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByDraftResource(): void
    {
        $draftResourceId = StrTestHelper::generateUuid();

        $draftId1 = StrTestHelper::generateUuid();
        $draftId2 = StrTestHelper::generateUuid();
        $otherDraftId = StrTestHelper::generateUuid();

        CreateDraftImage::create($draftId1, [
            'resource_type' => ResourceType::TALENT->value,
            'draft_resource_identifier' => $draftResourceId,
            'image_path' => '/images/talents/profile1.jpg',
            'image_usage' => ImageUsage::PROFILE->value,
            'display_order' => 1,
        ]);

        CreateDraftImage::create($draftId2, [
            'resource_type' => ResourceType::TALENT->value,
            'draft_resource_identifier' => $draftResourceId,
            'image_path' => '/images/talents/additional.jpg',
            'image_usage' => ImageUsage::ADDITIONAL->value,
            'display_order' => 2,
        ]);

        CreateDraftImage::create($otherDraftId, [
            'resource_type' => ResourceType::GROUP->value,
            'draft_resource_identifier' => StrTestHelper::generateUuid(),
            'image_path' => '/images/groups/cover.jpg',
            'image_usage' => ImageUsage::COVER->value,
            'display_order' => 1,
        ]);

        $repository = $this->app->make(DraftImageRepositoryInterface::class);
        $drafts = $repository->findByDraftResource(
            ResourceType::TALENT,
            new ResourceIdentifier($draftResourceId),
        );

        $this->assertCount(2, $drafts);
        $draftIds = array_map(
            static fn (DraftImage $draft): string => (string) $draft->imageIdentifier(),
            $drafts,
        );
        $this->assertContains($draftId1, $draftIds);
        $this->assertContains($draftId2, $draftIds);
        $this->assertNotContains($otherDraftId, $draftIds);

        // display_orderでソートされていることを確認
        $this->assertSame($draftId1, (string) $drafts[0]->imageIdentifier());
        $this->assertSame($draftId2, (string) $drafts[1]->imageIdentifier());
    }

    /**
     * 正常系：指定したリソースに紐づく下書き画像が存在しない場合、空配列が返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByDraftResourceWhenNotExist(): void
    {
        $repository = $this->app->make(DraftImageRepositoryInterface::class);
        $drafts = $repository->findByDraftResource(
            ResourceType::TALENT,
            new ResourceIdentifier(StrTestHelper::generateUuid()),
        );

        $this->assertIsArray($drafts);
        $this->assertEmpty($drafts);
    }

    /**
     * 正常系：正しく下書き画像を保存できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSave(): void
    {
        $draft = new DraftImage(
            new ImageIdentifier(StrTestHelper::generateUuid()),
            new ImageIdentifier(StrTestHelper::generateUuid()),
            ResourceType::TALENT,
            new ResourceIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new ImagePath('/images/talents/new-profile.jpg'),
            ImageUsage::PROFILE,
            1,
            'https://example.com/source',
            'Example Source',
            'Profile image of talent',
            ApprovalStatus::UnderReview,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable(),
        );

        $repository = $this->app->make(DraftImageRepositoryInterface::class);
        $repository->save($draft);

        $this->assertDatabaseHas('draft_wiki_images', [
            'id' => (string) $draft->imageIdentifier(),
            'published_id' => (string) $draft->publishedImageIdentifier(),
            'resource_type' => $draft->resourceType()->value,
            'draft_resource_identifier' => (string) $draft->draftResourceIdentifier(),
            'editor_id' => (string) $draft->editorIdentifier(),
            'image_path' => (string) $draft->imagePath(),
            'image_usage' => $draft->imageUsage()->value,
            'display_order' => $draft->displayOrder(),
            'source_url' => $draft->sourceUrl(),
            'source_name' => $draft->sourceName(),
            'alt_text' => $draft->altText(),
            'status' => $draft->status()->value,
        ]);
    }

    /**
     * 正常系：publishedImageIdentifierがnullの場合も正しく保存できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWhenPublishedIdIsNull(): void
    {
        $draft = new DraftImage(
            new ImageIdentifier(StrTestHelper::generateUuid()),
            null,
            ResourceType::GROUP,
            new ResourceIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new ImagePath('/images/groups/logo.png'),
            ImageUsage::LOGO,
            1,
            'https://example.com/source',
            'Example Source',
            'Logo image of group',
            ApprovalStatus::UnderReview,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable(),
        );

        $repository = $this->app->make(DraftImageRepositoryInterface::class);
        $repository->save($draft);

        $this->assertDatabaseHas('draft_wiki_images', [
            'id' => (string) $draft->imageIdentifier(),
            'published_id' => null,
            'resource_type' => $draft->resourceType()->value,
        ]);
    }

    /**
     * 正常系：既存の下書き画像を更新できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveUpdate(): void
    {
        $draftId = StrTestHelper::generateUuid();
        $draftResourceId = StrTestHelper::generateUuid();
        $editorId = StrTestHelper::generateUuid();

        CreateDraftImage::create($draftId, [
            'resource_type' => ResourceType::TALENT->value,
            'draft_resource_identifier' => $draftResourceId,
            'editor_id' => $editorId,
            'image_path' => '/images/talents/old.jpg',
            'image_usage' => ImageUsage::PROFILE->value,
            'display_order' => 1,
        ]);

        $draft = new DraftImage(
            new ImageIdentifier($draftId),
            null,
            ResourceType::TALENT,
            new ResourceIdentifier($draftResourceId),
            new PrincipalIdentifier($editorId),
            new ImagePath('/images/talents/updated.jpg'),
            ImageUsage::COVER,
            2,
            'https://example.com/updated-source',
            'Updated Source',
            'Updated alt text',
            ApprovalStatus::UnderReview,
            new DateTimeImmutable('2024-01-01 00:00:00'),
            new DateTimeImmutable(),
        );

        $repository = $this->app->make(DraftImageRepositoryInterface::class);
        $repository->save($draft);

        $this->assertDatabaseHas('draft_wiki_images', [
            'id' => $draftId,
            'image_path' => '/images/talents/updated.jpg',
            'image_usage' => ImageUsage::COVER->value,
            'display_order' => 2,
        ]);

        $this->assertDatabaseCount('draft_wiki_images', 1);
    }

    /**
     * 正常系：正しく下書き画像を削除できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testDelete(): void
    {
        $draftId = StrTestHelper::generateUuid();

        CreateDraftImage::create($draftId, [
            'resource_type' => ResourceType::TALENT->value,
            'image_path' => '/images/talents/to-delete.jpg',
            'image_usage' => ImageUsage::ADDITIONAL->value,
        ]);

        $this->assertDatabaseHas('draft_wiki_images', ['id' => $draftId]);

        $repository = $this->app->make(DraftImageRepositoryInterface::class);
        $repository->delete(new ImageIdentifier($draftId));

        $this->assertDatabaseMissing('draft_wiki_images', ['id' => $draftId]);
    }

    /**
     * 正常系：指定したリソースに紐づく下書き画像をすべて削除できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testDeleteByDraftResource(): void
    {
        $draftResourceId = StrTestHelper::generateUuid();

        $draftId1 = StrTestHelper::generateUuid();
        $draftId2 = StrTestHelper::generateUuid();
        $otherDraftId = StrTestHelper::generateUuid();

        CreateDraftImage::create($draftId1, [
            'resource_type' => ResourceType::TALENT->value,
            'draft_resource_identifier' => $draftResourceId,
            'image_path' => '/images/talents/image1.jpg',
            'display_order' => 1,
        ]);

        CreateDraftImage::create($draftId2, [
            'resource_type' => ResourceType::TALENT->value,
            'draft_resource_identifier' => $draftResourceId,
            'image_path' => '/images/talents/image2.jpg',
            'display_order' => 2,
        ]);

        CreateDraftImage::create($otherDraftId, [
            'resource_type' => ResourceType::GROUP->value,
            'draft_resource_identifier' => StrTestHelper::generateUuid(),
            'image_path' => '/images/groups/image.jpg',
        ]);

        $this->assertDatabaseHas('draft_wiki_images', ['id' => $draftId1]);
        $this->assertDatabaseHas('draft_wiki_images', ['id' => $draftId2]);
        $this->assertDatabaseHas('draft_wiki_images', ['id' => $otherDraftId]);

        $repository = $this->app->make(DraftImageRepositoryInterface::class);
        $repository->deleteByDraftResource(
            ResourceType::TALENT,
            new ResourceIdentifier($draftResourceId),
        );

        $this->assertDatabaseMissing('draft_wiki_images', ['id' => $draftId1]);
        $this->assertDatabaseMissing('draft_wiki_images', ['id' => $draftId2]);
        $this->assertDatabaseHas('draft_wiki_images', ['id' => $otherDraftId]);
    }
}
