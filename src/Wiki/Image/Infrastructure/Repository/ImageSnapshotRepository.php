<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Infrastructure\Repository;

use Application\Models\Wiki\WikiImageSnapshot;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Domain\Entity\ImageSnapshot;
use Source\Wiki\Image\Domain\Repository\ImageSnapshotRepositoryInterface;
use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Image\Domain\ValueObject\ImageSnapshotIdentifier;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;

final class ImageSnapshotRepository implements ImageSnapshotRepositoryInterface
{
    public function findById(ImageSnapshotIdentifier $identifier): ?ImageSnapshot
    {
        $model = WikiImageSnapshot::query()
            ->where('id', (string) $identifier)
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    /**
     * @return ImageSnapshot[]
     */
    public function findByResourceSnapshot(ResourceIdentifier $resourceSnapshotIdentifier): array
    {
        $models = WikiImageSnapshot::query()
            ->where('resource_snapshot_identifier', (string) $resourceSnapshotIdentifier)
            ->orderBy('display_order')
            ->get();

        return $models->map(fn (WikiImageSnapshot $model) => $this->toEntity($model))->toArray();
    }

    public function save(ImageSnapshot $imageSnapshot): void
    {
        WikiImageSnapshot::query()->create([
            'id' => (string) $imageSnapshot->snapshotIdentifier(),
            'image_id' => (string) $imageSnapshot->imageIdentifier(),
            'resource_snapshot_identifier' => (string) $imageSnapshot->resourceSnapshotIdentifier(),
            'image_path' => (string) $imageSnapshot->imagePath(),
            'image_usage' => $imageSnapshot->imageUsage()->value,
            'display_order' => $imageSnapshot->displayOrder(),
            'created_at' => $imageSnapshot->createdAt(),
        ]);
    }

    private function toEntity(WikiImageSnapshot $model): ImageSnapshot
    {
        return new ImageSnapshot(
            new ImageSnapshotIdentifier($model->id),
            new ImageIdentifier($model->image_id),
            new ResourceIdentifier($model->resource_snapshot_identifier),
            new ImagePath($model->image_path),
            ImageUsage::from($model->image_usage),
            $model->display_order,
            $model->created_at->toDateTimeImmutable(),
        );
    }
}
