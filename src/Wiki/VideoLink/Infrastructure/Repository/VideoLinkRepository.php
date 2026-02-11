<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Infrastructure\Repository;

use Application\Models\Wiki\VideoLink as VideoLinkModel;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLink\Domain\Entity\VideoLink;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkRepositoryInterface;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

final class VideoLinkRepository implements VideoLinkRepositoryInterface
{
    public function findById(VideoLinkIdentifier $identifier): ?VideoLink
    {
        $model = VideoLinkModel::query()
            ->where('id', (string) $identifier)
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    /**
     * @return VideoLink[]
     */
    public function findByResource(ResourceType $resourceType, WikiIdentifier $wikiIdentifier): array
    {
        $models = VideoLinkModel::query()
            ->where('resource_type', $resourceType->value)
            ->where('wiki_id', (string) $wikiIdentifier)
            ->orderBy('display_order')
            ->get();

        return $models->map(fn (VideoLinkModel $model) => $this->toEntity($model))->toArray();
    }

    public function save(VideoLink $videoLink): void
    {
        VideoLinkModel::query()->updateOrCreate(
            ['id' => (string) $videoLink->videoLinkIdentifier()],
            [
                'resource_type' => $videoLink->resourceType()->value,
                'wiki_id' => (string) $videoLink->wikiIdentifier(),
                'url' => (string) $videoLink->url(),
                'video_usage' => $videoLink->videoUsage()->value,
                'title' => $videoLink->title(),
                'thumbnail_url' => $videoLink->thumbnailUrl(),
                'published_at' => $videoLink->publishedAt(),
                'display_order' => $videoLink->displayOrder(),
                'created_at' => $videoLink->createdAt(),
            ],
        );
    }

    public function delete(VideoLinkIdentifier $identifier): void
    {
        VideoLinkModel::query()
            ->where('id', (string) $identifier)
            ->delete();
    }

    public function deleteByResource(ResourceType $resourceType, WikiIdentifier $resourceIdentifier): void
    {
        VideoLinkModel::query()
            ->where('resource_type', $resourceType->value)
            ->where('wiki_id', (string) $resourceIdentifier)
            ->delete();
    }

    public function deleteAutoCollectedByResource(ResourceType $resourceType, WikiIdentifier $resourceIdentifier): void
    {
        $autoCollectedUsages = [
            VideoUsage::YOUTUBE_AUTO_VIEW_COUNT->value,
            VideoUsage::YOUTUBE_AUTO_LIKE_COUNT->value,
            VideoUsage::YOUTUBE_AUTO_RECENT_POPULAR->value,
        ];

        VideoLinkModel::query()
            ->where('resource_type', $resourceType->value)
            ->where('wiki_id', (string) $resourceIdentifier)
            ->whereIn('video_usage', $autoCollectedUsages)
            ->delete();
    }

    public function findByResourceWithMaxDisplayOrder(ResourceType $resourceType, WikiIdentifier $resourceIdentifier): ?VideoLink
    {
        $model = VideoLinkModel::query()
            ->where('resource_type', $resourceType->value)
            ->where('wiki_id', (string) $resourceIdentifier)
            ->orderByDesc('display_order')
            ->first();

        return $model !== null ? $this->toEntity($model) : null;
    }

    /**
     * @param string[] $urls
     *
     * @return VideoLink[]
     */
    public function findByResourceAndUrls(ResourceType $resourceType, WikiIdentifier $resourceIdentifier, array $urls): array
    {
        if ($urls === []) {
            return [];
        }

        $models = VideoLinkModel::query()
            ->where('resource_type', $resourceType->value)
            ->where('wiki_id', (string) $resourceIdentifier)
            ->whereIn('url', $urls)
            ->get();

        return $models->map(fn (VideoLinkModel $model) => $this->toEntity($model))->toArray();
    }

    private function toEntity(VideoLinkModel $model): VideoLink
    {
        return new VideoLink(
            new VideoLinkIdentifier($model->id),
            ResourceType::from($model->resource_type),
            new WikiIdentifier($model->wiki_id),
            new ExternalContentLink($model->url),
            VideoUsage::from($model->video_usage),
            $model->title,
            $model->thumbnail_url,
            $model->published_at?->toDateTimeImmutable(),
            $model->display_order,
            $model->created_at->toDateTimeImmutable(),
        );
    }
}
