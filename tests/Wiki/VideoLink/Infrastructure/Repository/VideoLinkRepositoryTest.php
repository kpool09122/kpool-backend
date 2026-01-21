<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLink\Domain\Entity\VideoLink;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkRepositoryInterface;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Tests\Helper\CreateVideoLink;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class VideoLinkRepositoryTest extends TestCase
{
    /**
     * 正常系：指定したIDの動画リンクが取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $videoLinkId = StrTestHelper::generateUuid();
        $resourceId = StrTestHelper::generateUuid();

        CreateVideoLink::create($videoLinkId, [
            'resource_type' => ResourceType::TALENT->value,
            'resource_identifier' => $resourceId,
            'url' => 'https://www.youtube.com/watch?v=test123',
            'video_usage' => VideoUsage::MUSIC_VIDEO->value,
            'title' => 'Test Music Video',
            'display_order' => 1,
        ]);

        $repository = $this->app->make(VideoLinkRepositoryInterface::class);
        $videoLink = $repository->findById(new VideoLinkIdentifier($videoLinkId));

        $this->assertInstanceOf(VideoLink::class, $videoLink);
        $this->assertSame($videoLinkId, (string) $videoLink->videoLinkIdentifier());
        $this->assertSame(ResourceType::TALENT, $videoLink->resourceType());
        $this->assertSame($resourceId, (string) $videoLink->resourceIdentifier());
        $this->assertSame('https://www.youtube.com/watch?v=test123', (string) $videoLink->url());
        $this->assertSame(VideoUsage::MUSIC_VIDEO, $videoLink->videoUsage());
        $this->assertSame('Test Music Video', $videoLink->title());
        $this->assertSame(1, $videoLink->displayOrder());
        $this->assertInstanceOf(DateTimeImmutable::class, $videoLink->createdAt());
    }

    /**
     * 正常系：指定したIDの動画リンクが存在しない場合、nullが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNotExist(): void
    {
        $repository = $this->app->make(VideoLinkRepositoryInterface::class);
        $videoLink = $repository->findById(new VideoLinkIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($videoLink);
    }

    /**
     * 正常系：指定したリソースタイプとリソースIDに紐づく動画リンクが取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByResource(): void
    {
        $resourceId = StrTestHelper::generateUuid();

        $videoLinkId1 = StrTestHelper::generateUuid();
        $videoLinkId2 = StrTestHelper::generateUuid();
        $otherVideoLinkId = StrTestHelper::generateUuid();

        CreateVideoLink::create($videoLinkId1, [
            'resource_type' => ResourceType::TALENT->value,
            'resource_identifier' => $resourceId,
            'url' => 'https://www.youtube.com/watch?v=video1',
            'video_usage' => VideoUsage::MUSIC_VIDEO->value,
            'title' => 'Music Video 1',
            'display_order' => 1,
        ]);

        CreateVideoLink::create($videoLinkId2, [
            'resource_type' => ResourceType::TALENT->value,
            'resource_identifier' => $resourceId,
            'url' => 'https://www.youtube.com/watch?v=video2',
            'video_usage' => VideoUsage::LIVE->value,
            'title' => 'Live Video',
            'display_order' => 2,
        ]);

        CreateVideoLink::create($otherVideoLinkId, [
            'resource_type' => ResourceType::GROUP->value,
            'resource_identifier' => StrTestHelper::generateUuid(),
            'url' => 'https://www.youtube.com/watch?v=other',
            'video_usage' => VideoUsage::INTERVIEW->value,
            'title' => 'Other Video',
            'display_order' => 1,
        ]);

        $repository = $this->app->make(VideoLinkRepositoryInterface::class);
        $videoLinks = $repository->findByResource(
            ResourceType::TALENT,
            new ResourceIdentifier($resourceId),
        );

        $this->assertCount(2, $videoLinks);
        $videoLinkIds = array_map(
            static fn (VideoLink $videoLink): string => (string) $videoLink->videoLinkIdentifier(),
            $videoLinks,
        );
        $this->assertContains($videoLinkId1, $videoLinkIds);
        $this->assertContains($videoLinkId2, $videoLinkIds);
        $this->assertNotContains($otherVideoLinkId, $videoLinkIds);

        // display_orderでソートされていることを確認
        $this->assertSame($videoLinkId1, (string) $videoLinks[0]->videoLinkIdentifier());
        $this->assertSame($videoLinkId2, (string) $videoLinks[1]->videoLinkIdentifier());
    }

    /**
     * 正常系：指定したリソースに紐づく動画リンクが存在しない場合、空配列が返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByResourceWhenNotExist(): void
    {
        $repository = $this->app->make(VideoLinkRepositoryInterface::class);
        $videoLinks = $repository->findByResource(
            ResourceType::TALENT,
            new ResourceIdentifier(StrTestHelper::generateUuid()),
        );

        $this->assertIsArray($videoLinks);
        $this->assertEmpty($videoLinks);
    }

    /**
     * 正常系：正しく動画リンクを保存できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSave(): void
    {
        $videoLink = new VideoLink(
            new VideoLinkIdentifier(StrTestHelper::generateUuid()),
            ResourceType::TALENT,
            new ResourceIdentifier(StrTestHelper::generateUuid()),
            new ExternalContentLink('https://www.youtube.com/watch?v=newvideo'),
            VideoUsage::MUSIC_VIDEO,
            'New Music Video',
            null,
            null,
            1,
            new DateTimeImmutable(),
        );

        $repository = $this->app->make(VideoLinkRepositoryInterface::class);
        $repository->save($videoLink);

        $this->assertDatabaseHas('video_links', [
            'id' => (string) $videoLink->videoLinkIdentifier(),
            'resource_type' => $videoLink->resourceType()->value,
            'resource_identifier' => (string) $videoLink->resourceIdentifier(),
            'url' => (string) $videoLink->url(),
            'video_usage' => $videoLink->videoUsage()->value,
            'title' => $videoLink->title(),
            'display_order' => $videoLink->displayOrder(),
        ]);
    }

    /**
     * 正常系：既存の動画リンクを更新できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveUpdate(): void
    {
        $videoLinkId = StrTestHelper::generateUuid();
        $resourceId = StrTestHelper::generateUuid();

        CreateVideoLink::create($videoLinkId, [
            'resource_type' => ResourceType::TALENT->value,
            'resource_identifier' => $resourceId,
            'url' => 'https://www.youtube.com/watch?v=old',
            'video_usage' => VideoUsage::MUSIC_VIDEO->value,
            'title' => 'Old Title',
            'display_order' => 1,
        ]);

        $videoLink = new VideoLink(
            new VideoLinkIdentifier($videoLinkId),
            ResourceType::TALENT,
            new ResourceIdentifier($resourceId),
            new ExternalContentLink('https://www.youtube.com/watch?v=updated'),
            VideoUsage::LIVE,
            'Updated Title',
            null,
            null,
            2,
            new DateTimeImmutable(),
        );

        $repository = $this->app->make(VideoLinkRepositoryInterface::class);
        $repository->save($videoLink);

        $this->assertDatabaseHas('video_links', [
            'id' => $videoLinkId,
            'url' => 'https://www.youtube.com/watch?v=updated',
            'video_usage' => VideoUsage::LIVE->value,
            'title' => 'Updated Title',
            'display_order' => 2,
        ]);

        $this->assertDatabaseCount('video_links', 1);
    }

    /**
     * 正常系：正しく動画リンクを削除できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testDelete(): void
    {
        $videoLinkId = StrTestHelper::generateUuid();

        CreateVideoLink::create($videoLinkId, [
            'resource_type' => ResourceType::TALENT->value,
            'url' => 'https://www.youtube.com/watch?v=delete',
            'video_usage' => VideoUsage::OTHER->value,
        ]);

        $this->assertDatabaseHas('video_links', ['id' => $videoLinkId]);

        $repository = $this->app->make(VideoLinkRepositoryInterface::class);
        $repository->delete(new VideoLinkIdentifier($videoLinkId));

        $this->assertDatabaseMissing('video_links', ['id' => $videoLinkId]);
    }

    /**
     * 正常系：指定したリソースに紐づく動画リンクを一括削除できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testDeleteByResource(): void
    {
        $resourceId = StrTestHelper::generateUuid();
        $otherResourceId = StrTestHelper::generateUuid();

        $videoLinkId1 = StrTestHelper::generateUuid();
        $videoLinkId2 = StrTestHelper::generateUuid();
        $otherVideoLinkId = StrTestHelper::generateUuid();

        CreateVideoLink::create($videoLinkId1, [
            'resource_type' => ResourceType::TALENT->value,
            'resource_identifier' => $resourceId,
        ]);

        CreateVideoLink::create($videoLinkId2, [
            'resource_type' => ResourceType::TALENT->value,
            'resource_identifier' => $resourceId,
        ]);

        CreateVideoLink::create($otherVideoLinkId, [
            'resource_type' => ResourceType::TALENT->value,
            'resource_identifier' => $otherResourceId,
        ]);

        $repository = $this->app->make(VideoLinkRepositoryInterface::class);
        $repository->deleteByResource(
            ResourceType::TALENT,
            new ResourceIdentifier($resourceId),
        );

        $this->assertDatabaseMissing('video_links', ['id' => $videoLinkId1]);
        $this->assertDatabaseMissing('video_links', ['id' => $videoLinkId2]);
        $this->assertDatabaseHas('video_links', ['id' => $otherVideoLinkId]);
    }

    /**
     * 正常系：指定したリソースに紐づく自動収集済みの動画リンクのみを削除できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testDeleteAutoCollectedByResource(): void
    {
        $resourceId = StrTestHelper::generateUuid();

        $manualVideoLinkId = StrTestHelper::generateUuid();
        $autoViewCountId = StrTestHelper::generateUuid();
        $autoLikeCountId = StrTestHelper::generateUuid();
        $autoRecentId = StrTestHelper::generateUuid();

        CreateVideoLink::create($manualVideoLinkId, [
            'resource_type' => ResourceType::TALENT->value,
            'resource_identifier' => $resourceId,
            'video_usage' => VideoUsage::MUSIC_VIDEO->value,
        ]);

        CreateVideoLink::create($autoViewCountId, [
            'resource_type' => ResourceType::TALENT->value,
            'resource_identifier' => $resourceId,
            'video_usage' => VideoUsage::YOUTUBE_AUTO_VIEW_COUNT->value,
        ]);

        CreateVideoLink::create($autoLikeCountId, [
            'resource_type' => ResourceType::TALENT->value,
            'resource_identifier' => $resourceId,
            'video_usage' => VideoUsage::YOUTUBE_AUTO_LIKE_COUNT->value,
        ]);

        CreateVideoLink::create($autoRecentId, [
            'resource_type' => ResourceType::TALENT->value,
            'resource_identifier' => $resourceId,
            'video_usage' => VideoUsage::YOUTUBE_AUTO_RECENT_POPULAR->value,
        ]);

        $repository = $this->app->make(VideoLinkRepositoryInterface::class);
        $repository->deleteAutoCollectedByResource(
            ResourceType::TALENT,
            new ResourceIdentifier($resourceId),
        );

        $this->assertDatabaseHas('video_links', ['id' => $manualVideoLinkId]);
        $this->assertDatabaseMissing('video_links', ['id' => $autoViewCountId]);
        $this->assertDatabaseMissing('video_links', ['id' => $autoLikeCountId]);
        $this->assertDatabaseMissing('video_links', ['id' => $autoRecentId]);
    }

    /**
     * 正常系：指定したリソースに紐づくdisplay_orderが最大の動画リンクが取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByResourceWithMaxDisplayOrder(): void
    {
        $resourceId = StrTestHelper::generateUuid();

        $videoLinkId1 = StrTestHelper::generateUuid();
        $videoLinkId2 = StrTestHelper::generateUuid();
        $videoLinkId3 = StrTestHelper::generateUuid();

        CreateVideoLink::create($videoLinkId1, [
            'resource_type' => ResourceType::TALENT->value,
            'resource_identifier' => $resourceId,
            'url' => 'https://www.youtube.com/watch?v=video1',
            'video_usage' => VideoUsage::MUSIC_VIDEO->value,
            'title' => 'Video 1',
            'display_order' => 1,
        ]);

        CreateVideoLink::create($videoLinkId2, [
            'resource_type' => ResourceType::TALENT->value,
            'resource_identifier' => $resourceId,
            'url' => 'https://www.youtube.com/watch?v=video2',
            'video_usage' => VideoUsage::LIVE->value,
            'title' => 'Video 2',
            'display_order' => 5,
        ]);

        CreateVideoLink::create($videoLinkId3, [
            'resource_type' => ResourceType::TALENT->value,
            'resource_identifier' => $resourceId,
            'url' => 'https://www.youtube.com/watch?v=video3',
            'video_usage' => VideoUsage::INTERVIEW->value,
            'title' => 'Video 3',
            'display_order' => 3,
        ]);

        $repository = $this->app->make(VideoLinkRepositoryInterface::class);
        $videoLink = $repository->findByResourceWithMaxDisplayOrder(
            ResourceType::TALENT,
            new ResourceIdentifier($resourceId),
        );

        $this->assertInstanceOf(VideoLink::class, $videoLink);
        $this->assertSame($videoLinkId2, (string) $videoLink->videoLinkIdentifier());
        $this->assertSame(5, $videoLink->displayOrder());
    }

    /**
     * 正常系：指定したリソースに紐づく動画リンクが存在しない場合、nullが返ること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByResourceWithMaxDisplayOrderWhenNotExist(): void
    {
        $repository = $this->app->make(VideoLinkRepositoryInterface::class);
        $videoLink = $repository->findByResourceWithMaxDisplayOrder(
            ResourceType::TALENT,
            new ResourceIdentifier(StrTestHelper::generateUuid()),
        );

        $this->assertNull($videoLink);
    }

    /**
     * 正常系：異なるリソースの動画リンクは取得されないこと.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByResourceWithMaxDisplayOrderDoesNotReturnOtherResources(): void
    {
        $targetResourceId = StrTestHelper::generateUuid();
        $otherResourceId = StrTestHelper::generateUuid();

        $targetVideoLinkId = StrTestHelper::generateUuid();
        $otherVideoLinkId = StrTestHelper::generateUuid();

        CreateVideoLink::create($targetVideoLinkId, [
            'resource_type' => ResourceType::TALENT->value,
            'resource_identifier' => $targetResourceId,
            'url' => 'https://www.youtube.com/watch?v=target',
            'video_usage' => VideoUsage::MUSIC_VIDEO->value,
            'title' => 'Target Video',
            'display_order' => 2,
        ]);

        CreateVideoLink::create($otherVideoLinkId, [
            'resource_type' => ResourceType::TALENT->value,
            'resource_identifier' => $otherResourceId,
            'url' => 'https://www.youtube.com/watch?v=other',
            'video_usage' => VideoUsage::LIVE->value,
            'title' => 'Other Video',
            'display_order' => 10,
        ]);

        $repository = $this->app->make(VideoLinkRepositoryInterface::class);
        $videoLink = $repository->findByResourceWithMaxDisplayOrder(
            ResourceType::TALENT,
            new ResourceIdentifier($targetResourceId),
        );

        $this->assertInstanceOf(VideoLink::class, $videoLink);
        $this->assertSame($targetVideoLinkId, (string) $videoLink->videoLinkIdentifier());
        $this->assertSame(2, $videoLink->displayOrder());
    }
}
