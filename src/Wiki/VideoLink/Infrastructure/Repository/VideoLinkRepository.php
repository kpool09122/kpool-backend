<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Infrastructure\Repository;

use Application\Models\Wiki\VideoLink as VideoLinkModel;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Shared\Application\Service\VideoThumbnailServiceInterface;
use Source\Wiki\Shared\Domain\ValueObject\DurationSeconds;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\ThumbnailUrl;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoTitle;
use Source\Wiki\VideoLink\Domain\Entity\VideoLink;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkRepositoryInterface;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use UnexpectedValueException;

final class VideoLinkRepository implements VideoLinkRepositoryInterface
{
    public function __construct(private readonly VideoThumbnailServiceInterface $videoThumbnailService)
    {
    }

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
            ->orderByDesc('created_at')->orderBy('id')
            ->get();

        return $models->map(fn (VideoLinkModel $model) => $this->toEntity($model))->all();
    }

    public function save(VideoLink $videoLink): void
    {
        if ($videoLink->thumbnailPath() !== null) {
            $this->videoThumbnailService->track($videoLink->thumbnailPath());
        }
        VideoLinkModel::query()->updateOrCreate(
            ['id' => (string) $videoLink->videoLinkIdentifier()],
            [
                'resource_type' => $videoLink->resourceType()->value,
                'wiki_id' => (string) $videoLink->wikiIdentifier(),
                'url' => (string) $videoLink->url(),
                'thumbnail_path' => $videoLink->thumbnailPath() === null ? null : (string) $videoLink->thumbnailPath(),
                'video_usage' => $videoLink->videoUsage()->value,
                'title' => (string) $videoLink->title(),
                'thumbnail_url' => $videoLink->thumbnailUrl() !== null ? (string) $videoLink->thumbnailUrl() : null,
                'published_at' => $videoLink->publishedAt(),
                'duration_seconds' => $videoLink->durationSeconds()?->value(),
                'creator_principal_id' => $videoLink->creatorPrincipalIdentifier() !== null ? (string) $videoLink->creatorPrincipalIdentifier() : null,
                'created_at' => $videoLink->createdAt(),
            ],
        );
    }

    public function delete(VideoLinkIdentifier $identifier): void
    {
        VideoLinkModel::query()
            ->where('id', (string) $identifier)
            ->delete();
        $this->videoThumbnailService->releaseUnused();
    }

    public function deleteByResource(ResourceType $resourceType, WikiIdentifier $resourceIdentifier): void
    {
        VideoLinkModel::query()
            ->where('resource_type', $resourceType->value)
            ->where('wiki_id', (string) $resourceIdentifier)
            ->delete();
        $this->videoThumbnailService->releaseUnused();
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
        $this->videoThumbnailService->releaseUnused();
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
            ->where(function ($query) use ($urls): void {
                $query->whereIn('url', $urls);
                foreach ($urls as $url) {
                    if (preg_match('~^https://www\.tiktok\.com/@[^/]+/video/([1-9][0-9]{14,19})$~D', $url, $matches) === 1) {
                        $query->orWhereRaw("url ~ ?", ['^https://www\.tiktok\.com/@[^/]+/video/' . $matches[1] . '$']);
                    }
                }
            })
            ->get();

        return $models->map(fn (VideoLinkModel $model) => $this->toEntity($model))->all();
    }

    private function toEntity(VideoLinkModel $model): VideoLink
    {
        return new VideoLink(
            new VideoLinkIdentifier($model->id),
            ResourceType::from($model->resource_type),
            new WikiIdentifier($model->wiki_id),
            new ExternalContentLink($model->url),
            VideoUsage::from($model->video_usage),
            new VideoTitle($model->title),
            $model->thumbnail_url !== null ? new ThumbnailUrl($model->thumbnail_url) : null,
            $model->published_at?->toDateTimeImmutable(),
            ($model->created_at ?? throw new UnexpectedValueException('Missing creation timestamp.'))->toDateTimeImmutable(),
            $model->duration_seconds !== null ? new DurationSeconds($model->duration_seconds) : null,
            $model->creator_principal_id !== null ? new PrincipalIdentifier($model->creator_principal_id) : null,
            $model->thumbnail_path !== null ? new ImagePath($model->thumbnail_path) : null,
        );
    }
}
