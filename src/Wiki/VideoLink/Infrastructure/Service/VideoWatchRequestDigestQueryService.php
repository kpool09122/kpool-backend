<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Infrastructure\Service;

use Application\Models\Wiki\VideoLink;
use Application\Models\Wiki\VideoWatchRequest;
use Application\Models\Wiki\Wiki;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Infrastructure\Support\ImageUrl;
use Source\Wiki\VideoLink\Application\Service\VideoWatchRequestDigest;
use Source\Wiki\VideoLink\Application\Service\VideoWatchRequestDigestQueryServiceInterface;

final readonly class VideoWatchRequestDigestQueryService implements VideoWatchRequestDigestQueryServiceInterface
{
    public function findByPeriod(DateTimeImmutable $newSince, DateTimeImmutable $recentSince, DateTimeImmutable $before): iterable
    {
        foreach (Wiki::query()->where('resource_type', 'talent')->whereNotNull('owner_account_id')->whereNotNull('published_at')->orderBy('id')->cursor() as $wiki) {
            assert($wiki->owner_account_id !== null);
            $requests = VideoWatchRequest::query()->with([
                'videoLink' => static fn (BelongsTo $query) => $query->withCount([
                    'likes' => static fn (Builder $query) => $query->where('is_official_owner', false),
                ]),
            ])->where('wiki_id', $wiki->id)
                ->where('sent_at', '>=', $newSince)
                ->where('sent_at', '<', $before)->orderBy('sent_at')->orderBy('id')->get();
            if ($requests->isEmpty()) {
                continue;
            }
            $recentRequestCount = VideoWatchRequest::query()->where('wiki_id', $wiki->id)
                ->where('sent_at', '>=', $recentSince)
                ->where('sent_at', '<', $before)->count();
            $videos = [];
            foreach ($requests as $request) {
                $video = $request->videoLink;
                $videos[$request->video_link_id] ??= [
                    'wikiTitle' => $wiki->title ?? $wiki->slug,
                    'videoTitle' => $video?->title,
                    'thumbnailUrl' => $this->thumbnailUrl($video),
                    'likeCount' => $video->likes_count ?? 0,
                    'requestCount' => 0,
                ];
                $videos[$request->video_link_id]['requestCount']++;
            }
            $representativeVideos = array_values(collect($videos)->sortByDesc('likeCount')->take(5)->all());
            yield new VideoWatchRequestDigest(
                new AccountIdentifier($wiki->owner_account_id),
                Language::from($wiki->language),
                $wiki->slug,
                $representativeVideos,
                $recentRequestCount,
                $requests->count(),
            );
        }
    }

    private function thumbnailUrl(?VideoLink $video): ?string
    {
        if ($video === null) {
            return null;
        }
        if ($video->thumbnail_path !== null) {
            return ImageUrl::fromPath($video->thumbnail_path);
        }
        $url = $video->thumbnail_url;
        if ($url === null) {
            return null;
        }
        $parts = parse_url($url);
        if ($parts === false || ($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
            return null;
        }
        if (in_array($parts['host'] ?? '', ['i.ytimg.com', 'img.youtube.com'], true)) {
            return $url;
        }
        if (($parts['host'] ?? '') === 'images.k-pool.app' && str_starts_with($parts['path'] ?? '', '/images/') && ! isset($parts['query']) && ! isset($parts['fragment'])) {
            return $url;
        }

        return null;
    }
}
