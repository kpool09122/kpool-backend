<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Infrastructure\Repository;

use Application\Models\Wiki\WikiImage;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\Repository\ImageRepositoryInterface;
use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

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
    public function findByResource(ResourceType $resourceType, ResourceIdentifier $resourceIdentifier): array
    {
        $models = WikiImage::query()
            ->where('resource_type', $resourceType->value)
            ->where('resource_identifier', (string) $resourceIdentifier)
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
                'resource_identifier' => (string) $image->resourceIdentifier(),
                'image_path' => (string) $image->imagePath(),
                'image_usage' => $image->imageUsage()->value,
                'display_order' => $image->displayOrder(),
                'source_url' => $image->sourceUrl(),
                'source_name' => $image->sourceName(),
                'alt_text' => $image->altText(),
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
            new ResourceIdentifier($model->resource_identifier),
            new ImagePath($model->image_path),
            ImageUsage::from($model->image_usage),
            $model->display_order,
            $model->source_url,
            $model->source_name,
            $model->alt_text,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
        );
    }
}
