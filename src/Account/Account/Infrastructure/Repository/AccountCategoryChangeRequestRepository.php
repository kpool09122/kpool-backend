<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Repository;

use Application\Models\Account\AccountCategoryChangeRequest as AccountCategoryChangeRequestModel;
use Source\Account\Account\Domain\Entity\AccountCategoryChangeRequest;
use Source\Account\Account\Domain\Repository\AccountCategoryChangeRequestRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestStatus;
use Source\Account\Account\Domain\ValueObject\RejectionReason;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class AccountCategoryChangeRequestRepository implements AccountCategoryChangeRequestRepositoryInterface
{
    public function save(AccountCategoryChangeRequest $request): void
    {
        AccountCategoryChangeRequestModel::query()->updateOrCreate(
            ['id' => (string) $request->requestIdentifier()],
            [
                'account_id' => (string) $request->accountIdentifier(),
                'current_account_category' => $request->currentAccountCategory()->value,
                'requested_account_category' => $request->requestedAccountCategory()->value,
                'status' => $request->status()->value,
                'requested_at' => $request->requestedAt(),
                'reviewed_by' => $request->reviewedBy() !== null ? (string) $request->reviewedBy() : null,
                'reviewed_at' => $request->reviewedAt(),
                'rejection_reason' => $request->rejectionReason()?->toArray(),
            ],
        );
    }

    public function findById(AccountCategoryChangeRequestIdentifier $id): ?AccountCategoryChangeRequest
    {
        $model = AccountCategoryChangeRequestModel::query()->where('id', (string) $id)->first();

        return $model === null ? null : $this->toEntity($model);
    }

    public function findPendingByAccountId(AccountIdentifier $accountId): ?AccountCategoryChangeRequest
    {
        $model = AccountCategoryChangeRequestModel::query()
            ->where('account_id', (string) $accountId)
            ->where('status', AccountCategoryChangeRequestStatus::PENDING->value)
            ->first();

        return $model === null ? null : $this->toEntity($model);
    }

    private function toEntity(AccountCategoryChangeRequestModel $model): AccountCategoryChangeRequest
    {
        return new AccountCategoryChangeRequest(
            new AccountCategoryChangeRequestIdentifier($model->id),
            new AccountIdentifier($model->account_id),
            AccountCategory::from($model->current_account_category),
            AccountCategory::from($model->requested_account_category),
            AccountCategoryChangeRequestStatus::from($model->status),
            $model->requested_at->toDateTimeImmutable(),
            $model->reviewed_by !== null ? new AccountIdentifier($model->reviewed_by) : null,
            $model->reviewed_at?->toDateTimeImmutable(),
            $model->rejection_reason !== null ? RejectionReason::fromArray($model->rejection_reason) : null,
        );
    }
}
