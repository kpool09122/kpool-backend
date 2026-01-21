<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLinkAutoCollection\Infrastructure\Service;

use Application\Http\Client\YouTubeClient;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Source\Wiki\VideoLinkAutoCollection\Domain\Service\YouTubeSearchServiceInterface;
use Source\Wiki\VideoLinkAutoCollection\Domain\ValueObject\YouTubeVideoInfo;

class YouTubeSearchService implements YouTubeSearchServiceInterface
{
    private const int MAX_RESULTS_PER_SEARCH = 30;

    private const int MIN_LIKE_COUNT = 10;

    private const float MIN_LIKE_RATE = 0.01;

    private const int MIN_VIEW_COUNT_FOR_LIKE_RATE = 10000;

    private const int TOP_COUNT = 3;

    public function __construct(
        private readonly YouTubeClient $youTubeClient,
    ) {
    }

    /**
     * @return YouTubeVideoInfo[]
     */
    public function searchVideos(string $keyword): array
    {
        if (! $this->youTubeClient->isConfigured()) {
            Log::warning('YouTube API key is not configured');

            return [];
        }

        $viewCountVideoIds = $this->youTubeClient->searchVideoIds($keyword, 'viewCount', self::MAX_RESULTS_PER_SEARCH);
        $relevanceVideoIds = $this->youTubeClient->searchVideoIds($keyword, 'relevance', self::MAX_RESULTS_PER_SEARCH);
        $recentVideoIds = $this->youTubeClient->searchRecentVideoIds(
            $keyword,
            self::MAX_RESULTS_PER_SEARCH,
            CarbonImmutable::now()->subMonth(),
        );

        $allVideoIds = array_unique(array_merge($viewCountVideoIds, $relevanceVideoIds, $recentVideoIds));

        if (empty($allVideoIds)) {
            return [];
        }

        $videoDetails = $this->youTubeClient->getVideoDetails($allVideoIds);

        $viewCountVideos = $this->selectTopVideos($videoDetails, $viewCountVideoIds, 'viewCount', VideoUsage::YOUTUBE_AUTO_VIEW_COUNT, false);
        $likeRateVideos = $this->selectTopVideos($videoDetails, $relevanceVideoIds, 'likeRate', VideoUsage::YOUTUBE_AUTO_LIKE_COUNT, true);
        $recentVideos = $this->selectTopVideos($videoDetails, $recentVideoIds, 'viewCount', VideoUsage::YOUTUBE_AUTO_RECENT_POPULAR, false);

        return $this->deduplicateVideos([...$viewCountVideos, ...$likeRateVideos, ...$recentVideos]);
    }

    /**
     * @param array<string, array{id: string, title: string, publishedAt: string, thumbnailUrl: string, viewCount: int, likeCount: int}> $videoDetails
     * @param string[] $videoIds
     * @return YouTubeVideoInfo[]
     */
    private function selectTopVideos(array $videoDetails, array $videoIds, string $sortBy, VideoUsage $usage, bool $isLikeRateCategory): array
    {
        $candidates = [];

        foreach ($videoIds as $videoId) {
            if (! isset($videoDetails[$videoId])) {
                continue;
            }

            $video = $videoDetails[$videoId];

            if (! $this->isValidVideo($video, $isLikeRateCategory)) {
                continue;
            }

            $candidates[] = $video;
        }

        if ($sortBy === 'likeRate') {
            usort($candidates, static function (array $a, array $b): int {
                $rateA = $a['viewCount'] > 0 ? $a['likeCount'] / $a['viewCount'] : 0;
                $rateB = $b['viewCount'] > 0 ? $b['likeCount'] / $b['viewCount'] : 0;

                return $rateB <=> $rateA;
            });
        } else {
            usort($candidates, static fn (array $a, array $b): int => $b['viewCount'] <=> $a['viewCount']);
        }

        $topVideos = array_slice($candidates, 0, self::TOP_COUNT);

        return array_map(
            static fn (array $video): YouTubeVideoInfo => new YouTubeVideoInfo(
                videoId: $video['id'],
                title: $video['title'],
                url: "https://www.youtube.com/watch?v={$video['id']}",
                thumbnailUrl: $video['thumbnailUrl'],
                videoUsage: $usage,
                publishedAt: CarbonImmutable::parse($video['publishedAt'])->toDateTimeImmutable(),
            ),
            $topVideos,
        );
    }

    /**
     * @param  array{viewCount: int, likeCount: int}  $video
     */
    private function isValidVideo(array $video, bool $isLikeRateCategory): bool
    {
        $viewCount = $video['viewCount'];
        $likeCount = $video['likeCount'];

        if ($likeCount < self::MIN_LIKE_COUNT) {
            return false;
        }

        if ($viewCount > 0 && ($likeCount / $viewCount) < self::MIN_LIKE_RATE) {
            return false;
        }

        if ($isLikeRateCategory && $viewCount < self::MIN_VIEW_COUNT_FOR_LIKE_RATE) {
            return false;
        }

        return true;
    }

    /**
     * @param  YouTubeVideoInfo[]  $videos
     * @return YouTubeVideoInfo[]
     */
    private function deduplicateVideos(array $videos): array
    {
        $seen = [];
        $result = [];

        foreach ($videos as $video) {
            if (! isset($seen[$video->videoId()])) {
                $seen[$video->videoId()] = true;
                $result[] = $video;
            }
        }

        return $result;
    }
}
