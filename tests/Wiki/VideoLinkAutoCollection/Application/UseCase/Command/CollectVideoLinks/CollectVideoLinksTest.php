<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLinkAutoCollection\Application\UseCase\Command\CollectVideoLinks;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Song\Domain\Entity\Song;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Source\Wiki\Talent\Domain\Entity\Talent;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Source\Wiki\VideoLink\Domain\Entity\VideoLink;
use Source\Wiki\VideoLink\Domain\Factory\VideoLinkFactoryInterface;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkRepositoryInterface;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Source\Wiki\VideoLinkAutoCollection\Application\UseCase\Command\CollectVideoLinks\CollectVideoLinksInterface;
use Source\Wiki\VideoLinkAutoCollection\Application\UseCase\Command\CollectVideoLinks\CollectVideoLinksOutput;
use Source\Wiki\VideoLinkAutoCollection\Domain\Entity\VideoLinkCollectionStatus;
use Source\Wiki\VideoLinkAutoCollection\Domain\Repository\VideoLinkCollectionStatusRepositoryInterface;
use Source\Wiki\VideoLinkAutoCollection\Domain\Service\YouTubeSearchServiceInterface;
use Source\Wiki\VideoLinkAutoCollection\Domain\ValueObject\VideoLinkCollectionStatusIdentifier;
use Source\Wiki\VideoLinkAutoCollection\Domain\ValueObject\YouTubeVideoInfo;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

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

        $this->app->instance(VideoLinkCollectionStatusRepositoryInterface::class, $collectionStatusRepository);
        $this->app->instance(TalentRepositoryInterface::class, Mockery::mock(TalentRepositoryInterface::class));
        $this->app->instance(GroupRepositoryInterface::class, Mockery::mock(GroupRepositoryInterface::class));
        $this->app->instance(SongRepositoryInterface::class, Mockery::mock(SongRepositoryInterface::class));
        $this->app->instance(YouTubeSearchServiceInterface::class, Mockery::mock(YouTubeSearchServiceInterface::class));
        $this->app->instance(VideoLinkRepositoryInterface::class, Mockery::mock(VideoLinkRepositoryInterface::class));
        $this->app->instance(VideoLinkFactoryInterface::class, Mockery::mock(VideoLinkFactoryInterface::class));

        $useCase = $this->app->make(CollectVideoLinksInterface::class);
        $output = new CollectVideoLinksOutput();
        $useCase->process($output);

        $this->assertFalse($output->processed);
        $this->assertNull($output->resourceType);
        $this->assertNull($output->resourceIdentifier);
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
            new ResourceIdentifier($resourceId),
            null,
            new DateTimeImmutable(),
        );

        $talent = $this->createDummyTalent($resourceId, '채영');

        $videos = [
            new YouTubeVideoInfo(
                videoId: 'video1',
                title: 'Test Video 1',
                url: 'https://www.youtube.com/watch?v=video1',
                thumbnailUrl: 'https://i.ytimg.com/vi/video1/hqdefault.jpg',
                videoUsage: VideoUsage::YOUTUBE_AUTO_VIEW_COUNT,
                publishedAt: new DateTimeImmutable(),
            ),
            new YouTubeVideoInfo(
                videoId: 'video2',
                title: 'Test Video 2',
                url: 'https://www.youtube.com/watch?v=video2',
                thumbnailUrl: 'https://i.ytimg.com/vi/video2/hqdefault.jpg',
                videoUsage: VideoUsage::YOUTUBE_AUTO_LIKE_COUNT,
                publishedAt: new DateTimeImmutable(),
            ),
        ];

        $collectionStatusRepository = Mockery::mock(VideoLinkCollectionStatusRepositoryInterface::class);
        $collectionStatusRepository->shouldReceive('findNextTargetResource')
            ->once()
            ->andReturn($status);
        $collectionStatusRepository->shouldReceive('save')
            ->once();

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(static fn (TalentIdentifier $id): bool => (string) $id === $resourceId))
            ->andReturn($talent);

        $youtubeSearchService = Mockery::mock(YouTubeSearchServiceInterface::class);
        $youtubeSearchService->shouldReceive('searchVideos')
            ->once()
            ->with('채영')
            ->andReturn($videos);

        $existingVideoLink = $this->createDummyVideoLink($resourceId, 5);

        $videoLinkRepository = Mockery::mock(VideoLinkRepositoryInterface::class);
        $videoLinkRepository->shouldReceive('deleteAutoCollectedByResource')
            ->once()
            ->with(ResourceType::TALENT, Mockery::on(static fn (ResourceIdentifier $id): bool => (string) $id === $resourceId));
        $videoLinkRepository->shouldReceive('findByResourceAndUrls')
            ->once()
            ->andReturn([]);
        $videoLinkRepository->shouldReceive('findByResourceWithMaxDisplayOrder')
            ->once()
            ->with(ResourceType::TALENT, Mockery::on(static fn (ResourceIdentifier $id): bool => (string) $id === $resourceId))
            ->andReturn($existingVideoLink);
        $videoLinkRepository->shouldReceive('save')
            ->times(2);

        $videoLinkFactory = Mockery::mock(VideoLinkFactoryInterface::class);
        $videoLinkFactory->shouldReceive('create')
            ->once()
            ->with(
                ResourceType::TALENT,
                Mockery::on(static fn (ResourceIdentifier $id): bool => (string) $id === $resourceId),
                Mockery::type(ExternalContentLink::class),
                VideoUsage::YOUTUBE_AUTO_VIEW_COUNT,
                'Test Video 1',
                6,
            )
            ->andReturn($this->createDummyVideoLink($resourceId, 6));
        $videoLinkFactory->shouldReceive('create')
            ->once()
            ->with(
                ResourceType::TALENT,
                Mockery::on(static fn (ResourceIdentifier $id): bool => (string) $id === $resourceId),
                Mockery::type(ExternalContentLink::class),
                VideoUsage::YOUTUBE_AUTO_LIKE_COUNT,
                'Test Video 2',
                7,
            )
            ->andReturn($this->createDummyVideoLink($resourceId, 7));

        $this->app->instance(VideoLinkCollectionStatusRepositoryInterface::class, $collectionStatusRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(YouTubeSearchServiceInterface::class, $youtubeSearchService);
        $this->app->instance(VideoLinkRepositoryInterface::class, $videoLinkRepository);
        $this->app->instance(VideoLinkFactoryInterface::class, $videoLinkFactory);
        $this->app->instance(GroupRepositoryInterface::class, Mockery::mock(GroupRepositoryInterface::class));
        $this->app->instance(SongRepositoryInterface::class, Mockery::mock(SongRepositoryInterface::class));

        $useCase = $this->app->make(CollectVideoLinksInterface::class);
        $output = new CollectVideoLinksOutput();
        $useCase->process($output);

        $this->assertTrue($output->processed);
        $this->assertSame(ResourceType::TALENT, $output->resourceType);
        $this->assertSame($resourceId, (string) $output->resourceIdentifier);
        $this->assertSame(2, $output->collectedCount);
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
            new ResourceIdentifier($resourceId),
            null,
            new DateTimeImmutable(),
        );

        $collectionStatusRepository = Mockery::mock(VideoLinkCollectionStatusRepositoryInterface::class);
        $collectionStatusRepository->shouldReceive('findNextTargetResource')
            ->once()
            ->andReturn($status);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->andReturn(null);

        $this->app->instance(VideoLinkCollectionStatusRepositoryInterface::class, $collectionStatusRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(GroupRepositoryInterface::class, Mockery::mock(GroupRepositoryInterface::class));
        $this->app->instance(SongRepositoryInterface::class, Mockery::mock(SongRepositoryInterface::class));
        $this->app->instance(YouTubeSearchServiceInterface::class, Mockery::mock(YouTubeSearchServiceInterface::class));
        $this->app->instance(VideoLinkRepositoryInterface::class, Mockery::mock(VideoLinkRepositoryInterface::class));
        $this->app->instance(VideoLinkFactoryInterface::class, Mockery::mock(VideoLinkFactoryInterface::class));

        $useCase = $this->app->make(CollectVideoLinksInterface::class);
        $output = new CollectVideoLinksOutput();
        $useCase->process($output);

        $this->assertFalse($output->processed);
        $this->assertSame(ResourceType::TALENT, $output->resourceType);
        $this->assertSame($resourceId, (string) $output->resourceIdentifier);
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
            new ResourceIdentifier($resourceId),
            null,
            new DateTimeImmutable(),
        );

        $groupName = 'TWICE';
        $group = $this->createDummyGroup($resourceId, $groupName, 'twice');

        $videos = [
            new YouTubeVideoInfo(
                videoId: 'video1',
                title: 'Test Video 1',
                url: 'https://www.youtube.com/watch?v=video1',
                thumbnailUrl: 'https://i.ytimg.com/vi/video1/hqdefault.jpg',
                videoUsage: VideoUsage::YOUTUBE_AUTO_VIEW_COUNT,
                publishedAt: new DateTimeImmutable(),
            ),
        ];

        $collectionStatusRepository = Mockery::mock(VideoLinkCollectionStatusRepositoryInterface::class);
        $collectionStatusRepository->shouldReceive('findNextTargetResource')
            ->once()
            ->andReturn($status);
        $collectionStatusRepository->shouldReceive('save')
            ->once();

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(static fn (GroupIdentifier $id): bool => (string) $id === $resourceId))
            ->andReturn($group);

        $youtubeSearchService = Mockery::mock(YouTubeSearchServiceInterface::class);
        $youtubeSearchService->shouldReceive('searchVideos')
            ->once()
            ->with($groupName)
            ->andReturn($videos);

        $videoLinkRepository = Mockery::mock(VideoLinkRepositoryInterface::class);
        $videoLinkRepository->shouldReceive('deleteAutoCollectedByResource')
            ->once();
        $videoLinkRepository->shouldReceive('findByResourceAndUrls')
            ->once()
            ->andReturn([]);
        $videoLinkRepository->shouldReceive('findByResourceWithMaxDisplayOrder')
            ->once()
            ->andReturn(null);
        $videoLinkRepository->shouldReceive('save')
            ->once();

        $videoLinkFactory = Mockery::mock(VideoLinkFactoryInterface::class);
        $videoLinkFactory->shouldReceive('create')
            ->once()
            ->with(
                ResourceType::GROUP,
                Mockery::on(static fn (ResourceIdentifier $id): bool => (string) $id === $resourceId),
                Mockery::type(ExternalContentLink::class),
                VideoUsage::YOUTUBE_AUTO_VIEW_COUNT,
                'Test Video 1',
                1,
            )
            ->andReturn($this->createDummyVideoLink($resourceId, 1));

        $this->app->instance(VideoLinkCollectionStatusRepositoryInterface::class, $collectionStatusRepository);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $this->app->instance(YouTubeSearchServiceInterface::class, $youtubeSearchService);
        $this->app->instance(VideoLinkRepositoryInterface::class, $videoLinkRepository);
        $this->app->instance(VideoLinkFactoryInterface::class, $videoLinkFactory);
        $this->app->instance(TalentRepositoryInterface::class, Mockery::mock(TalentRepositoryInterface::class));
        $this->app->instance(SongRepositoryInterface::class, Mockery::mock(SongRepositoryInterface::class));

        $useCase = $this->app->make(CollectVideoLinksInterface::class);
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
            new ResourceIdentifier($resourceId),
            null,
            new DateTimeImmutable(),
        );

        $song = $this->createDummySong($resourceId, 'テストソング');

        $videos = [
            new YouTubeVideoInfo(
                videoId: 'video1',
                title: 'Test Song Video 1',
                url: 'https://www.youtube.com/watch?v=video1',
                thumbnailUrl: 'https://i.ytimg.com/vi/video1/hqdefault.jpg',
                videoUsage: VideoUsage::YOUTUBE_AUTO_VIEW_COUNT,
                publishedAt: new DateTimeImmutable(),
            ),
        ];

        $collectionStatusRepository = Mockery::mock(VideoLinkCollectionStatusRepositoryInterface::class);
        $collectionStatusRepository->shouldReceive('findNextTargetResource')
            ->once()
            ->andReturn($status);
        $collectionStatusRepository->shouldReceive('save')
            ->once();

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(static fn (SongIdentifier $id): bool => (string) $id === $resourceId))
            ->andReturn($song);

        $youtubeSearchService = Mockery::mock(YouTubeSearchServiceInterface::class);
        $youtubeSearchService->shouldReceive('searchVideos')
            ->once()
            ->with('テストソング')
            ->andReturn($videos);

        $videoLinkRepository = Mockery::mock(VideoLinkRepositoryInterface::class);
        $videoLinkRepository->shouldReceive('deleteAutoCollectedByResource')
            ->once();
        $videoLinkRepository->shouldReceive('findByResourceAndUrls')
            ->once()
            ->andReturn([]);
        $videoLinkRepository->shouldReceive('findByResourceWithMaxDisplayOrder')
            ->once()
            ->andReturn(null);
        $videoLinkRepository->shouldReceive('save')
            ->once();

        $videoLinkFactory = Mockery::mock(VideoLinkFactoryInterface::class);
        $videoLinkFactory->shouldReceive('create')
            ->once()
            ->with(
                ResourceType::SONG,
                Mockery::on(static fn (ResourceIdentifier $id): bool => (string) $id === $resourceId),
                Mockery::type(ExternalContentLink::class),
                VideoUsage::YOUTUBE_AUTO_VIEW_COUNT,
                'Test Song Video 1',
                1,
            )
            ->andReturn($this->createDummyVideoLink($resourceId, 1));

        $this->app->instance(VideoLinkCollectionStatusRepositoryInterface::class, $collectionStatusRepository);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(YouTubeSearchServiceInterface::class, $youtubeSearchService);
        $this->app->instance(VideoLinkRepositoryInterface::class, $videoLinkRepository);
        $this->app->instance(VideoLinkFactoryInterface::class, $videoLinkFactory);
        $this->app->instance(TalentRepositoryInterface::class, Mockery::mock(TalentRepositoryInterface::class));
        $this->app->instance(GroupRepositoryInterface::class, Mockery::mock(GroupRepositoryInterface::class));

        $useCase = $this->app->make(CollectVideoLinksInterface::class);
        $output = new CollectVideoLinksOutput();
        $useCase->process($output);

        $this->assertTrue($output->processed);
        $this->assertSame(ResourceType::SONG, $output->resourceType);
        $this->assertSame($resourceId, (string) $output->resourceIdentifier);
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
            new ResourceIdentifier($resourceId),
            null,
            new DateTimeImmutable(),
        );

        $collectionStatusRepository = Mockery::mock(VideoLinkCollectionStatusRepositoryInterface::class);
        $collectionStatusRepository->shouldReceive('findNextTargetResource')
            ->once()
            ->andReturn($status);

        $this->app->instance(VideoLinkCollectionStatusRepositoryInterface::class, $collectionStatusRepository);
        $this->app->instance(TalentRepositoryInterface::class, Mockery::mock(TalentRepositoryInterface::class));
        $this->app->instance(GroupRepositoryInterface::class, Mockery::mock(GroupRepositoryInterface::class));
        $this->app->instance(SongRepositoryInterface::class, Mockery::mock(SongRepositoryInterface::class));
        $this->app->instance(YouTubeSearchServiceInterface::class, Mockery::mock(YouTubeSearchServiceInterface::class));
        $this->app->instance(VideoLinkRepositoryInterface::class, Mockery::mock(VideoLinkRepositoryInterface::class));
        $this->app->instance(VideoLinkFactoryInterface::class, Mockery::mock(VideoLinkFactoryInterface::class));

        $useCase = $this->app->make(CollectVideoLinksInterface::class);
        $output = new CollectVideoLinksOutput();
        $useCase->process($output);

        $this->assertFalse($output->processed);
        $this->assertSame(ResourceType::AGENCY, $output->resourceType);
        $this->assertSame($resourceId, (string) $output->resourceIdentifier);
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
            new ResourceIdentifier($resourceId),
            new DateTimeImmutable('-2 weeks'),
            new DateTimeImmutable(),
        );

        $collectionStatusRepository = Mockery::mock(VideoLinkCollectionStatusRepositoryInterface::class);
        $collectionStatusRepository->shouldReceive('findNextTargetResource')
            ->once()
            ->andReturn($status);

        $this->app->instance(VideoLinkCollectionStatusRepositoryInterface::class, $collectionStatusRepository);
        $this->app->instance(TalentRepositoryInterface::class, Mockery::mock(TalentRepositoryInterface::class));
        $this->app->instance(GroupRepositoryInterface::class, Mockery::mock(GroupRepositoryInterface::class));
        $this->app->instance(SongRepositoryInterface::class, Mockery::mock(SongRepositoryInterface::class));
        $this->app->instance(YouTubeSearchServiceInterface::class, Mockery::mock(YouTubeSearchServiceInterface::class));
        $this->app->instance(VideoLinkRepositoryInterface::class, Mockery::mock(VideoLinkRepositoryInterface::class));
        $this->app->instance(VideoLinkFactoryInterface::class, Mockery::mock(VideoLinkFactoryInterface::class));

        $useCase = $this->app->make(CollectVideoLinksInterface::class);
        $output = new CollectVideoLinksOutput();
        $useCase->process($output);

        $this->assertFalse($output->processed);
        $this->assertSame(ResourceType::TALENT, $output->resourceType);
        $this->assertSame($resourceId, (string) $output->resourceIdentifier);
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
            new ResourceIdentifier($resourceId),
            new DateTimeImmutable('-2 months'),
            new DateTimeImmutable(),
        );

        $talent = $this->createDummyTalent($resourceId, '채영');

        $videos = [
            new YouTubeVideoInfo(
                videoId: 'video1',
                title: 'Test Video 1',
                url: 'https://www.youtube.com/watch?v=video1',
                thumbnailUrl: 'https://i.ytimg.com/vi/video1/hqdefault.jpg',
                videoUsage: VideoUsage::YOUTUBE_AUTO_VIEW_COUNT,
                publishedAt: new DateTimeImmutable(),
            ),
        ];

        $collectionStatusRepository = Mockery::mock(VideoLinkCollectionStatusRepositoryInterface::class);
        $collectionStatusRepository->shouldReceive('findNextTargetResource')
            ->once()
            ->andReturn($status);
        $collectionStatusRepository->shouldReceive('save')
            ->once();

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->andReturn($talent);

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
        $videoLinkRepository->shouldReceive('findByResourceWithMaxDisplayOrder')
            ->once()
            ->andReturn(null);
        $videoLinkRepository->shouldReceive('save')
            ->once();

        $videoLinkFactory = Mockery::mock(VideoLinkFactoryInterface::class);
        $videoLinkFactory->shouldReceive('create')
            ->once()
            ->andReturn($this->createDummyVideoLink($resourceId, 1));

        $this->app->instance(VideoLinkCollectionStatusRepositoryInterface::class, $collectionStatusRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(YouTubeSearchServiceInterface::class, $youtubeSearchService);
        $this->app->instance(VideoLinkRepositoryInterface::class, $videoLinkRepository);
        $this->app->instance(VideoLinkFactoryInterface::class, $videoLinkFactory);
        $this->app->instance(GroupRepositoryInterface::class, Mockery::mock(GroupRepositoryInterface::class));
        $this->app->instance(SongRepositoryInterface::class, Mockery::mock(SongRepositoryInterface::class));

        $useCase = $this->app->make(CollectVideoLinksInterface::class);
        $output = new CollectVideoLinksOutput();
        $useCase->process($output);

        $this->assertTrue($output->processed);
        $this->assertSame(ResourceType::TALENT, $output->resourceType);
        $this->assertSame($resourceId, (string) $output->resourceIdentifier);
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
            new ResourceIdentifier($resourceId),
            null,
            new DateTimeImmutable(),
        );

        $talent = $this->createDummyTalent($resourceId, '채영');

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
            ),
            new YouTubeVideoInfo(
                videoId: 'new',
                title: 'New Video',
                url: $newUrl,
                thumbnailUrl: 'https://i.ytimg.com/vi/new/hqdefault.jpg',
                videoUsage: VideoUsage::YOUTUBE_AUTO_LIKE_COUNT,
                publishedAt: new DateTimeImmutable(),
            ),
        ];

        $existingVideoLink = new VideoLink(
            new VideoLinkIdentifier(StrTestHelper::generateUuid()),
            ResourceType::TALENT,
            new ResourceIdentifier($resourceId),
            new ExternalContentLink($existingUrl),
            VideoUsage::MUSIC_VIDEO,
            'Existing Video',
            null,
            null,
            1,
            new DateTimeImmutable(),
        );

        $collectionStatusRepository = Mockery::mock(VideoLinkCollectionStatusRepositoryInterface::class);
        $collectionStatusRepository->shouldReceive('findNextTargetResource')
            ->once()
            ->andReturn($status);
        $collectionStatusRepository->shouldReceive('save')
            ->once();

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->andReturn($talent);

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
                Mockery::on(static fn (ResourceIdentifier $id): bool => (string) $id === $resourceId),
                [$existingUrl, $newUrl],
            )
            ->andReturn([$existingVideoLink]);
        $videoLinkRepository->shouldReceive('findByResourceWithMaxDisplayOrder')
            ->once()
            ->andReturn(null);
        $videoLinkRepository->shouldReceive('save')
            ->once();

        $videoLinkFactory = Mockery::mock(VideoLinkFactoryInterface::class);
        $videoLinkFactory->shouldReceive('create')
            ->once()
            ->with(
                ResourceType::TALENT,
                Mockery::on(static fn (ResourceIdentifier $id): bool => (string) $id === $resourceId),
                Mockery::on(static fn (ExternalContentLink $url): bool => (string) $url === $newUrl),
                VideoUsage::YOUTUBE_AUTO_LIKE_COUNT,
                'New Video',
                1,
            )
            ->andReturn($this->createDummyVideoLink($resourceId, 1));

        $this->app->instance(VideoLinkCollectionStatusRepositoryInterface::class, $collectionStatusRepository);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(YouTubeSearchServiceInterface::class, $youtubeSearchService);
        $this->app->instance(VideoLinkRepositoryInterface::class, $videoLinkRepository);
        $this->app->instance(VideoLinkFactoryInterface::class, $videoLinkFactory);
        $this->app->instance(GroupRepositoryInterface::class, Mockery::mock(GroupRepositoryInterface::class));
        $this->app->instance(SongRepositoryInterface::class, Mockery::mock(SongRepositoryInterface::class));

        $useCase = $this->app->make(CollectVideoLinksInterface::class);
        $output = new CollectVideoLinksOutput();
        $useCase->process($output);

        $this->assertTrue($output->processed);
        $this->assertSame(ResourceType::TALENT, $output->resourceType);
        $this->assertSame(2, $output->collectedCount);
    }

    private function createDummyTalent(string $resourceId, string $name): Talent
    {
        return new Talent(
            new TalentIdentifier($resourceId),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug('chaeyoung'),
            Language::KOREAN,
            new TalentName($name),
            new RealName('손채영'),
            null,
            [],
            null,
            new Career(''),
            new Version(1),
        );
    }

    private function createDummyGroup(string $resourceId, string $name, string $slug): Group
    {
        return new Group(
            new GroupIdentifier($resourceId),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Slug($slug),
            Language::JAPANESE,
            new GroupName($name),
            $name,
            null,
            new Description(''),
            new Version(1),
        );
    }

    private function createDummySong(string $resourceId, string $name): Song
    {
        return new Song(
            new SongIdentifier($resourceId),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            Language::JAPANESE,
            new SongName($name),
            null,
            null,
            null,
            new Lyricist(''),
            new Composer(''),
            null,
            new Overview(''),
            new Version(1),
        );
    }

    private function createDummyVideoLink(string $resourceId, int $displayOrder = 1): VideoLink
    {
        return new VideoLink(
            new VideoLinkIdentifier(StrTestHelper::generateUuid()),
            ResourceType::TALENT,
            new ResourceIdentifier($resourceId),
            new ExternalContentLink('https://www.youtube.com/watch?v=test'),
            VideoUsage::YOUTUBE_AUTO_VIEW_COUNT,
            'Test Video',
            'https://i.ytimg.com/vi/test/hqdefault.jpg',
            new DateTimeImmutable(),
            $displayOrder,
            new DateTimeImmutable(),
        );
    }
}
