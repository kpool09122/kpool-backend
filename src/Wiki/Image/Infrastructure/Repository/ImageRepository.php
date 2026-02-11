<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Infrastructure\Repository;

use Application\Models\Wiki\WikiImage;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\Repository\ImageRepositoryInterface;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

final class ImageRepository implements ImageRepositoryInterface
{
    public function findById(ImageIdentifier $identifier): ?Image
    {
        $model = WikiImage::query()
            ->where('id', (string) $identifier)
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    /**
     * @return Image[]
     */
    public function findByResource(ResourceType $resourceType, WikiIdentifier $wikiIdentifier): array
    {
        $models = WikiImage::query()
            ->where('resource_type', $resourceType->value)
            ->where('wiki_id', (string) $wikiIdentifier)
            ->orderBy('display_order')
            ->get();

        return $models->map(fn (WikiImage $model) => $this->toEntity($model))->toArray();
    }

    public function save(Image $image): void
    {
        WikiImage::query()->updateOrCreate(
            ['id' => (string) $image->imageIdentifier()],
            [
                'resource_type' => $image->resourceType()->value,
                'wiki_id' => (string) $image->wikiIdentifier(),
                'image_path' => (string) $image->imagePath(),
                'image_usage' => $image->imageUsage()->value,
                'display_order' => $image->displayOrder(),
                'source_url' => $image->sourceUrl(),
                'source_name' => $image->sourceName(),
                'alt_text' => $image->altText(),
                'is_hidden' => $image->isHidden(),
                'hidden_by' => $image->hiddenBy() ? (string) $image->hiddenBy() : null,
                'hidden_at' => $image->hiddenAt(),
                'uploader_id' => (string) $image->uploaderIdentifier(),
                'uploaded_at' => $image->uploadedAt(),
                'approver_id' => $image->approverIdentifier() ? (string) $image->approverIdentifier() : null,
                'approved_at' => $image->approvedAt(),
                'updater_id' => $image->updaterIdentifier() ? (string) $image->updaterIdentifier() : null,
                'updated_at' => $image->updatedAt(),
            ],
        );
    }

    public function delete(ImageIdentifier $identifier): void
    {
        WikiImage::query()
            ->where('id', (string) $identifier)
            ->delete();
    }

    private function toEntity(WikiImage $model): Image
    {
        return new Image(
            new ImageIdentifier($model->id),
            ResourceType::from($model->resource_type),
            new WikiIdentifier($model->wiki_id),
            new ImagePath($model->image_path),
            ImageUsage::from($model->image_usage),
            $model->display_order,
            $model->source_url,
            $model->source_name,
            $model->alt_text,
            $model->is_hidden,
            $model->hidden_by ? new PrincipalIdentifier($model->hidden_by) : null,
            $model->hidden_at?->toDateTimeImmutable(),
            new PrincipalIdentifier($model->uploader_id),
            $model->uploaded_at->toDateTimeImmutable(),
            $model->approver_id ? new PrincipalIdentifier($model->approver_id) : null,
            $model->approved_at?->toDateTimeImmutable(),
            $model->updater_id ? new PrincipalIdentifier($model->updater_id) : null,
            $model->updated_at?->toDateTimeImmutable(),
        );
    }
}
