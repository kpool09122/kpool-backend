<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLinkAutoCollection\Infrastructure\Repository;

use Application\Models\Wiki\VideoLinkCollectionStatus as VideoLinkCollectionStatusModel;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLinkAutoCollection\Domain\Entity\VideoLinkCollectionStatus;
use Source\Wiki\VideoLinkAutoCollection\Domain\Repository\VideoLinkCollectionStatusRepositoryInterface;
use Source\Wiki\VideoLinkAutoCollection\Domain\ValueObject\VideoLinkCollectionStatusIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

final class VideoLinkCollectionStatusRepository implements VideoLinkCollectionStatusRepositoryInterface
{
    public function findByResource(ResourceType $resourceType, WikiIdentifier $wikiIdentifier): ?VideoLinkCollectionStatus
    {
        $model = VideoLinkCollectionStatusModel::query()
            ->where('resource_type', $resourceType->value)
            ->where('wiki_id', (string) $wikiIdentifier)
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function findNextTargetResource(): ?VideoLinkCollectionStatus
    {
        // 未収集優先 → 収集日が古い順
        $model = VideoLinkCollectionStatusModel::query()
            ->orderByRaw('last_collected_at IS NOT NULL')
            ->orderBy('last_collected_at')
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function save(VideoLinkCollectionStatus $status): void
    {
        VideoLinkCollectionStatusModel::query()->updateOrCreate(
            ['id' => (string) $status->identifier()],
            [
                'resource_type' => $status->resourceType()->value,
                'wiki_id' => (string) $status->wikiIdentifier(),
                'last_collected_at' => $status->lastCollectedAt(),
                'created_at' => $status->createdAt(),
            ],
        );
    }

    private function toEntity(VideoLinkCollectionStatusModel $model): VideoLinkCollectionStatus
    {
        return new VideoLinkCollectionStatus(
            new VideoLinkCollectionStatusIdentifier($model->id),
            ResourceType::from($model->resource_type),
            new WikiIdentifier($model->wiki_id),
            $model->last_collected_at?->toDateTimeImmutable(),
            $model->created_at->toDateTimeImmutable(),
        );
    }
}
