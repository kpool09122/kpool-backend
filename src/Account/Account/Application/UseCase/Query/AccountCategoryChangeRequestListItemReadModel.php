<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query;

readonly class AccountCategoryChangeRequestListItemReadModel
{
    public function __construct(
        private AccountCategoryChangeRequestReadModel $request,
        private AccountReadModel $account,
    ) {
    }

    /** @return array{requestIdentifier: string, accountIdentifier: string, currentAccountCategory: string, requestedAccountCategory: string, status: string, requestedAt: string, reviewedBy: ?string, reviewedAt: ?string, rejectionReason: array{code: string, detail: ?string}|null, account: array{accountIdentifier: string, email: string, type: string|null, name: string, status: string, accountCategory: string, phone: string|null, address: array{countryCode: string|null, administrativeAreaCode: string|null, postalCode: string|null, locality: string|null, addressLine1: string|null, addressLine2: string|null}|null}} */
    public function toArray(): array
    {
        return [
            ...$this->request->toArray(),
            'account' => $this->account->toArray(),
        ];
    }
}
