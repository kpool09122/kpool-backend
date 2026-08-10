<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Repository;

use Application\Models\Account\AccountTypeChangeRequest as AccountTypeChangeRequestModel;
use Source\Account\Account\Domain\Entity\AccountTypeChangeRequest;
use Source\Account\Account\Domain\Repository\AccountTypeChangeRequestRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\AccountTypeChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountTypeChangeRequestStatus;
use Source\Account\Account\Domain\ValueObject\RejectionReason;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class AccountTypeChangeRequestRepository implements AccountTypeChangeRequestRepositoryInterface
{
    public function save(AccountTypeChangeRequest $request): void
    {
        AccountTypeChangeRequestModel::query()->updateOrCreate(
            ['id' => (string) $request->requestIdentifier()],
            [
                'account_id' => (string) $request->accountIdentifier(),
                'current_account_type' => $request->currentAccountType()->value,
                'requested_account_type' => $request->requestedAccountType()->value,
                'status' => $request->status()->value,
                'requested_at' => $request->requestedAt(),
                'reviewed_by' => $request->reviewedBy() !== null ? (string) $request->reviewedBy() : null,
                'reviewed_at' => $request->reviewedAt(),
                'rejection_reason' => $request->rejectionReason()?->toArray(),
            ],
        );
    }

    public function findById(AccountTypeChangeRequestIdentifier $id): ?AccountTypeChangeRequest
    {
        $model = AccountTypeChangeRequestModel::query()->where('id', (string) $id)->first();

        return $model === null ? null : $this->toEntity($model);
    }

    public function findPendingByAccountId(AccountIdentifier $accountId): ?AccountTypeChangeRequest
    {
        $model = AccountTypeChangeRequestModel::query()
            ->where('account_id', (string) $accountId)
            ->where('status', AccountTypeChangeRequestStatus::PENDING->value)
            ->first();

        return $model === null ? null : $this->toEntity($model);
    }

    public function existsPending(AccountIdentifier $accountId): bool
    {
        return AccountTypeChangeRequestModel::query()
            ->where('account_id', (string) $accountId)
            ->where('status', AccountTypeChangeRequestStatus::PENDING->value)
            ->exists();
    }

    private function toEntity(AccountTypeChangeRequestModel $model): AccountTypeChangeRequest
    {
        return new AccountTypeChangeRequest(
            new AccountTypeChangeRequestIdentifier($model->id),
            new AccountIdentifier($model->account_id),
            AccountType::from($model->current_account_type),
            AccountType::from($model->requested_account_type),
            AccountTypeChangeRequestStatus::from($model->status),
            $model->requested_at->toDateTimeImmutable(),
            $model->reviewed_by !== null ? new AccountIdentifier($model->reviewed_by) : null,
            $model->reviewed_at?->toDateTimeImmutable(),
            $model->rejection_reason !== null ? RejectionReason::fromArray($model->rejection_reason) : null,
        );
    }
}
