<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Repository;

use Application\Models\Account\AccountCategoryChangeRequest as AccountCategoryChangeRequestModel;
use Source\Account\Account\Domain\Entity\AccountCategoryChangeRequest;
use Source\Account\Account\Domain\Repository\AccountCategoryChangeRequestRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestAccount;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestStatus;
use Source\Account\Account\Domain\ValueObject\RejectionReason;
use Source\Shared\Domain\ValueObject\AccountCategory;
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
                'account_email' => $request->accountSnapshot()->email,
                'account_type' => $request->accountSnapshot()->type,
                'account_name' => $request->accountSnapshot()->name,
                'account_status' => $request->accountSnapshot()->status,
                'account_phone' => $request->accountSnapshot()->phone,
                'account_address_country_code' => $request->accountSnapshot()->countryCode,
                'account_address_administrative_area_code' => $request->accountSnapshot()->administrativeAreaCode,
                'account_address_postal_code' => $request->accountSnapshot()->postalCode,
                'account_address_locality' => $request->accountSnapshot()->locality,
                'account_address_line1' => $request->accountSnapshot()->addressLine1,
                'account_address_line2' => $request->accountSnapshot()->addressLine2,
            ],
        );
    }

    public function findById(AccountCategoryChangeRequestIdentifier $id): ?AccountCategoryChangeRequest
    {
        $model = AccountCategoryChangeRequestModel::query()->where('id', (string) $id)->first();

        return $model === null ? null : $this->toEntity($model);
    }

    /**
     * @param AccountCategoryChangeRequestStatus[] $statuses
     * @return AccountCategoryChangeRequest[]
     */
    public function findByAccountIdAndStatuses(AccountIdentifier $accountId, array $statuses): array
    {
        return AccountCategoryChangeRequestModel::query()
            ->where('account_id', (string) $accountId)
            ->whereIn('status', array_map(static fn (AccountCategoryChangeRequestStatus $status): string => $status->value, $statuses))
            ->get()
            ->map(fn (AccountCategoryChangeRequestModel $model): AccountCategoryChangeRequest => $this->toEntity($model))
            ->all();
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
            new AccountCategoryChangeRequestAccount(
                email: $model->account_email,
                type: $model->account_type,
                name: $model->account_name,
                status: $model->account_status,
                phone: $model->account_phone,
                countryCode: $model->account_address_country_code,
                administrativeAreaCode: $model->account_address_administrative_area_code,
                postalCode: $model->account_address_postal_code,
                locality: $model->account_address_locality,
                addressLine1: $model->account_address_line1,
                addressLine2: $model->account_address_line2,
            ),
        );
    }
}
