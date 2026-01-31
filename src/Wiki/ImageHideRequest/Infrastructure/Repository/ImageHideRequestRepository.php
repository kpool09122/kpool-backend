<?php

declare(strict_types=1);

namespace Source\Wiki\ImageHideRequest\Infrastructure\Repository;

use Application\Models\Wiki\ImageHideRequest as ImageHideRequestModel;
use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\ImageHideRequest\Domain\Entity\ImageHideRequest;
use Source\Wiki\ImageHideRequest\Domain\Repository\ImageHideRequestRepositoryInterface;
use Source\Wiki\ImageHideRequest\Domain\ValueObject\ImageHideRequestIdentifier;
use Source\Wiki\ImageHideRequest\Domain\ValueObject\ImageHideRequestStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class ImageHideRequestRepository implements ImageHideRequestRepositoryInterface
{
    public function save(ImageHideRequest $entity): void
    {
        ImageHideRequestModel::query()->updateOrCreate(
            ['id' => (string) $entity->requestIdentifier()],
            [
                'image_id' => (string) $entity->imageIdentifier(),
                'requester_name' => $entity->requesterName(),
                'requester_email' => $entity->requesterEmail(),
                'reason' => $entity->reason(),
                'status' => $entity->status()->value,
                'requested_at' => $entity->requestedAt(),
                'reviewer_id' => $entity->reviewerIdentifier() ? (string) $entity->reviewerIdentifier() : null,
                'reviewed_at' => $entity->reviewedAt(),
                'reviewer_comment' => $entity->reviewerComment(),
            ],
        );
    }

    public function findById(ImageHideRequestIdentifier $id): ?ImageHideRequest
    {
        $model = ImageHideRequestModel::query()
            ->where('id', (string) $id)
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function existsPendingByImageId(ImageIdentifier $imageIdentifier): bool
    {
        return ImageHideRequestModel::query()
            ->where('image_id', (string) $imageIdentifier)
            ->where('status', ImageHideRequestStatus::PENDING->value)
            ->exists();
    }

    private function toEntity(ImageHideRequestModel $model): ImageHideRequest
    {
        $requestedAt = $model->requested_at->toDateTimeImmutable();
        $reviewedAt = $model->reviewed_at
            ? $model->reviewed_at->toDateTimeImmutable()
            : null;

        return new ImageHideRequest(
            new ImageHideRequestIdentifier($model->id),
            new ImageIdentifier($model->image_id),
            $model->requester_name,
            $model->requester_email,
            $model->reason,
            ImageHideRequestStatus::from($model->status),
            $requestedAt,
            $model->reviewer_id ? new PrincipalIdentifier($model->reviewer_id) : null,
            $reviewedAt,
            $model->reviewer_comment,
        );
    }
}
