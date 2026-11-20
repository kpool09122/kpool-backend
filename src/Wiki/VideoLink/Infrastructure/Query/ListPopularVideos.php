<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Infrastructure\Query;

use Application\Models\Wiki\VideoLink as VideoLinkModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Source\Shared\Infrastructure\Support\ImageUrl;
use Source\Wiki\Shared\Domain\Service\VideoUrlBuilderInterface;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListPopularVideos\ListPopularVideosInterface;
use Source\Wiki\VideoLink\Application\UseCase\Query\PopularVideoReadModel;

readonly class ListPopularVideos implements ListPopularVideosInterface
{
    public function __construct(private VideoUrlBuilderInterface $videoUrlBuilder)
    {
    }

    public function process(): array
    {
        return array_values(VideoLinkModel::query()
            ->whereHas('wiki', static fn (Builder $query) => $query
                ->whereNotNull('published_at')
                ->whereColumn('wikis.resource_type', 'wiki_video_links.resource_type'))
            ->whereIn('resource_type', [ResourceType::GROUP->value, ResourceType::TALENT->value, ResourceType::SONG->value, ResourceType::AGENCY->value])
            ->where(static fn (Builder $query) => $query
                ->whereIn('resource_type', [ResourceType::GROUP->value, ResourceType::TALENT->value, ResourceType::SONG->value])
                ->orWhereNotNull('creator_principal_id'))
            ->withCount(['likes' => static fn (Builder|HasMany $query) => $query->where('is_official_owner', false)])
            ->with('wiki:id,language,slug')
            ->orderByDesc('likes_count')->orderByDesc('created_at')->orderBy('id')
            ->lazy(100)
            ->unique(function (VideoLinkModel $video): string {
                $url = $this->videoUrlBuilder->build($video->url);

                return $url->provider() . ':' . $url->externalVideoId();
            })
            ->take(20)
            ->map(function (VideoLinkModel $video): PopularVideoReadModel {
                $wiki = $video->wiki;
                assert($wiki !== null);

                return new PopularVideoReadModel(
                    $video->id,
                    $this->videoUrlBuilder->build($video->url),
                    $video->title,
                    $video->thumbnail_path !== null ? ImageUrl::fromPath($video->thumbnail_path) : $video->thumbnail_url,
                    $video->duration_seconds,
                    $video->likes_count,
                    $wiki->language,
                    $wiki->slug,
                );
            })->all());
    }
}
