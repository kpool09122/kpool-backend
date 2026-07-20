<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Infrastructure\Repository;

use Application\Models\Wiki\ImageDeletionRequest as ImageDeletionRequestModel;
use Application\Models\Wiki\WikiImage;
use Illuminate\Support\Str;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\Repository\ImageRepositoryInterface;
use Source\Wiki\Image\Domain\ValueObject\DeletionRequest;
use Source\Wiki\Image\Domain\ValueObject\RightsConfirmationAgreed;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

final class ImageRepository implements ImageRepositoryInterface
{
    public function findById(ImageIdentifier $identifier): ?Image
    {
        $model = WikiImage::query()
            ->with('deletionRequests')
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
    public function findByResource(ResourceType $resourceType, TranslationSetIdentifier $translationSetIdentifier): array
    {
        $models = WikiImage::query()
            ->with('deletionRequests')
            ->where('resource_type', $resourceType->value)
            ->where('translation_set_identifier', (string) $translationSetIdentifier)
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
                'translation_set_identifier' => (string) $image->translationSetIdentifier(),
                'image_path' => (string) $image->imagePath(),
                'display_order' => $image->displayOrder(),
                'source_url' => $image->sourceUrl(),
                'source_name' => $image->sourceName(),
                'alt_text' => $image->altText(),
                'is_hidden' => $image->isHidden(),
                'hidden_at' => $image->hiddenAt(),
                'uploader_id' => (string) $image->uploaderIdentifier(),
                'uploaded_at' => $image->uploadedAt(),
                'rights_confirmation_agreed' => $image->rightsConfirmationAgreed()->value(),
                'approver_id' => $image->approverIdentifier() ? (string) $image->approverIdentifier() : null,
                'approved_at' => $image->approvedAt(),
                'updater_id' => $image->updaterIdentifier() ? (string) $image->updaterIdentifier() : null,
                'updated_at' => $image->updatedAt(),
            ],
        );

        foreach ($image->deletionRequests() as $deletionRequest) {
            $existingModel = ImageDeletionRequestModel::query()
                ->where('image_id', (string) $image->imageIdentifier())
                ->where('requested_at', $deletionRequest->requestedAt())
                ->first();

            $attributes = [
                'image_id' => (string) $image->imageIdentifier(),
                'requester_name' => $deletionRequest->requesterName(),
                'requester_email' => $deletionRequest->requesterEmail(),
                'reason' => $deletionRequest->reason(),
                'requested_at' => $deletionRequest->requestedAt(),
                'reviewer_id' => $deletionRequest->reviewerIdentifier() ? (string) $deletionRequest->reviewerIdentifier() : null,
                'reviewed_at' => $deletionRequest->reviewedAt(),
                'reject_reason' => $deletionRequest->rejectReason(),
            ];

            if ($existingModel !== null) {
                $existingModel->update($attributes);
            } else {
                $attributes['id'] = (string) Str::uuid7();
                ImageDeletionRequestModel::query()->create($attributes);
            }
        }
    }

    public function delete(ImageIdentifier $identifier): void
    {
        WikiImage::query()
            ->where('id', (string) $identifier)
            ->delete();
    }

    public function existsPendingDeletionRequest(ImageIdentifier $imageIdentifier): bool
    {
        return ImageDeletionRequestModel::query()
            ->where('image_id', (string) $imageIdentifier)
            ->whereNull('reviewed_at')
            ->exists();
    }

    private function toEntity(WikiImage $model): Image
    {
        return new Image(
            new ImageIdentifier($model->id),
            ResourceType::from($model->resource_type),
            new TranslationSetIdentifier($model->translation_set_identifier),
            new ImagePath($model->image_path),
            $model->display_order,
            $model->source_url,
            $model->source_name,
            $model->alt_text,
            $model->is_hidden,
            $model->hidden_at?->toDateTimeImmutable(),
            new PrincipalIdentifier($model->uploader_id),
            $model->uploaded_at->toDateTimeImmutable(),
            $model->approver_id ? new PrincipalIdentifier($model->approver_id) : null,
            $model->approved_at?->toDateTimeImmutable(),
            $model->updater_id ? new PrincipalIdentifier($model->updater_id) : null,
            $model->updated_at?->toDateTimeImmutable(),
            new RightsConfirmationAgreed($model->rights_confirmation_agreed),
            $model->deletionRequests
                ->map(fn (ImageDeletionRequestModel $deletionRequestModel) => $this->toDeletionRequest($deletionRequestModel))
                ->all(),
        );
    }

    private function toDeletionRequest(ImageDeletionRequestModel $model): DeletionRequest
    {
        return new DeletionRequest(
            $model->requester_name,
            $model->requester_email,
            $model->reason,
            $model->requested_at->toDateTimeImmutable(),
            $model->reviewer_id ? new PrincipalIdentifier($model->reviewer_id) : null,
            $model->reviewed_at?->toDateTimeImmutable(),
            $model->reject_reason,
        );
    }
}
