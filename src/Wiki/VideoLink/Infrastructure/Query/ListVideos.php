<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Infrastructure\Query;

use Application\Models\Wiki\VideoLink as VideoLinkModel;
use Application\Models\Wiki\Wiki as WikiModel;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListVideos\ListVideosInputPort;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListVideos\ListVideosInterface;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListVideos\ListVideosOutputPort;
use Source\Wiki\VideoLink\Application\UseCase\Query\VideoLinkReadModel;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Source\Wiki\VideoLinkAutoCollection\Domain\Service\VideoLinkCollectionPolicy;
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
        if (! VideoLinkCollectionPolicy::supports($input->resourceType())) {
            $output->output([]);

            return;
        }
        $videos = VideoLinkModel::query()
            ->where('wiki_id', $wiki->id)
            ->where('resource_type', $input->resourceType()->value)
            ->whereIn('video_usage', array_map(static fn (VideoUsage $usage): string => $usage->value, array_filter(VideoUsage::cases(), static fn (VideoUsage $usage): bool => $usage->isAutoCollected())))
            ->orderBy('display_order')->orderBy('id')->get()
            ->map(static fn (VideoLinkModel $video): VideoLinkReadModel => new VideoLinkReadModel(
                $video->id,
                $video->url,
                $video->title,
                $video->thumbnail_url,
                $video->published_at?->toAtomString(),
                $video->display_order,
                $video->video_usage,
            ))->all();
        $output->output(array_values($videos));
    }
}
