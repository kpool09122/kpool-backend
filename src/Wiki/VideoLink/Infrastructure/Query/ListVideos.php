<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Infrastructure\Query;

use Application\Models\Wiki\VideoLink as VideoLinkModel;
use Application\Models\Wiki\Wiki as WikiModel;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListVideos\ListVideosInputPort;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListVideos\ListVideosInterface;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListVideos\ListVideosOutputPort;
use Source\Wiki\VideoLink\Application\UseCase\Query\VideoLinkReadModel;
use Source\Wiki\VideoLink\Domain\Service\VideoLinkCollectionPolicy;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;

readonly class ListVideos implements ListVideosInterface
{
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
        $videos = $query->orderByDesc('created_at')->orderBy('id')->get()
            ->map(static fn (VideoLinkModel $video): VideoLinkReadModel => new VideoLinkReadModel(
                $video->id,
                $video->url,
                $video->title,
                $video->thumbnail_url,
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
