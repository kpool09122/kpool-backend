<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Infrastructure\Repository;

use Application\Models\Wiki\OfficialCertification as OfficialCertificationModel;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\OfficialCertification\Domain\Entity\OfficialCertification;
use Source\Wiki\OfficialCertification\Domain\Repository\OfficialCertificationRepositoryInterface;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationIdentifier;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

class OfficialCertificationRepository implements OfficialCertificationRepositoryInterface
{
    public function save(OfficialCertification $entity): void
    {
        OfficialCertificationModel::query()->updateOrCreate(
            ['id' => (string) $entity->certificationIdentifier()],
            [
                'resource_type' => $entity->resourceType()->value,
                'translation_set_identifier' => (string) $entity->translationSetIdentifier(),
                'owner_account_id' => (string) $entity->ownerAccountIdentifier(),
                'status' => $entity->status()->value,
                'requested_at' => $entity->requestedAt(),
                'approved_at' => $entity->approvedAt(),
                'rejected_at' => $entity->rejectedAt(),
            ],
        );
    }

    public function findById(CertificationIdentifier $id): ?OfficialCertification
    {
        $model = OfficialCertificationModel::query()
            ->where('id', (string) $id)
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function findByResource(ResourceType $type, TranslationSetIdentifier $id): ?OfficialCertification
    {
        $model = OfficialCertificationModel::query()
            ->where('resource_type', $type->value)
            ->where('translation_set_identifier', (string) $id)
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function existsPending(ResourceType $type, TranslationSetIdentifier $id): bool
    {
        return OfficialCertificationModel::query()
            ->where('resource_type', $type->value)
            ->where('translation_set_identifier', (string) $id)
            ->where('status', CertificationStatus::PENDING->value)
            ->exists();
    }

    private function toEntity(OfficialCertificationModel $model): OfficialCertification
    {
        $requestedAt = $model->requested_at->toDateTimeImmutable();
        $approvedAt = $model->approved_at
            ? $model->approved_at->toDateTimeImmutable()
            : null;
        $rejectedAt = $model->rejected_at
            ? $model->rejected_at->toDateTimeImmutable()
            : null;

        return new OfficialCertification(
            new CertificationIdentifier($model->id),
            ResourceType::from($model->resource_type),
            new TranslationSetIdentifier($model->translation_set_identifier),
            new AccountIdentifier($model->owner_account_id),
            CertificationStatus::from($model->status),
            $requestedAt,
            $approvedAt,
            $rejectedAt,
        );
    }
}
