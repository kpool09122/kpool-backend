<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Infrastructure\Service;

use Application\Http\Client\YouTubeClient\YouTubeClient;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Mockery\MockInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Source\Wiki\VideoLink\Domain\Exception\VideoSearchFailedException;
use Source\Wiki\VideoLink\Domain\Service\YouTubeSearchServiceInterface;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Tests\Helper\YouTubeClientTestHelper;
use Tests\TestCase;

class YouTubeSearchServiceTest extends TestCase
{
    public function testSearchVideosPreservesOriginalExceptionMessageAndCause(): void
    {
        $exception = new RuntimeException('Video response could not be parsed.');
        /** @var ClientInterface&MockInterface $http */
        $http = Mockery::mock(ClientInterface::class);
        $http->shouldReceive('sendRequest')->once()->andThrow($exception);
        $this->app()->instance(YouTubeClient::class, YouTubeClientTestHelper::create($http));

        $service = $this->app()->make(YouTubeSearchServiceInterface::class);

        try {
            $service->searchVideos('TWICE', ['en' => ['TWICE']]);
            self::fail('Expected video search to fail.');
        } catch (VideoSearchFailedException $actual) {
            self::assertSame($exception->getMessage(), $actual->getMessage());
            self::assertSame($exception, $actual->getPrevious());
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * 正常系: APIキーが空の場合、失敗として扱うこと.
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function testSearchVideosFailsWhenApiKeyIsEmpty(): void
    {
        /** @var ClientInterface&MockInterface $http */
        $http = Mockery::mock(ClientInterface::class);
        $http->shouldNotReceive('sendRequest');
        $client = YouTubeClientTestHelper::create($http, '');

        $this->app()->instance(YouTubeClient::class, $client);

        $service = $this->app()->make(YouTubeSearchServiceInterface::class);
        $this->expectException(VideoSearchFailedException::class);
        $service->searchVideos('test keyword', ['en' => ['Video']]);
    }

    /**
     * 正常系: 検索結果が空の場合、空配列を返すこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testSearchVideosReturnsEmptyWhenNoVideosFound(): void
    {
        $client = $this->createYouTubeClient(
            viewCountVideoIds: [],
            relevanceVideoIds: [],
            videoDetails: [],
        );

        $this->app()->instance(YouTubeClient::class, $client);

        $service = $this->app()->make(YouTubeSearchServiceInterface::class);
        $result = $service->searchVideos('test keyword', ['en' => ['Video']]);

        $this->assertEmpty($result);
    }

    /**
     * 正常系: 動画を検索して結果を返すこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testSearchVideosReturnsVideos(): void
    {
        $client = $this->createYouTubeClient(
            viewCountVideoIds: ['video1', 'video2', 'video3'],
            relevanceVideoIds: ['video4', 'video5'],
            videoDetails: [
                'video1' => $this->createVideoDetail('video1', 'Video 1 Title', 1000000, 50000),
                'video2' => $this->createVideoDetail('video2', 'Video 2 Title', 500000, 25000),
                'video3' => $this->createVideoDetail('video3', 'Video 3 Title', 250000, 12500),
                'video4' => $this->createVideoDetail('video4', 'Video 4 Title', 100000, 10000),
                'video5' => $this->createVideoDetail('video5', 'Video 5 Title', 80000, 8000),
            ],
        );

        $this->app()->instance(YouTubeClient::class, $client);

        $service = $this->app()->make(YouTubeSearchServiceInterface::class);
        $result = $service->searchVideos('test keyword', ['en' => ['Video']]);

        $this->assertNotEmpty($result);
        $this->assertLessThanOrEqual(10, count($result));

        foreach ($result as $video) {
            $this->assertTrue($video->videoUsage()->isAutoCollected());
            $this->assertSame(253, $video->durationSeconds());
        }
    }

    /**
     * 正常系: 低評価率の動画は除外されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testSearchVideosFiltersLowLikeRateVideos(): void
    {
        $client = $this->createYouTubeClient(
            viewCountVideoIds: ['good_video', 'bad_video'],
            relevanceVideoIds: [],
            videoDetails: [
                'good_video' => $this->createVideoDetail('good_video', 'Good Video', 100000, 5000),
                'bad_video' => $this->createVideoDetail('bad_video', 'Bad Video (Low Like Rate)', 100000, 100),
            ],
        );

        $this->app()->instance(YouTubeClient::class, $client);

        $service = $this->app()->make(YouTubeSearchServiceInterface::class);
        $result = $service->searchVideos('test keyword', ['en' => ['Video']]);

        $videoIds = array_map(static fn ($video) => $video->videoId(), $result);

        $this->assertContains('good_video', $videoIds);
        $this->assertNotContains('bad_video', $videoIds);
    }

    /**
     * 正常系: 動画が重複除去されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testSearchVideosDeduplicatesVideos(): void
    {
        $client = $this->createYouTubeClient(
            viewCountVideoIds: ['same_video'],
            relevanceVideoIds: ['same_video'],
            videoDetails: [
                'same_video' => $this->createVideoDetail('same_video', 'Same Video', 100000, 5000),
            ],
        );

        $this->app()->instance(YouTubeClient::class, $client);

        $service = $this->app()->make(YouTubeSearchServiceInterface::class);
        $result = $service->searchVideos('test keyword', ['en' => ['Video']]);

        $videoIds = array_map(static fn ($video) => $video->videoId(), $result);
        $uniqueVideoIds = array_unique($videoIds);

        $this->assertCount(count($uniqueVideoIds), $videoIds);
    }

    /**
     * 正常系: 各カテゴリのVideoUsageが正しく設定されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testSearchVideosAssignsCorrectVideoUsage(): void
    {
        $client = $this->createYouTubeClient(
            viewCountVideoIds: ['view_video'],
            relevanceVideoIds: ['like_video'],
            videoDetails: [
                'view_video' => $this->createVideoDetail('view_video', 'View Video', 1000000, 50000),
                'like_video' => $this->createVideoDetail('like_video', 'Like Video', 50000, 10000),
            ],
        );

        $this->app()->instance(YouTubeClient::class, $client);

        $service = $this->app()->make(YouTubeSearchServiceInterface::class);
        $result = $service->searchVideos('test keyword', ['en' => ['Video']]);

        $videoUsages = array_map(static fn ($video) => $video->videoUsage(), $result);

        $autoCollectedUsages = [
            VideoUsage::YOUTUBE_AUTO_VIEW_COUNT,
            VideoUsage::YOUTUBE_AUTO_LIKE_COUNT,
        ];

        foreach ($videoUsages as $usage) {
            $this->assertContains($usage, $autoCollectedUsages);
        }
    }

    /**
     * 正常系: いいね数が最小値未満の動画は除外されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testSearchVideosFiltersLowLikeCountVideos(): void
    {
        $client = $this->createYouTubeClient(
            viewCountVideoIds: ['good_video', 'low_like_video'],
            relevanceVideoIds: [],
            videoDetails: [
                'good_video' => $this->createVideoDetail('good_video', 'Good Video', 1000, 100),
                'low_like_video' => $this->createVideoDetail('low_like_video', 'Low Like Video', 1000, 9),
            ],
        );

        $this->app()->instance(YouTubeClient::class, $client);

        $service = $this->app()->make(YouTubeSearchServiceInterface::class);
        $result = $service->searchVideos('test keyword', ['en' => ['Video']]);

        $videoIds = array_map(static fn ($video) => $video->videoId(), $result);

        $this->assertContains('good_video', $videoIds);
        $this->assertNotContains('low_like_video', $videoIds);
    }

    /**
     * 正常系: いいね率カテゴリで再生数が最小値未満の動画は除外されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testSearchVideosFiltersLowViewCountForLikeRateCategory(): void
    {
        $client = $this->createYouTubeClient(
            viewCountVideoIds: [],
            relevanceVideoIds: ['high_view_video', 'low_view_video'],
            videoDetails: [
                'high_view_video' => $this->createVideoDetail('high_view_video', 'High View Video', 10000, 1000),
                'low_view_video' => $this->createVideoDetail('low_view_video', 'Low View Video', 9999, 1000),
            ],
        );

        $this->app()->instance(YouTubeClient::class, $client);

        $service = $this->app()->make(YouTubeSearchServiceInterface::class);
        $result = $service->searchVideos('test keyword', ['en' => ['Video']]);

        $videoIds = array_map(static fn ($video) => $video->videoId(), $result);

        $this->assertContains('high_view_video', $videoIds);
        $this->assertNotContains('low_view_video', $videoIds);
    }

    /**
     * 異常系: 詳細情報が一部欠落しても収集全体を失敗させること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testSearchVideosFailsWhenAnyDetailIsMissing(): void
    {
        $client = $this->createYouTubeClient(
            viewCountVideoIds: ['video_with_details', 'video_without_details'],
            relevanceVideoIds: [],
            videoDetails: [
                'video_with_details' => $this->createVideoDetail('video_with_details', 'Video With Details', 100000, 5000),
            ],
        );

        $this->app()->instance(YouTubeClient::class, $client);

        $service = $this->app()->make(YouTubeSearchServiceInterface::class);
        $this->expectException(VideoSearchFailedException::class);
        $service->searchVideos('test keyword', ['en' => ['Video']]);
    }

    public function testFiltersTitlesBeforeSelectingInBothCategories(): void
    {
        $ids = ['unrelated1', 'unrelated2', 'unrelated3', 'own', 'translated', 'related'];
        $details = [];
        foreach ($ids as $index => $id) {
            $title = match ($id) {
                'own' => 'TWICE performance',
                'translated' => '트와이스 무대',
                'related' => 'MOMO fancam',
                default => 'Other artists',
            };
            $details[$id] = $this->createVideoDetail($id, $title, 100000 - $index * 1000, 10000 - $index * 200);
        }
        foreach ([0, 1] as $category) {
            $this->app()->instance(YouTubeClient::class, $this->createYouTubeClient(
                $category === 0 ? $ids : [],
                $category === 1 ? $ids : [],
                $details,
            ));
            $service = $this->app()->make(YouTubeSearchServiceInterface::class);
            $result = $service->searchVideos('TWICE k-pop', ['en' => ['TWICE', 'MOMO'], 'ko' => ['트와이스']]);
            self::assertEqualsCanonicalizing(['own', 'translated', 'related'], array_map(static fn ($video) => $video->videoId(), $result));
            $this->app()->forgetInstance(YouTubeSearchServiceInterface::class);
        }
    }

    public function testSelectsFivePerAxisAfterFilteringFiftyCandidatesEach(): void
    {
        $viewIds = [];
        $likeIds = [];
        $details = [];
        foreach (range(1, 50) as $index) {
            $viewIds[] = 'view' . $index;
            $likeIds[] = 'like' . $index;
            foreach (['view', 'like'] as $prefix) {
                $id = $prefix . $index;
                $details[$id] = $this->createVideoDetail(
                    $id,
                    $index >= 45 ? 'TWICE performance' : 'Other artists',
                    100000 - $index * 1000,
                    10000,
                );
            }
        }
        $this->app()->instance(YouTubeClient::class, $this->createYouTubeClient($viewIds, $likeIds, $details));

        $result = $this->app()->make(YouTubeSearchServiceInterface::class)->searchVideos('TWICE k-pop', ['en' => ['TWICE']]);

        self::assertSame(
            ['view45', 'view46', 'view47', 'view48', 'view49', 'like50', 'like49', 'like48', 'like47', 'like46'],
            array_map(static fn ($video) => $video->videoId(), $result),
        );
        self::assertSame(
            [...array_fill(0, 5, VideoUsage::YOUTUBE_AUTO_VIEW_COUNT), ...array_fill(0, 5, VideoUsage::YOUTUBE_AUTO_LIKE_COUNT)],
            array_map(static fn ($video) => $video->videoUsage(), $result),
        );
    }

    /**
     * @param string[] $viewCountVideoIds
     * @param string[] $relevanceVideoIds
     * @param array<string, array{id: string, title: string, publishedAt: string, thumbnailUrl: string, viewCount: int, likeCount: int}> $videoDetails
     */
    private function createYouTubeClient(
        array $viewCountVideoIds,
        array $relevanceVideoIds,
        array $videoDetails,
    ): YouTubeClient {
        /** @var ClientInterface&MockInterface $http */
        $http = Mockery::mock(ClientInterface::class);
        foreach (['viewCount' => $viewCountVideoIds, 'relevance' => $relevanceVideoIds] as $order => $ids) {
            $http->shouldReceive('sendRequest')->once()->ordered()
                ->with(Mockery::on(static function (RequestInterface $request) use ($order): bool {
                    parse_str($request->getUri()->getQuery(), $query);

                    return $request->getUri()->getPath() === '/youtube/v3/search'
                        && $query['order'] === $order
                        && $query['maxResults'] === '50'
                        && ! isset($query['publishedAfter']);
                }))
                ->andReturn($this->createSearchResponse($ids));
        }
        $allIds = array_values(array_unique([...$viewCountVideoIds, ...$relevanceVideoIds]));
        foreach (array_chunk($allIds, 50) as $chunk) {
            $http->shouldReceive('sendRequest')->once()->ordered()
                ->with(Mockery::on(static function (RequestInterface $request) use ($chunk): bool {
                    parse_str($request->getUri()->getQuery(), $query);

                    return $request->getUri()->getPath() === '/youtube/v3/videos'
                        && $query['id'] === implode(',', $chunk);
                }))
                ->andReturn($this->createVideoDetailsResponse(array_intersect_key($videoDetails, array_flip($chunk))));
        }

        return YouTubeClientTestHelper::create($http);
    }

    /**
     * @return array{id: string, title: string, publishedAt: string, thumbnailUrl: string, viewCount: int, likeCount: int}
     */
    private function createVideoDetail(string $id, string $title, int $viewCount, int $likeCount): array
    {
        return [
            'id' => $id,
            'title' => $title,
            'publishedAt' => '2024-01-15T10:30:00Z',
            'thumbnailUrl' => "https://i.ytimg.com/vi/$id/hqdefault.jpg",
            'viewCount' => $viewCount,
            'likeCount' => $likeCount,
        ];
    }

    /**
     * @param string[] $videoIds
     */
    private function createSearchResponse(array $videoIds): ResponseInterface
    {
        $items = array_map(
            static fn (string $videoId): array => ['id' => ['videoId' => $videoId]],
            $videoIds,
        );

        return $this->createJsonResponse(['items' => $items]);
    }

    /**
     * @param array<string, array{id: string, title: string, publishedAt: string, thumbnailUrl: string, viewCount: int, likeCount: int}> $videoDetails
     */
    private function createVideoDetailsResponse(array $videoDetails): ResponseInterface
    {
        $items = array_values(array_map(
            static fn (array $detail): array => [
                'id' => $detail['id'],
                'snippet' => [
                    'title' => $detail['title'],
                    'publishedAt' => $detail['publishedAt'],
                    'thumbnails' => [
                        'high' => ['url' => $detail['thumbnailUrl']],
                        'default' => ['url' => $detail['thumbnailUrl']],
                    ],
                ],
                'contentDetails' => ['duration' => 'PT4M13S'],
                'statistics' => [
                    'viewCount' => (string) $detail['viewCount'],
                    'likeCount' => (string) $detail['likeCount'],
                ],
            ],
            $videoDetails,
        ));

        return $this->createJsonResponse(['items' => $items]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createJsonResponse(array $data): ResponseInterface
    {
        $factory = new HttpFactory();
        $json = json_encode($data, JSON_THROW_ON_ERROR);
        $stream = $factory->createStream($json);

        return $factory->createResponse()->withBody($stream);
    }
}
