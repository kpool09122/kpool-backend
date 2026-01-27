<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLinkAutoCollection\Infrastructure\Service;

use Application\Http\Client\YouTubeClient\GetVideoDetails\GetVideoDetailsResponse;
use Application\Http\Client\YouTubeClient\SearchRecentVideoIds\SearchRecentVideoIdsResponse;
use Application\Http\Client\YouTubeClient\SearchVideoIds\SearchVideoIdsResponse;
use Application\Http\Client\YouTubeClient\YouTubeClient;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Source\Wiki\VideoLinkAutoCollection\Domain\Service\YouTubeSearchServiceInterface;
use Tests\TestCase;

class YouTubeSearchServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * 正常系: APIキーが空の場合、空配列を返すこと.
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function testSearchVideosReturnsEmptyWhenApiKeyIsEmpty(): void
    {
        $client = Mockery::mock(YouTubeClient::class);
        $client->shouldReceive('isConfigured')
            ->once()
            ->andReturn(false);

        $this->app->instance(YouTubeClient::class, $client);

        $service = $this->app->make(YouTubeSearchServiceInterface::class);
        $result = $service->searchVideos('test keyword');

        $this->assertEmpty($result);
    }

    /**
     * 正常系: 検索結果が空の場合、空配列を返すこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testSearchVideosReturnsEmptyWhenNoVideosFound(): void
    {
        $client = $this->createMockYouTubeClient(
            viewCountVideoIds: [],
            relevanceVideoIds: [],
            recentVideoIds: [],
            videoDetails: [],
        );

        $this->app->instance(YouTubeClient::class, $client);

        $service = $this->app->make(YouTubeSearchServiceInterface::class);
        $result = $service->searchVideos('test keyword');

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
        $client = $this->createMockYouTubeClient(
            viewCountVideoIds: ['video1', 'video2', 'video3'],
            relevanceVideoIds: ['video4', 'video5'],
            recentVideoIds: ['video6'],
            videoDetails: [
                'video1' => $this->createVideoDetail('video1', 'Video 1 Title', 1000000, 50000),
                'video2' => $this->createVideoDetail('video2', 'Video 2 Title', 500000, 25000),
                'video3' => $this->createVideoDetail('video3', 'Video 3 Title', 250000, 12500),
                'video4' => $this->createVideoDetail('video4', 'Video 4 Title', 100000, 10000),
                'video5' => $this->createVideoDetail('video5', 'Video 5 Title', 80000, 8000),
                'video6' => $this->createVideoDetail('video6', 'Video 6 Title', 50000, 5000),
            ],
        );

        $this->app->instance(YouTubeClient::class, $client);

        $service = $this->app->make(YouTubeSearchServiceInterface::class);
        $result = $service->searchVideos('test keyword');

        $this->assertNotEmpty($result);
        $this->assertLessThanOrEqual(9, count($result));

        foreach ($result as $video) {
            $this->assertTrue($video->videoUsage()->isAutoCollected());
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
        $client = $this->createMockYouTubeClient(
            viewCountVideoIds: ['good_video', 'bad_video'],
            relevanceVideoIds: [],
            recentVideoIds: [],
            videoDetails: [
                'good_video' => $this->createVideoDetail('good_video', 'Good Video', 100000, 5000),
                'bad_video' => $this->createVideoDetail('bad_video', 'Bad Video (Low Like Rate)', 100000, 100),
            ],
        );

        $this->app->instance(YouTubeClient::class, $client);

        $service = $this->app->make(YouTubeSearchServiceInterface::class);
        $result = $service->searchVideos('test keyword');

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
        $client = $this->createMockYouTubeClient(
            viewCountVideoIds: ['same_video'],
            relevanceVideoIds: ['same_video'],
            recentVideoIds: ['same_video'],
            videoDetails: [
                'same_video' => $this->createVideoDetail('same_video', 'Same Video', 100000, 5000),
            ],
        );

        $this->app->instance(YouTubeClient::class, $client);

        $service = $this->app->make(YouTubeSearchServiceInterface::class);
        $result = $service->searchVideos('test keyword');

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
        $client = $this->createMockYouTubeClient(
            viewCountVideoIds: ['view_video'],
            relevanceVideoIds: ['like_video'],
            recentVideoIds: ['recent_video'],
            videoDetails: [
                'view_video' => $this->createVideoDetail('view_video', 'View Video', 1000000, 50000),
                'like_video' => $this->createVideoDetail('like_video', 'Like Video', 50000, 10000),
                'recent_video' => $this->createVideoDetail('recent_video', 'Recent Video', 100000, 5000),
            ],
        );

        $this->app->instance(YouTubeClient::class, $client);

        $service = $this->app->make(YouTubeSearchServiceInterface::class);
        $result = $service->searchVideos('test keyword');

        $videoUsages = array_map(static fn ($video) => $video->videoUsage(), $result);

        $autoCollectedUsages = [
            VideoUsage::YOUTUBE_AUTO_VIEW_COUNT,
            VideoUsage::YOUTUBE_AUTO_LIKE_COUNT,
            VideoUsage::YOUTUBE_AUTO_RECENT_POPULAR,
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
        $client = $this->createMockYouTubeClient(
            viewCountVideoIds: ['good_video', 'low_like_video'],
            relevanceVideoIds: [],
            recentVideoIds: [],
            videoDetails: [
                'good_video' => $this->createVideoDetail('good_video', 'Good Video', 1000, 100),
                'low_like_video' => $this->createVideoDetail('low_like_video', 'Low Like Video', 1000, 9),
            ],
        );

        $this->app->instance(YouTubeClient::class, $client);

        $service = $this->app->make(YouTubeSearchServiceInterface::class);
        $result = $service->searchVideos('test keyword');

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
        $client = $this->createMockYouTubeClient(
            viewCountVideoIds: [],
            relevanceVideoIds: ['high_view_video', 'low_view_video'],
            recentVideoIds: [],
            videoDetails: [
                'high_view_video' => $this->createVideoDetail('high_view_video', 'High View Video', 10000, 1000),
                'low_view_video' => $this->createVideoDetail('low_view_video', 'Low View Video', 9999, 1000),
            ],
        );

        $this->app->instance(YouTubeClient::class, $client);

        $service = $this->app->make(YouTubeSearchServiceInterface::class);
        $result = $service->searchVideos('test keyword');

        $videoIds = array_map(static fn ($video) => $video->videoId(), $result);

        $this->assertContains('high_view_video', $videoIds);
        $this->assertNotContains('low_view_video', $videoIds);
    }

    /**
     * 正常系: 詳細情報が取得できない動画はスキップされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testSearchVideosSkipsVideosWithoutDetails(): void
    {
        $client = $this->createMockYouTubeClient(
            viewCountVideoIds: ['video_with_details', 'video_without_details'],
            relevanceVideoIds: [],
            recentVideoIds: [],
            videoDetails: [
                'video_with_details' => $this->createVideoDetail('video_with_details', 'Video With Details', 100000, 5000),
            ],
        );

        $this->app->instance(YouTubeClient::class, $client);

        $service = $this->app->make(YouTubeSearchServiceInterface::class);
        $result = $service->searchVideos('test keyword');

        $videoIds = array_map(static fn ($video) => $video->videoId(), $result);

        $this->assertContains('video_with_details', $videoIds);
        $this->assertNotContains('video_without_details', $videoIds);
    }

    /**
     * @param string[] $viewCountVideoIds
     * @param string[] $relevanceVideoIds
     * @param string[] $recentVideoIds
     * @param array<string, array{id: string, title: string, publishedAt: string, thumbnailUrl: string, viewCount: int, likeCount: int}> $videoDetails
     * @return MockInterface&YouTubeClient
     */
    private function createMockYouTubeClient(
        array $viewCountVideoIds,
        array $relevanceVideoIds,
        array $recentVideoIds,
        array $videoDetails,
    ): MockInterface {
        /** @var MockInterface&YouTubeClient $client */
        $client = Mockery::mock(YouTubeClient::class);

        $client->shouldReceive('isConfigured')
            ->andReturn(true);

        $client->shouldReceive('searchVideoIds')
            ->withArgs(fn ($request) => $request->keyword() === 'test keyword' && $request->order() === 'viewCount')
            ->andReturn(new SearchVideoIdsResponse($this->createSearchResponse($viewCountVideoIds)));

        $client->shouldReceive('searchVideoIds')
            ->withArgs(fn ($request) => $request->keyword() === 'test keyword' && $request->order() === 'relevance')
            ->andReturn(new SearchVideoIdsResponse($this->createSearchResponse($relevanceVideoIds)));

        $client->shouldReceive('searchRecentVideoIds')
            ->withArgs(fn ($request) => $request->keyword() === 'test keyword')
            ->andReturn(new SearchRecentVideoIdsResponse($this->createSearchResponse($recentVideoIds)));

        $client->shouldReceive('getVideoDetails')
            ->andReturn(new GetVideoDetailsResponse($this->createVideoDetailsResponse($videoDetails)));

        return $client;
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
