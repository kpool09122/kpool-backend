<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLink\Domain\Entity\VideoLink;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkRepositoryInterface;
use Source\Wiki\VideoLink\Domain\ValueObject\DurationSeconds;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoTitle;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\CreateVideoLink;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class VideoLinkRepositoryTest extends TestCase
{
    #[Group('useDb')]
    public function testCreatorPrincipalIdentifierIsPersistedAndRestored(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $identifier = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        $video = new VideoLink($identifier, ResourceType::TALENT, new WikiIdentifier(StrTestHelper::generateUuid()), new ExternalContentLink('https://example.com/manual'), VideoUsage::MUSIC_VIDEO, new VideoTitle('Title'), null, null, new DateTimeImmutable(), new DurationSeconds(253), $principalIdentifier);
        $repository = $this->app()->make(VideoLinkRepositoryInterface::class);
        $repository->save($video);
        $restored = $repository->findById($identifier);
        self::assertNotNull($restored);
        self::assertSame((string) $principalIdentifier, (string) $restored->creatorPrincipalIdentifier());
        self::assertSame(253, $restored->durationSeconds()?->value());
        $this->assertDatabaseHas('wiki_video_links', ['id' => (string) $identifier, 'creator_principal_id' => (string) $principalIdentifier]);
    }

    #[Group('useDb')]
    #[DataProvider('videoDurations')]
    public function testDurationIsPersistedAndRestored(?int $durationSeconds): void
    {
        $videoLinkId = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        $videoLink = new VideoLink(
            $videoLinkId,
            ResourceType::TALENT,
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new ExternalContentLink('https://www.youtube.com/watch?v=test'),
            VideoUsage::YOUTUBE_AUTO_VIEW_COUNT,
            new VideoTitle('Test Video'),
            null,
            null,
            new DateTimeImmutable(),
            $durationSeconds !== null ? new DurationSeconds($durationSeconds) : null,
        );
        $repository = $this->app()->make(VideoLinkRepositoryInterface::class);
        $repository->save($videoLink);

        $this->assertDatabaseHas('wiki_video_links', [
            'id' => (string) $videoLinkId,
            'duration_seconds' => $durationSeconds,
        ]);
        $this->assertSame($durationSeconds, $repository->findById($videoLinkId)?->durationSeconds()?->value());

        $this->assertNull($repository->findById($videoLinkId)?->creatorPrincipalIdentifier());
    }

    /** @return array<string, array{int|null}> */
    public static function videoDurations(): array
    {
        return ['known' => [253], 'zero' => [0], 'unknown' => [null]];
    }

    /**
     * 正常系：指定したIDの動画リンクが取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $videoLinkId = StrTestHelper::generateUuid();
        $wikiId = StrTestHelper::generateUuid();

        CreateVideoLink::create($videoLinkId, [
            'resource_type' => ResourceType::TALENT->value,
            'wiki_id' => $wikiId,
            'url' => 'https://www.youtube.com/watch?v=test123',
            'video_usage' => VideoUsage::MUSIC_VIDEO->value,
            'title' => 'Test Music Video',
        ]);

        $repository = $this->app()->make(VideoLinkRepositoryInterface::class);
        $videoLink = $repository->findById(new VideoLinkIdentifier($videoLinkId));

        $this->assertInstanceOf(VideoLink::class, $videoLink);
        $this->assertSame($videoLinkId, (string) $videoLink->videoLinkIdentifier());
        $this->assertSame(ResourceType::TALENT, $videoLink->resourceType());
        $this->assertSame($wikiId, (string) $videoLink->wikiIdentifier());
        $this->assertSame('https://www.youtube.com/watch?v=test123', (string) $videoLink->url());
        $this->assertSame(VideoUsage::MUSIC_VIDEO, $videoLink->videoUsage());
        $this->assertSame('Test Music Video', (string) $videoLink->title());
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
        $repository = $this->app()->make(VideoLinkRepositoryInterface::class);
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
        $wikiId = StrTestHelper::generateUuid();

        $videoLinkId1 = StrTestHelper::generateUuid();
        $videoLinkId2 = StrTestHelper::generateUuid();
        $otherVideoLinkId = StrTestHelper::generateUuid();

        CreateVideoLink::create($videoLinkId1, [
            'resource_type' => ResourceType::TALENT->value,
            'wiki_id' => $wikiId,
            'url' => 'https://www.youtube.com/watch?v=video1',
            'video_usage' => VideoUsage::MUSIC_VIDEO->value,
            'title' => 'Music Video 1',
        ]);

        CreateVideoLink::create($videoLinkId2, [
            'resource_type' => ResourceType::TALENT->value,
            'wiki_id' => $wikiId,
            'url' => 'https://www.youtube.com/watch?v=video2',
            'video_usage' => VideoUsage::LIVE->value,
            'title' => 'Live Video',
        ]);

        CreateVideoLink::create($otherVideoLinkId, [
            'resource_type' => ResourceType::GROUP->value,
            'wiki_id' => StrTestHelper::generateUuid(),
            'url' => 'https://www.youtube.com/watch?v=other',
            'video_usage' => VideoUsage::INTERVIEW->value,
            'title' => 'Other Video',
        ]);

        $repository = $this->app()->make(VideoLinkRepositoryInterface::class);
        $videoLinks = $repository->findByResource(
            ResourceType::TALENT,
            new WikiIdentifier($wikiId),
        );

        $this->assertCount(2, $videoLinks);
        $videoLinkIds = array_map(
            static fn (VideoLink $videoLink): string => (string) $videoLink->videoLinkIdentifier(),
            $videoLinks,
        );
        $this->assertContains($videoLinkId1, $videoLinkIds);
        $this->assertContains($videoLinkId2, $videoLinkIds);
        $this->assertNotContains($otherVideoLinkId, $videoLinkIds);

    }

    /**
     * 正常系：指定したリソースに紐づく動画リンクが存在しない場合、空配列が返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByResourceWhenNotExist(): void
    {
        $repository = $this->app()->make(VideoLinkRepositoryInterface::class);
        $videoLinks = $repository->findByResource(
            ResourceType::TALENT,
            new WikiIdentifier(StrTestHelper::generateUuid()),
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
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new ExternalContentLink('https://www.youtube.com/watch?v=newvideo'),
            VideoUsage::MUSIC_VIDEO,
            new VideoTitle('New Music Video'),
            null,
            null,
            new DateTimeImmutable(),
            durationSeconds: null,
        );

        $repository = $this->app()->make(VideoLinkRepositoryInterface::class);
        $repository->save($videoLink);

        $this->assertDatabaseHas('wiki_video_links', [
            'id' => (string) $videoLink->videoLinkIdentifier(),
            'resource_type' => $videoLink->resourceType()->value,
            'wiki_id' => (string) $videoLink->wikiIdentifier(),
            'url' => (string) $videoLink->url(),
            'video_usage' => $videoLink->videoUsage()->value,
            'title' => (string) $videoLink->title(),
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
        $wikiId = StrTestHelper::generateUuid();

        CreateVideoLink::create($videoLinkId, [
            'resource_type' => ResourceType::TALENT->value,
            'wiki_id' => $wikiId,
            'url' => 'https://www.youtube.com/watch?v=old',
            'video_usage' => VideoUsage::MUSIC_VIDEO->value,
            'title' => 'Old Title',
        ]);

        $videoLink = new VideoLink(
            new VideoLinkIdentifier($videoLinkId),
            ResourceType::TALENT,
            new WikiIdentifier($wikiId),
            new ExternalContentLink('https://www.youtube.com/watch?v=updated'),
            VideoUsage::LIVE,
            new VideoTitle('Updated Title'),
            null,
            null,
            new DateTimeImmutable(),
            durationSeconds: null,
        );

        $repository = $this->app()->make(VideoLinkRepositoryInterface::class);
        $repository->save($videoLink);

        $this->assertDatabaseHas('wiki_video_links', [
            'id' => $videoLinkId,
            'url' => 'https://www.youtube.com/watch?v=updated',
            'video_usage' => VideoUsage::LIVE->value,
            'title' => 'Updated Title',
        ]);

        $this->assertDatabaseCount('wiki_video_links', 1);
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

        $this->assertDatabaseHas('wiki_video_links', ['id' => $videoLinkId]);

        $repository = $this->app()->make(VideoLinkRepositoryInterface::class);
        $repository->delete(new VideoLinkIdentifier($videoLinkId));

        $this->assertDatabaseMissing('wiki_video_links', ['id' => $videoLinkId]);
    }

    /**
     * 正常系：指定したリソースに紐づく動画リンクを一括削除できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testDeleteByResource(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $otherResourceId = StrTestHelper::generateUuid();

        $videoLinkId1 = StrTestHelper::generateUuid();
        $videoLinkId2 = StrTestHelper::generateUuid();
        $otherVideoLinkId = StrTestHelper::generateUuid();

        CreateVideoLink::create($videoLinkId1, [
            'resource_type' => ResourceType::TALENT->value,
            'wiki_id' => $wikiId,
        ]);

        CreateVideoLink::create($videoLinkId2, [
            'resource_type' => ResourceType::TALENT->value,
            'wiki_id' => $wikiId,
        ]);

        CreateVideoLink::create($otherVideoLinkId, [
            'resource_type' => ResourceType::TALENT->value,
            'wiki_id' => $otherResourceId,
        ]);

        $repository = $this->app()->make(VideoLinkRepositoryInterface::class);
        $repository->deleteByResource(
            ResourceType::TALENT,
            new WikiIdentifier($wikiId),
        );

        $this->assertDatabaseMissing('wiki_video_links', ['id' => $videoLinkId1]);
        $this->assertDatabaseMissing('wiki_video_links', ['id' => $videoLinkId2]);
        $this->assertDatabaseHas('wiki_video_links', ['id' => $otherVideoLinkId]);
    }

    /**
     * 正常系：指定したリソースに紐づく自動収集済みの動画リンクのみを削除できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testDeleteAutoCollectedByResource(): void
    {
        $wikiId = StrTestHelper::generateUuid();

        $manualVideoLinkId = StrTestHelper::generateUuid();
        $autoViewCountId = StrTestHelper::generateUuid();
        $autoLikeCountId = StrTestHelper::generateUuid();
        $autoRecentId = StrTestHelper::generateUuid();

        CreateVideoLink::create($manualVideoLinkId, [
            'resource_type' => ResourceType::TALENT->value,
            'wiki_id' => $wikiId,
            'video_usage' => VideoUsage::MUSIC_VIDEO->value,
        ]);

        CreateVideoLink::create($autoViewCountId, [
            'resource_type' => ResourceType::TALENT->value,
            'wiki_id' => $wikiId,
            'video_usage' => VideoUsage::YOUTUBE_AUTO_VIEW_COUNT->value,
        ]);

        CreateVideoLink::create($autoLikeCountId, [
            'resource_type' => ResourceType::TALENT->value,
            'wiki_id' => $wikiId,
            'video_usage' => VideoUsage::YOUTUBE_AUTO_LIKE_COUNT->value,
        ]);

        CreateVideoLink::create($autoRecentId, [
            'resource_type' => ResourceType::TALENT->value,
            'wiki_id' => $wikiId,
            'video_usage' => VideoUsage::YOUTUBE_AUTO_RECENT_POPULAR->value,
        ]);

        $repository = $this->app()->make(VideoLinkRepositoryInterface::class);
        $repository->deleteAutoCollectedByResource(
            ResourceType::TALENT,
            new WikiIdentifier($wikiId),
        );

        $this->assertDatabaseHas('wiki_video_links', ['id' => $manualVideoLinkId]);
        $this->assertDatabaseMissing('wiki_video_links', ['id' => $autoViewCountId]);
        $this->assertDatabaseMissing('wiki_video_links', ['id' => $autoLikeCountId]);
        $this->assertDatabaseMissing('wiki_video_links', ['id' => $autoRecentId]);
    }

    /**
     * 正常系：指定したリソースに紐づく動画リンクのうち、指定したURLに該当するものを返却すること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByResourceAndUrls(): void
    {
        $wikiId = StrTestHelper::generateUuid();

        $videoLinkId1 = StrTestHelper::generateUuid();
        $videoLinkId2 = StrTestHelper::generateUuid();

        $url1 = 'https://www.youtube.com/watch?v=existing1';
        $url2 = 'https://www.youtube.com/watch?v=existing2';
        $url3 = 'https://www.youtube.com/watch?v=notexisting';

        CreateVideoLink::create($videoLinkId1, [
            'resource_type' => ResourceType::TALENT->value,
            'wiki_id' => $wikiId,
            'url' => $url1,
            'video_usage' => VideoUsage::MUSIC_VIDEO->value,
            'title' => 'Existing Video 1',
        ]);

        CreateVideoLink::create($videoLinkId2, [
            'resource_type' => ResourceType::TALENT->value,
            'wiki_id' => $wikiId,
            'url' => $url2,
            'video_usage' => VideoUsage::LIVE->value,
            'title' => 'Existing Video 2',
        ]);

        $repository = $this->app()->make(VideoLinkRepositoryInterface::class);
        $videoLinks = $repository->findByResourceAndUrls(
            ResourceType::TALENT,
            new WikiIdentifier($wikiId),
            [$url1, $url2, $url3],
        );

        $this->assertCount(2, $videoLinks);
        $this->assertContainsOnlyInstancesOf(VideoLink::class, $videoLinks);

        $returnedUrls = array_map(
            static fn (VideoLink $videoLink): string => (string) $videoLink->url(),
            $videoLinks,
        );
        $this->assertContains($url1, $returnedUrls);
        $this->assertContains($url2, $returnedUrls);
        $this->assertNotContains($url3, $returnedUrls);
    }

    /**
     * 正常系：異なるリソースの動画リンクは含まれないこと.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByResourceAndUrlsDoesNotReturnOtherResources(): void
    {
        $targetResourceId = StrTestHelper::generateUuid();
        $otherResourceId = StrTestHelper::generateUuid();

        $url = 'https://www.youtube.com/watch?v=sameurl';

        CreateVideoLink::create(StrTestHelper::generateUuid(), [
            'resource_type' => ResourceType::TALENT->value,
            'wiki_id' => $otherResourceId,
            'url' => $url,
            'video_usage' => VideoUsage::MUSIC_VIDEO->value,
            'title' => 'Other Resource Video',
        ]);

        $repository = $this->app()->make(VideoLinkRepositoryInterface::class);
        $videoLinks = $repository->findByResourceAndUrls(
            ResourceType::TALENT,
            new WikiIdentifier($targetResourceId),
            [$url],
        );

        $this->assertEmpty($videoLinks);
    }

    /**
     * 正常系：空配列を渡した場合、空配列が返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByResourceAndUrlsWithEmptyUrls(): void
    {
        $wikiId = StrTestHelper::generateUuid();

        CreateVideoLink::create(StrTestHelper::generateUuid(), [
            'resource_type' => ResourceType::TALENT->value,
            'wiki_id' => $wikiId,
            'url' => 'https://www.youtube.com/watch?v=existing',
            'video_usage' => VideoUsage::MUSIC_VIDEO->value,
            'title' => 'Existing Video',
        ]);

        $repository = $this->app()->make(VideoLinkRepositoryInterface::class);
        $videoLinks = $repository->findByResourceAndUrls(
            ResourceType::TALENT,
            new WikiIdentifier($wikiId),
            [],
        );

        $this->assertEmpty($videoLinks);
    }
}
