<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Infrastructure\Query;

use Application\Models\Wiki\VideoLink as VideoLinkModel;
use Application\Models\Wiki\Wiki as WikiModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Source\Shared\Infrastructure\Support\ImageUrl;
use Source\Wiki\Shared\Domain\Service\VideoUrlBuilderInterface;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListVideos\ListVideosInputPort;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListVideos\ListVideosInterface;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListVideos\ListVideosOutputPort;
use Source\Wiki\VideoLink\Application\UseCase\Query\VideoLinkReadModel;
use Source\Wiki\VideoLink\Domain\Service\VideoLinkCollectionPolicy;
use Source\Wiki\VideoLink\Domain\Service\VideoWatchRequestPolicy;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;

readonly class ListVideos implements ListVideosInterface
{
    public function __construct(private VideoUrlBuilderInterface $videoUrlBuilder)
    {
    }

    /** @throws WikiNotFoundException */
    public function process(ListVideosInputPort $input, ListVideosOutputPort $output): void
    {
        $wiki = WikiModel::query()->select('id')
            ->where('language', $input->language()->value)
            ->where('resource_type', $input->resourceType()->value)
            ->where('slug', (string) $input->slug())
            ->whereNotNull('published_at')->first();
        if ($wiki === null) {
            throw new WikiNotFoundException();
        }
        $query = VideoLinkModel::query()
            ->withCount('likes')
            ->with(['creatorPrincipal:id,identity_id', 'creatorPrincipal.identity:id,identity_name'])
            ->where('wiki_id', $wiki->id)
            ->where('resource_type', $input->resourceType()->value)
            ->when(! VideoLinkCollectionPolicy::supports($input->resourceType()), static fn ($query) => $query->whereIn('video_usage', array_map(static fn (VideoUsage $usage): string => $usage->value, array_filter(VideoUsage::cases(), static fn (VideoUsage $usage): bool => $usage->isManual()))));
        if ($input->minDurationSeconds() !== null) {
            $query->where('duration_seconds', '>=', $input->minDurationSeconds());
        }
        if ($input->maxDurationSeconds() !== null) {
            $query->where('duration_seconds', '<=', $input->maxDurationSeconds());
        }
        $cutoff = VideoWatchRequestPolicy::cutoff(CarbonImmutable::now()->toDateTimeImmutable());
        if ($input->recentWatchRequestsOnly()) {
            $query->whereHas('watchRequests', static fn ($query) => $query->where('sent_at', '>', $cutoff));
        }
        if ($input->sortByWatchRequests()) {
            $query->withCount(['watchRequests as recent_watch_requests_count' => static fn (Builder|HasMany $query) => $query->where('sent_at', '>', $cutoff)])
                ->orderByDesc('recent_watch_requests_count')->orderByDesc('likes_count');
        } else {
            $query->orderByDesc('created_at');
        }
        $videos = $query->orderBy('id')->get()
            ->map(fn (VideoLinkModel $video): VideoLinkReadModel => new VideoLinkReadModel(
                $video->id,
                $this->videoUrlBuilder->build($video->url),
                $video->title,
                $video->thumbnail_path !== null ? ImageUrl::fromPath($video->thumbnail_path) : $video->thumbnail_url,
                $video->published_at?->toAtomString(),
                $video->video_usage,
                $video->duration_seconds,
                VideoUsage::from($video->video_usage)->isAutoCollected() ? null : $video->creator_principal_id,
                VideoUsage::from($video->video_usage)->isAutoCollected() ? null : $video->creatorPrincipal?->identity?->identity_name,
                $video->likes_count,
            ))->all();
        $output->output(array_values($videos));
    }
}
