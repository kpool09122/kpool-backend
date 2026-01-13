<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Infrastructure\Repository;

use Application\Models\Account\AccountVerification as AccountVerificationModel;
use Application\Models\Account\VerificationDocument as VerificationDocumentModel;
use Source\Account\AccountVerification\Domain\Entity\AccountVerification;
use Source\Account\AccountVerification\Domain\Entity\VerificationDocument;
use Source\Account\AccountVerification\Domain\Repository\AccountVerificationRepositoryInterface;
use Source\Account\AccountVerification\Domain\ValueObject\ApplicantInfo;
use Source\Account\AccountVerification\Domain\ValueObject\DocumentIdentifier;
use Source\Account\AccountVerification\Domain\ValueObject\DocumentPath;
use Source\Account\AccountVerification\Domain\ValueObject\DocumentType;
use Source\Account\AccountVerification\Domain\ValueObject\RejectionReason;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationIdentifier;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationStatus;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class AccountVerificationRepository implements AccountVerificationRepositoryInterface
{
    public function save(AccountVerification $entity): void
    {
        AccountVerificationModel::query()->updateOrCreate(
            ['id' => (string) $entity->verificationIdentifier()],
            [
                'account_id' => (string) $entity->accountIdentifier(),
                'verification_type' => $entity->verificationType()->value,
                'status' => $entity->status()->value,
                'applicant_info' => $entity->applicantInfo()->toArray(),
                'requested_at' => $entity->requestedAt(),
                'reviewed_by' => $entity->reviewedBy() !== null ? (string) $entity->reviewedBy() : null,
                'reviewed_at' => $entity->reviewedAt(),
                'rejection_reason' => $entity->rejectionReason()?->toArray(),
            ],
        );

        foreach ($entity->documents() as $document) {
            VerificationDocumentModel::query()->updateOrCreate(
                ['id' => (string) $document->documentIdentifier()],
                [
                    'verification_id' => (string) $document->verificationIdentifier(),
                    'document_type' => $document->documentType()->value,
                    'document_path' => (string) $document->documentPath(),
                    'original_file_name' => $document->originalFileName(),
                    'file_size_bytes' => $document->fileSizeBytes(),
                    'uploaded_at' => $document->uploadedAt(),
                ],
            );
        }
    }

    public function findById(VerificationIdentifier $id): ?AccountVerification
    {
        $model = AccountVerificationModel::query()
            ->with('documents')
            ->where('id', (string) $id)
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function findByAccountId(AccountIdentifier $accountId): ?AccountVerification
    {
        $model = AccountVerificationModel::query()
            ->with('documents')
            ->where('account_id', (string) $accountId)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function findPendingByAccountId(AccountIdentifier $accountId): ?AccountVerification
    {
        $model = AccountVerificationModel::query()
            ->with('documents')
            ->where('account_id', (string) $accountId)
            ->where('status', VerificationStatus::PENDING->value)
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function existsPending(AccountIdentifier $accountId): bool
    {
        return AccountVerificationModel::query()
            ->where('account_id', (string) $accountId)
            ->where('status', VerificationStatus::PENDING->value)
            ->exists();
    }

    /**
     * @return AccountVerification[]
     */
    public function findByStatus(VerificationStatus $status, int $limit = 50, int $offset = 0): array
    {
        $models = AccountVerificationModel::query()
            ->with('documents')
            ->where('status', $status->value)
            ->orderBy('requested_at', 'asc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return $models->map(fn ($model) => $this->toEntity($model))->all();
    }

    /**
     * @return AccountVerification[]
     */
    public function findAll(int $limit = 50, int $offset = 0): array
    {
        $models = AccountVerificationModel::query()
            ->with('documents')
            ->orderBy('requested_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return $models->map(fn ($model) => $this->toEntity($model))->all();
    }

    private function toEntity(AccountVerificationModel $model): AccountVerification
    {
        $requestedAt = $model->requested_at->toDateTimeImmutable();
        $reviewedAt = $model->reviewed_at
            ? $model->reviewed_at->toDateTimeImmutable()
            : null;

        $rejectionReason = $model->rejection_reason !== null
            ? RejectionReason::fromArray($model->rejection_reason)
            : null;

        $reviewedBy = $model->reviewed_by !== null
            ? new AccountIdentifier($model->reviewed_by)
            : null;

        $documents = $model->documents->map(function ($doc) {
            return new VerificationDocument(
                new DocumentIdentifier($doc->id),
                new VerificationIdentifier($doc->verification_id),
                DocumentType::from($doc->document_type),
                new DocumentPath($doc->document_path),
                $doc->original_file_name,
                $doc->file_size_bytes,
                $doc->uploaded_at->toDateTimeImmutable(),
            );
        })->all();

        return new AccountVerification(
            new VerificationIdentifier($model->id),
            new AccountIdentifier($model->account_id),
            VerificationType::from($model->verification_type),
            VerificationStatus::from($model->status),
            ApplicantInfo::fromArray($model->applicant_info),
            $requestedAt,
            $reviewedBy,
            $reviewedAt,
            $rejectionReason,
            $documents,
        );
    }
}
