<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\GetAccountCategoryChangeRequest;

use Source\Account\Account\Application\UseCase\Query\AccountCategoryChangeRequestDetailReadModel;

class GetAccountCategoryChangeRequestOutput implements GetAccountCategoryChangeRequestOutputPort
{
    private ?AccountCategoryChangeRequestDetailReadModel $detail = null;

    public function output(AccountCategoryChangeRequestDetailReadModel $detail): void
    {
        $this->detail = $detail;
    }

    /** @return array{request: array{ requestIdentifier: string, accountIdentifier: string, currentAccountCategory: string, requestedAccountCategory: string, status: string, requestedAt: string, reviewedBy: ?string, reviewedAt: ?string, rejectionReason: array{code: string, detail: ?string}|null }, account: array{accountIdentifier: string, email: string, type: string|null, name: string, status: string, accountCategory: string, phone: string|null, address: array{countryCode: string|null, administrativeAreaCode: string|null, postalCode: string|null, locality: string|null, addressLine1: string|null, addressLine2: string|null}|null}, identities: array<array{name: string, email: string}>, documents: array<array{documentType: string, documentPath: string, uploadedAt: string}>}|array{request: array{}, account: array{}, identities: array{}, documents: array{}} */
    public function toArray(): array
    {
        if ($this->detail === null) {
            return [
                'request' => [],
                'account' => [],
                'identities' => [],
                'documents' => [],
            ];
        }

        return $this->detail->toArray();
    }
}
