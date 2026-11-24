<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Repository;

use Application\Models\Account\AccountSnapshotChangeRequest as AccountSnapshotChangeRequestModel;
use Source\Account\Account\Domain\Entity\AccountSnapshotChangeRequest;
use Source\Account\Account\Domain\Repository\AccountSnapshotChangeRequestRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeReason;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeRequestStatus;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotIdentifier;
use Source\Account\Account\Domain\ValueObject\RejectionReason;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class AccountSnapshotChangeRequestRepository implements AccountSnapshotChangeRequestRepositoryInterface
{
    public function save(AccountSnapshotChangeRequest $request): void
    {
        AccountSnapshotChangeRequestModel::query()->updateOrCreate(['id' => (string) $request->requestIdentifier()], [
            'account_id' => (string) $request->accountIdentifier(),
            'snapshot_id' => (string) $request->snapshotIdentifier(),
            'change_reason' => (string) $request->changeReason(),
            'requested_at' => $request->requestedAt(),
            'status' => $request->status()->value,
            'reviewed_by' => $request->reviewedBy() !== null ? (string) $request->reviewedBy() : null,
            'reviewed_at' => $request->reviewedAt(),
            'rejection_reason' => $request->rejectionReason()?->toArray(),
            'reviewed_snapshot_id' => $request->reviewedSnapshotIdentifier() !== null ? (string) $request->reviewedSnapshotIdentifier() : null,
        ]);
    }

    public function findById(AccountSnapshotChangeRequestIdentifier $id): ?AccountSnapshotChangeRequest
    {
        $model = AccountSnapshotChangeRequestModel::query()->find((string) $id);

        return $model === null ? null : $this->toEntity($model);
    }

    public function findByAccountIdAndStatuses(AccountIdentifier $accountId, array $statuses): array
    {
        return AccountSnapshotChangeRequestModel::query()->where('account_id', (string) $accountId)->whereIn('status', array_map(static fn (AccountSnapshotChangeRequestStatus $status): string => $status->value, $statuses))->get()->map(fn (AccountSnapshotChangeRequestModel $model): AccountSnapshotChangeRequest => $this->toEntity($model))->all();
    }

    private function toEntity(AccountSnapshotChangeRequestModel $model): AccountSnapshotChangeRequest
    {
        return new AccountSnapshotChangeRequest(
            new AccountSnapshotChangeRequestIdentifier($model->id),
            new AccountIdentifier($model->account_id),
            new AccountSnapshotIdentifier($model->snapshot_id),
            new AccountSnapshotChangeReason($model->change_reason),
            $model->requested_at->toDateTimeImmutable(),
            AccountSnapshotChangeRequestStatus::from($model->status),
            $model->reviewed_by !== null ? new AccountIdentifier($model->reviewed_by) : null,
            $model->reviewed_at?->toDateTimeImmutable(),
            $model->rejection_reason !== null ? RejectionReason::fromArray($model->rejection_reason) : null,
            $model->reviewed_snapshot_id !== null ? new AccountSnapshotIdentifier($model->reviewed_snapshot_id) : null,
        );
    }
}
