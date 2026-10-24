<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Command\CollectVideoLinks;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\VideoLink\Application\UseCase\Command\CollectVideoLinks\CollectVideoLinksInterface;
use Source\Wiki\VideoLink\Application\UseCase\Command\CollectVideoLinks\CollectVideoLinksOutput;
use Source\Wiki\VideoLink\Domain\Entity\VideoLink;
use Source\Wiki\VideoLink\Domain\Entity\VideoLinkCollectionStatus;
use Source\Wiki\VideoLink\Domain\Factory\VideoLinkFactoryInterface;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkCollectionStatusRepositoryInterface;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkRepositoryInterface;
use Source\Wiki\VideoLink\Domain\Service\YouTubeSearchServiceInterface;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkCollectionStatusIdentifier;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoTitle;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Source\Wiki\VideoLink\Domain\ValueObject\YouTubeVideoInfo;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class CollectVideoLinksTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * 正常系：収集対象リソースが存在しない場合、noTargetResourceが返ること.
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function testProcessReturnsNoTargetResourceWhenNoTarget(): void
    {
        $collectionStatusRepository = Mockery::mock(VideoLinkCollectionStatusRepositoryInterface::class);
        $collectionStatusRepository->shouldReceive('findNextTargetResource')
            ->once()
            ->andReturn(null);

        $this->app()->instance(VideoLinkCollectionStatusRepositoryInterface::class, $collectionStatusRepository);
        $this->app()->instance(WikiRepositoryInterface::class, Mockery::mock(WikiRepositoryInterface::class));
        $this->app()->instance(YouTubeSearchServiceInterface::class, Mockery::mock(YouTubeSearchServiceInterface::class));
        $this->app()->instance(VideoLinkRepositoryInterface::class, Mockery::mock(VideoLinkRepositoryInterface::class));
        $this->app()->instance(VideoLinkFactoryInterface::class, Mockery::mock(VideoLinkFactoryInterface::class));

        $useCase = $this->app()->make(CollectVideoLinksInterface::class);
        $output = new CollectVideoLinksOutput();
        $useCase->process($output);

        $this->assertFalse($output->processed);
        $this->assertNull($output->resourceType);
        $this->assertNull($output->wikiIdentifier);
        $this->assertSame(0, $output->collectedCount);
    }

    /**
     * 正常系：Talentリソースから動画を収集できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessCollectsTalentVideos(): void
    {
        $statusId = StrTestHelper::generateUuid();
        $resourceId = StrTestHelper::generateUuid();

        $status = new VideoLinkCollectionStatus(
            new VideoLinkCollectionStatusIdentifier($statusId),
            ResourceType::TALENT,
            new WikiIdentifier($resourceId),
            null,
            new DateTimeImmutable(),
        );

        $wiki = $this->createDummyWiki($resourceId, ResourceType::TALENT, '채영');

        $videos = [
            new YouTubeVideoInfo(
                videoId: 'video1',
                title: 'Test Video 1',
                url: 'https://www.youtube.com/watch?v=video1',
                thumbnailUrl: 'https://i.ytimg.com/vi/video1/hqdefault.jpg',
                videoUsage: VideoUsage::YOUTUBE_AUTO_VIEW_COUNT,
                publishedAt: new DateTimeImmutable(),
                durationSeconds: 253,
            ),
            new YouTubeVideoInfo(
                videoId: 'video2',
                title: 'Test Video 2',
                url: 'https://www.youtube.com/watch?v=video2',
                thumbnailUrl: 'https://i.ytimg.com/vi/video2/hqdefault.jpg',
                videoUsage: VideoUsage::YOUTUBE_AUTO_LIKE_COUNT,
                publishedAt: new DateTimeImmutable(),
                durationSeconds: null,
            ),
        ];

        $collectionStatusRepository = Mockery::mock(VideoLinkCollectionStatusRepositoryInterface::class);
        $collectionStatusRepository->shouldReceive('findNextTargetResource')
            ->once()
            ->andReturn($status);
        $collectionStatusRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(static fn (VideoLinkCollectionStatus $updated): bool =>
                $updated !== $status
                && $updated->identifier() === $status->identifier()
                && $updated->resourceType() === $status->resourceType()
                && $updated->wikiIdentifier() === $status->wikiIdentifier()
                && $updated->createdAt() === $status->createdAt()
                && $updated->lastCollectedAt() !== null));

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(static fn (WikiIdentifier $id): bool => (string) $id === $resourceId))
            ->andReturn($wiki);

        $youtubeSearchService = Mockery::mock(YouTubeSearchServiceInterface::class);
        $youtubeSearchService->shouldReceive('searchVideos')
            ->once()
            ->with('채영 k-pop')
            ->andReturn($videos);

        $videoLinkRepository = Mockery::mock(VideoLinkRepositoryInterface::class);
        $videoLinkRepository->shouldReceive('deleteAutoCollectedByResource')
            ->once()
            ->with(ResourceType::TALENT, Mockery::on(static fn (WikiIdentifier $id): bool => (string) $id === $resourceId));
        $videoLinkRepository->shouldReceive('findByResourceAndUrls')
            ->once()
            ->andReturn([]);
        $videoLinkRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(static fn (VideoLink $videoLink): bool =>
                $videoLink->durationSeconds()?->value() === 253
                && $videoLink->resourceType() === ResourceType::TALENT
                && (string) $videoLink->wikiIdentifier() === $resourceId
                && (string) $videoLink->url() === $videos[0]->url()
                && (string) $videoLink->title() === (string) $videos[0]->title()
                && $videoLink->videoUsage() === $videos[0]->videoUsage()
                && (string) $videoLink->thumbnailUrl() === $videos[0]->thumbnailUrl()
                && $videoLink->publishedAt() === $videos[0]->publishedAt()
                && $videoLink->creatorPrincipalIdentifier() === null));
        $videoLinkRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(static fn (VideoLink $videoLink): bool =>
                $videoLink->durationSeconds()?->value() === null
                && $videoLink->resourceType() === ResourceType::TALENT
                && (string) $videoLink->wikiIdentifier() === $resourceId
                && (string) $videoLink->url() === $videos[1]->url()
                && (string) $videoLink->title() === $videos[1]->title()
                && $videoLink->videoUsage() === $videos[1]->videoUsage()
                && (string) $videoLink->thumbnailUrl() === $videos[1]->thumbnailUrl()
                && $videoLink->publishedAt() === $videos[1]->publishedAt()
                && $videoLink->creatorPrincipalIdentifier() === null));

        $videoLinkFactory = $this->app()->make(VideoLinkFactoryInterface::class);

        $this->app()->instance(VideoLinkCollectionStatusRepositoryInterface::class, $collectionStatusRepository);
        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(YouTubeSearchServiceInterface::class, $youtubeSearchService);
        $this->app()->instance(VideoLinkRepositoryInterface::class, $videoLinkRepository);
        $this->app()->instance(VideoLinkFactoryInterface::class, $videoLinkFactory);

        $useCase = $this->app()->make(CollectVideoLinksInterface::class);
        $output = new CollectVideoLinksOutput();
        $useCase->process($output);

        $this->assertTrue($output->processed);
        $this->assertSame(ResourceType::TALENT, $output->resourceType);
        $this->assertSame($resourceId, (string) $output->wikiIdentifier);
        $this->assertSame(2, $output->collectedCount);
        $this->assertNull($status->lastCollectedAt());
    }

    /**
     * 正常系：リソースが見つからない場合、resourceNotFoundが返ること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessReturnsResourceNotFoundWhenTalentNotFound(): void
    {
        $statusId = StrTestHelper::generateUuid();
        $resourceId = StrTestHelper::generateUuid();

        $status = new VideoLinkCollectionStatus(
            new VideoLinkCollectionStatusIdentifier($statusId),
            ResourceType::TALENT,
            new WikiIdentifier($resourceId),
            null,
            new DateTimeImmutable(),
        );

        $collectionStatusRepository = Mockery::mock(VideoLinkCollectionStatusRepositoryInterface::class);
        $collectionStatusRepository->shouldReceive('findNextTargetResource')
            ->once()
            ->andReturn($status);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->once()
            ->andReturn(null);

        $this->app()->instance(VideoLinkCollectionStatusRepositoryInterface::class, $collectionStatusRepository);
        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(YouTubeSearchServiceInterface::class, Mockery::mock(YouTubeSearchServiceInterface::class));
        $this->app()->instance(VideoLinkRepositoryInterface::class, Mockery::mock(VideoLinkRepositoryInterface::class));
        $this->app()->instance(VideoLinkFactoryInterface::class, Mockery::mock(VideoLinkFactoryInterface::class));

        $useCase = $this->app()->make(CollectVideoLinksInterface::class);
        $output = new CollectVideoLinksOutput();
        $useCase->process($output);

        $this->assertFalse($output->processed);
        $this->assertSame(ResourceType::TALENT, $output->resourceType);
        $this->assertSame($resourceId, (string) $output->wikiIdentifier);
        $this->assertSame('Resource not found', $output->message);
    }

    /**
     * 正常系：Groupリソースから動画を収集できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessCollectsGroupVideos(): void
    {
        $statusId = StrTestHelper::generateUuid();
        $resourceId = StrTestHelper::generateUuid();

        $status = new VideoLinkCollectionStatus(
            new VideoLinkCollectionStatusIdentifier($statusId),
            ResourceType::GROUP,
            new WikiIdentifier($resourceId),
            null,
            new DateTimeImmutable(),
        );

        $groupName = 'TWICE';
        $wiki = $this->createDummyWiki($resourceId, ResourceType::GROUP, $groupName);

        $videos = [
            new YouTubeVideoInfo(
                videoId: 'video1',
                title: 'Test Video 1',
                url: 'https://www.youtube.com/watch?v=video1',
                thumbnailUrl: 'https://i.ytimg.com/vi/video1/hqdefault.jpg',
                videoUsage: VideoUsage::YOUTUBE_AUTO_VIEW_COUNT,
                publishedAt: new DateTimeImmutable(),
                durationSeconds: null,
            ),
        ];

        $collectionStatusRepository = Mockery::mock(VideoLinkCollectionStatusRepositoryInterface::class);
        $collectionStatusRepository->shouldReceive('findNextTargetResource')
            ->once()
            ->andReturn($status);
        $collectionStatusRepository->shouldReceive('save')
            ->once();

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(static fn (WikiIdentifier $id): bool => (string) $id === $resourceId))
            ->andReturn($wiki);

        $youtubeSearchService = Mockery::mock(YouTubeSearchServiceInterface::class);
        $youtubeSearchService->shouldReceive('searchVideos')
            ->once()
            ->with($groupName . ' k-pop')
            ->andReturn($videos);

        $videoLinkRepository = Mockery::mock(VideoLinkRepositoryInterface::class);
        $videoLinkRepository->shouldReceive('deleteAutoCollectedByResource')
            ->once();
        $videoLinkRepository->shouldReceive('findByResourceAndUrls')
            ->once()
            ->andReturn([]);
        $videoLinkRepository->shouldReceive('save')
            ->once();

        $videoLinkFactory = $this->app()->make(VideoLinkFactoryInterface::class);

        $this->app()->instance(VideoLinkCollectionStatusRepositoryInterface::class, $collectionStatusRepository);
        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(YouTubeSearchServiceInterface::class, $youtubeSearchService);
        $this->app()->instance(VideoLinkRepositoryInterface::class, $videoLinkRepository);
        $this->app()->instance(VideoLinkFactoryInterface::class, $videoLinkFactory);

        $useCase = $this->app()->make(CollectVideoLinksInterface::class);
        $output = new CollectVideoLinksOutput();
        $useCase->process($output);

        $this->assertTrue($output->processed);
        $this->assertSame(ResourceType::GROUP, $output->resourceType);
        $this->assertSame(1, $output->collectedCount);
    }

    /**
     * 正常系：Songリソースから動画を収集できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessCollectsSongVideos(): void
    {
        $statusId = StrTestHelper::generateUuid();
        $resourceId = StrTestHelper::generateUuid();

        $status = new VideoLinkCollectionStatus(
            new VideoLinkCollectionStatusIdentifier($statusId),
            ResourceType::SONG,
            new WikiIdentifier($resourceId),
            null,
            new DateTimeImmutable(),
        );

        $wiki = $this->createDummyWiki($resourceId, ResourceType::SONG, 'テストソング');

        $videos = [
            new YouTubeVideoInfo(
                videoId: 'video1',
                title: 'Test Song Video 1',
                url: 'https://www.youtube.com/watch?v=video1',
                thumbnailUrl: 'https://i.ytimg.com/vi/video1/hqdefault.jpg',
                videoUsage: VideoUsage::YOUTUBE_AUTO_VIEW_COUNT,
                publishedAt: new DateTimeImmutable(),
                durationSeconds: null,
            ),
        ];

        $collectionStatusRepository = Mockery::mock(VideoLinkCollectionStatusRepositoryInterface::class);
        $collectionStatusRepository->shouldReceive('findNextTargetResource')
            ->once()
            ->andReturn($status);
        $collectionStatusRepository->shouldReceive('save')
            ->once();

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(static fn (WikiIdentifier $id): bool => (string) $id === $resourceId))
            ->andReturn($wiki);

        $youtubeSearchService = Mockery::mock(YouTubeSearchServiceInterface::class);
        $youtubeSearchService->shouldReceive('searchVideos')
            ->once()
            ->with('テストソング k-pop')
            ->andReturn($videos);

        $videoLinkRepository = Mockery::mock(VideoLinkRepositoryInterface::class);
        $videoLinkRepository->shouldReceive('deleteAutoCollectedByResource')
            ->once();
        $videoLinkRepository->shouldReceive('findByResourceAndUrls')
            ->once()
            ->andReturn([]);
        $videoLinkRepository->shouldReceive('save')
            ->once();

        $videoLinkFactory = $this->app()->make(VideoLinkFactoryInterface::class);

        $this->app()->instance(VideoLinkCollectionStatusRepositoryInterface::class, $collectionStatusRepository);
        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(YouTubeSearchServiceInterface::class, $youtubeSearchService);
        $this->app()->instance(VideoLinkRepositoryInterface::class, $videoLinkRepository);
        $this->app()->instance(VideoLinkFactoryInterface::class, $videoLinkFactory);

        $useCase = $this->app()->make(CollectVideoLinksInterface::class);
        $output = new CollectVideoLinksOutput();
        $useCase->process($output);

        $this->assertTrue($output->processed);
        $this->assertSame(ResourceType::SONG, $output->resourceType);
        $this->assertSame($resourceId, (string) $output->wikiIdentifier);
        $this->assertSame(1, $output->collectedCount);
    }

    /**
     * 正常系：未対応のリソースタイプの場合、resourceNotFoundが返ること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessReturnsResourceNotFoundForUnsupportedResourceType(): void
    {
        $statusId = StrTestHelper::generateUuid();
        $resourceId = StrTestHelper::generateUuid();

        $status = new VideoLinkCollectionStatus(
            new VideoLinkCollectionStatusIdentifier($statusId),
            ResourceType::AGENCY,
            new WikiIdentifier($resourceId),
            null,
            new DateTimeImmutable(),
        );

        $collectionStatusRepository = Mockery::mock(VideoLinkCollectionStatusRepositoryInterface::class);
        $collectionStatusRepository->shouldReceive('findNextTargetResource')
            ->once()
            ->andReturn($status);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->once()
            ->andReturn(null);

        $this->app()->instance(VideoLinkCollectionStatusRepositoryInterface::class, $collectionStatusRepository);
        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(YouTubeSearchServiceInterface::class, Mockery::mock(YouTubeSearchServiceInterface::class));
        $this->app()->instance(VideoLinkRepositoryInterface::class, Mockery::mock(VideoLinkRepositoryInterface::class));
        $this->app()->instance(VideoLinkFactoryInterface::class, Mockery::mock(VideoLinkFactoryInterface::class));

        $useCase = $this->app()->make(CollectVideoLinksInterface::class);
        $output = new CollectVideoLinksOutput();
        $useCase->process($output);

        $this->assertFalse($output->processed);
        $this->assertSame(ResourceType::AGENCY, $output->resourceType);
        $this->assertSame($resourceId, (string) $output->wikiIdentifier);
        $this->assertSame('Resource not found', $output->message);
    }

    /**
     * 正常系：最終収集日が直近1ヶ月以内の場合、recentlyCollectedが返ること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessReturnsRecentlyCollectedWhenCollectedWithinOneMonth(): void
    {
        $statusId = StrTestHelper::generateUuid();
        $resourceId = StrTestHelper::generateUuid();

        $status = new VideoLinkCollectionStatus(
            new VideoLinkCollectionStatusIdentifier($statusId),
            ResourceType::TALENT,
            new WikiIdentifier($resourceId),
            new DateTimeImmutable('-2 weeks'),
            new DateTimeImmutable(),
        );

        $collectionStatusRepository = Mockery::mock(VideoLinkCollectionStatusRepositoryInterface::class);
        $collectionStatusRepository->shouldReceive('findNextTargetResource')
            ->once()
            ->andReturn($status);

        $this->app()->instance(VideoLinkCollectionStatusRepositoryInterface::class, $collectionStatusRepository);
        $this->app()->instance(WikiRepositoryInterface::class, Mockery::mock(WikiRepositoryInterface::class));
        $this->app()->instance(YouTubeSearchServiceInterface::class, Mockery::mock(YouTubeSearchServiceInterface::class));
        $this->app()->instance(VideoLinkRepositoryInterface::class, Mockery::mock(VideoLinkRepositoryInterface::class));
        $this->app()->instance(VideoLinkFactoryInterface::class, Mockery::mock(VideoLinkFactoryInterface::class));

        $useCase = $this->app()->make(CollectVideoLinksInterface::class);
        $output = new CollectVideoLinksOutput();
        $useCase->process($output);

        $this->assertFalse($output->processed);
        $this->assertSame(ResourceType::TALENT, $output->resourceType);
        $this->assertSame($resourceId, (string) $output->wikiIdentifier);
        $this->assertSame('Resource was collected within the last month', $output->message);
    }

    /**
     * 正常系：最終収集日が1ヶ月以上前の場合、通常通り収集処理が行われること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessCollectsVideosWhenLastCollectedMoreThanOneMonthAgo(): void
    {
        $statusId = StrTestHelper::generateUuid();
        $resourceId = StrTestHelper::generateUuid();

        $status = new VideoLinkCollectionStatus(
            new VideoLinkCollectionStatusIdentifier($statusId),
            ResourceType::TALENT,
            new WikiIdentifier($resourceId),
            new DateTimeImmutable('-2 months'),
            new DateTimeImmutable(),
        );

        $wiki = $this->createDummyWiki($resourceId, ResourceType::TALENT, '채영');

        $videos = [
            new YouTubeVideoInfo(
                videoId: 'video1',
                title: 'Test Video 1',
                url: 'https://www.youtube.com/watch?v=video1',
                thumbnailUrl: 'https://i.ytimg.com/vi/video1/hqdefault.jpg',
                videoUsage: VideoUsage::YOUTUBE_AUTO_VIEW_COUNT,
                publishedAt: new DateTimeImmutable(),
                durationSeconds: null,
            ),
        ];

        $collectionStatusRepository = Mockery::mock(VideoLinkCollectionStatusRepositoryInterface::class);
        $collectionStatusRepository->shouldReceive('findNextTargetResource')
            ->once()
            ->andReturn($status);
        $collectionStatusRepository->shouldReceive('save')
            ->once();

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->once()
            ->andReturn($wiki);

        $youtubeSearchService = Mockery::mock(YouTubeSearchServiceInterface::class);
        $youtubeSearchService->shouldReceive('searchVideos')
            ->once()
            ->andReturn($videos);

        $videoLinkRepository = Mockery::mock(VideoLinkRepositoryInterface::class);
        $videoLinkRepository->shouldReceive('deleteAutoCollectedByResource')
            ->once();
        $videoLinkRepository->shouldReceive('findByResourceAndUrls')
            ->once()
            ->andReturn([]);
        $videoLinkRepository->shouldReceive('save')
            ->once();

        $videoLinkFactory = $this->app()->make(VideoLinkFactoryInterface::class);

        $this->app()->instance(VideoLinkCollectionStatusRepositoryInterface::class, $collectionStatusRepository);
        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(YouTubeSearchServiceInterface::class, $youtubeSearchService);
        $this->app()->instance(VideoLinkRepositoryInterface::class, $videoLinkRepository);
        $this->app()->instance(VideoLinkFactoryInterface::class, $videoLinkFactory);

        $useCase = $this->app()->make(CollectVideoLinksInterface::class);
        $output = new CollectVideoLinksOutput();
        $useCase->process($output);

        $this->assertTrue($output->processed);
        $this->assertSame(ResourceType::TALENT, $output->resourceType);
        $this->assertSame($resourceId, (string) $output->wikiIdentifier);
        $this->assertSame(1, $output->collectedCount);
    }

    /**
     * 正常系：既に登録されているURLは重複登録されないこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessSkipsDuplicateUrls(): void
    {
        $statusId = StrTestHelper::generateUuid();
        $resourceId = StrTestHelper::generateUuid();

        $status = new VideoLinkCollectionStatus(
            new VideoLinkCollectionStatusIdentifier($statusId),
            ResourceType::TALENT,
            new WikiIdentifier($resourceId),
            null,
            new DateTimeImmutable(),
        );

        $wiki = $this->createDummyWiki($resourceId, ResourceType::TALENT, '채영');

        $existingUrl = 'https://www.youtube.com/watch?v=existing';
        $newUrl = 'https://www.youtube.com/watch?v=new';

        $videos = [
            new YouTubeVideoInfo(
                videoId: 'existing',
                title: 'Existing Video',
                url: $existingUrl,
                thumbnailUrl: 'https://i.ytimg.com/vi/existing/hqdefault.jpg',
                videoUsage: VideoUsage::YOUTUBE_AUTO_VIEW_COUNT,
                publishedAt: new DateTimeImmutable(),
                durationSeconds: null,
            ),
            new YouTubeVideoInfo(
                videoId: 'new',
                title: 'New Video',
                url: $newUrl,
                thumbnailUrl: 'https://i.ytimg.com/vi/new/hqdefault.jpg',
                videoUsage: VideoUsage::YOUTUBE_AUTO_LIKE_COUNT,
                publishedAt: new DateTimeImmutable(),
                durationSeconds: null,
            ),
        ];

        $existingVideoLink = new VideoLink(
            new VideoLinkIdentifier(StrTestHelper::generateUuid()),
            ResourceType::TALENT,
            new WikiIdentifier($resourceId),
            new ExternalContentLink($existingUrl),
            VideoUsage::MUSIC_VIDEO,
            new VideoTitle('Existing Video'),
            null,
            null,
            new DateTimeImmutable(),
            durationSeconds: null,
        );

        $collectionStatusRepository = Mockery::mock(VideoLinkCollectionStatusRepositoryInterface::class);
        $collectionStatusRepository->shouldReceive('findNextTargetResource')
            ->once()
            ->andReturn($status);
        $collectionStatusRepository->shouldReceive('save')
            ->once();

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')
            ->once()
            ->andReturn($wiki);

        $youtubeSearchService = Mockery::mock(YouTubeSearchServiceInterface::class);
        $youtubeSearchService->shouldReceive('searchVideos')
            ->once()
            ->andReturn($videos);

        $videoLinkRepository = Mockery::mock(VideoLinkRepositoryInterface::class);
        $videoLinkRepository->shouldReceive('deleteAutoCollectedByResource')
            ->once();
        $videoLinkRepository->shouldReceive('findByResourceAndUrls')
            ->once()
            ->with(
                ResourceType::TALENT,
                Mockery::on(static fn (WikiIdentifier $id): bool => (string) $id === $resourceId),
                [$existingUrl, $newUrl],
            )
            ->andReturn([$existingVideoLink]);
        $videoLinkRepository->shouldReceive('save')
            ->once();

        $videoLinkFactory = $this->app()->make(VideoLinkFactoryInterface::class);

        $this->app()->instance(VideoLinkCollectionStatusRepositoryInterface::class, $collectionStatusRepository);
        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(YouTubeSearchServiceInterface::class, $youtubeSearchService);
        $this->app()->instance(VideoLinkRepositoryInterface::class, $videoLinkRepository);
        $this->app()->instance(VideoLinkFactoryInterface::class, $videoLinkFactory);

        $useCase = $this->app()->make(CollectVideoLinksInterface::class);
        $output = new CollectVideoLinksOutput();
        $useCase->process($output);

        $this->assertTrue($output->processed);
        $this->assertSame(ResourceType::TALENT, $output->resourceType);
        $this->assertSame(2, $output->collectedCount);
    }

    private function createDummyWiki(string $resourceId, ResourceType $resourceType, string $name): Wiki
    {
        $basic = Mockery::mock(BasicInterface::class);
        $basic->shouldReceive('name')->andReturn(new Name($name));

        return new Wiki(
            new WikiIdentifier($resourceId),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('gr-test-slug'),
            Language::KOREAN,
            $resourceType,
            $basic, /** @phpstan-ignore argument.type */
            new SectionContentCollection(),
            null,
            new Version(1),
        );
    }
}
