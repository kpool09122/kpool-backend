<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLinkAutoCollection\Infrastructure\Repository;

use Application\Models\Wiki\VideoLinkCollectionStatus as VideoLinkCollectionStatusModel;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLinkAutoCollection\Domain\Entity\VideoLinkCollectionStatus;
use Source\Wiki\VideoLinkAutoCollection\Domain\Repository\VideoLinkCollectionStatusRepositoryInterface;
use Source\Wiki\VideoLinkAutoCollection\Domain\ValueObject\VideoLinkCollectionStatusIdentifier;

final class VideoLinkCollectionStatusRepository implements VideoLinkCollectionStatusRepositoryInterface
{
    public function findByResource(ResourceType $resourceType, ResourceIdentifier $resourceIdentifier): ?VideoLinkCollectionStatus
    {
        $model = VideoLinkCollectionStatusModel::query()
            ->where('resource_type', $resourceType->value)
            ->where('resource_identifier', (string) $resourceIdentifier)
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
                'resource_identifier' => (string) $status->resourceIdentifier(),
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
            new ResourceIdentifier($model->resource_identifier),
            $model->last_collected_at?->toDateTimeImmutable(),
            $model->created_at->toDateTimeImmutable(),
        );
    }
}
