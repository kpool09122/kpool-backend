<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Infrastructure\Repository;

use Application\Models\Wiki\DraftWikiImage;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Domain\Entity\DraftImage;
use Source\Wiki\Image\Domain\Repository\DraftImageRepositoryInterface;
use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

final class DraftImageRepository implements DraftImageRepositoryInterface
{
    public function findById(ImageIdentifier $identifier): ?DraftImage
    {
        $model = DraftWikiImage::query()
            ->where('id', (string) $identifier)
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    /**
     * @return DraftImage[]
     */
    public function findByDraftResource(ResourceType $resourceType, ResourceIdentifier $draftResourceIdentifier): array
    {
        $models = DraftWikiImage::query()
            ->where('resource_type', $resourceType->value)
            ->where('draft_resource_identifier', (string) $draftResourceIdentifier)
            ->orderBy('display_order')
            ->get();

        return $models->map(fn (DraftWikiImage $model) => $this->toEntity($model))->toArray();
    }

    public function save(DraftImage $draftImage): void
    {
        DraftWikiImage::query()->updateOrCreate(
            ['id' => (string) $draftImage->imageIdentifier()],
            [
                'published_id' => $draftImage->publishedImageIdentifier() ? (string) $draftImage->publishedImageIdentifier() : null,
                'resource_type' => $draftImage->resourceType()->value,
                'draft_resource_identifier' => (string) $draftImage->draftResourceIdentifier(),
                'editor_id' => (string) $draftImage->editorIdentifier(),
                'image_path' => (string) $draftImage->imagePath(),
                'image_usage' => $draftImage->imageUsage()->value,
                'display_order' => $draftImage->displayOrder(),
                'source_url' => $draftImage->sourceUrl(),
                'source_name' => $draftImage->sourceName(),
                'alt_text' => $draftImage->altText(),
                'status' => $draftImage->status()->value,
                'agreed_to_terms_at' => $draftImage->agreedToTermsAt(),
            ],
        );
    }

    public function delete(ImageIdentifier $identifier): void
    {
        DraftWikiImage::query()
            ->where('id', (string) $identifier)
            ->delete();
    }

    public function deleteByDraftResource(ResourceType $resourceType, ResourceIdentifier $draftResourceIdentifier): void
    {
        DraftWikiImage::query()
            ->where('resource_type', $resourceType->value)
            ->where('draft_resource_identifier', (string) $draftResourceIdentifier)
            ->delete();
    }

    private function toEntity(DraftWikiImage $model): DraftImage
    {
        return new DraftImage(
            new ImageIdentifier($model->id),
            $model->published_id ? new ImageIdentifier($model->published_id) : null,
            ResourceType::from($model->resource_type),
            new ResourceIdentifier($model->draft_resource_identifier),
            new PrincipalIdentifier($model->editor_id),
            new ImagePath($model->image_path),
            ImageUsage::from($model->image_usage),
            $model->display_order,
            $model->source_url,
            $model->source_name,
            $model->alt_text,
            ApprovalStatus::from($model->status),
            $model->agreed_to_terms_at->toDateTimeImmutable(),
            $model->created_at->toDateTimeImmutable(),
        );
    }
}
