<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Query;

use Application\Models\Account\AccountCategoryChangeRequest as AccountCategoryChangeRequestModel;
use DateTimeInterface;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestForbiddenException;
use Source\Account\Account\Application\UseCase\Query\AccountCategoryChangeRequestReadModel;
use Source\Account\Account\Application\UseCase\Query\GetLatestAccountCategoryChangeRequest\GetLatestAccountCategoryChangeRequestInputPort;
use Source\Account\Account\Application\UseCase\Query\GetLatestAccountCategoryChangeRequest\GetLatestAccountCategoryChangeRequestInterface;
use Source\Account\Account\Application\UseCase\Query\GetLatestAccountCategoryChangeRequest\GetLatestAccountCategoryChangeRequestOutputPort;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestStatus;

readonly class GetLatestAccountCategoryChangeRequest implements GetLatestAccountCategoryChangeRequestInterface
{
    public function process(GetLatestAccountCategoryChangeRequestInputPort $input, GetLatestAccountCategoryChangeRequestOutputPort $output): void
    {
        if ((string) $input->principal()->accountIdentifier() !== (string) $input->accountIdentifier()) {
            throw new AccountCategoryChangeRequestForbiddenException();
        }

        $request = AccountCategoryChangeRequestModel::query()
            ->where('account_id', (string) $input->accountIdentifier())
            ->where('status', '!=', AccountCategoryChangeRequestStatus::WITHDRAWN->value)
            ->orderByDesc('requested_at')
            ->orderByDesc('id')
            ->first();

        if ($request === null) {
            $output->output(null);

            return;
        }

        $output->output(new AccountCategoryChangeRequestReadModel(
            requestIdentifier: $request->id,
            accountIdentifier: $request->account_id,
            currentAccountCategory: $request->current_account_category,
            requestedAccountCategory: $request->requested_account_category,
            status: $request->status,
            requestedAt: $request->requested_at->format(DateTimeInterface::ATOM),
            reviewedBy: $request->reviewed_by,
            reviewedAt: $request->reviewed_at?->format(DateTimeInterface::ATOM),
            rejectionReason: $request->rejection_reason,
        ));
    }
}
