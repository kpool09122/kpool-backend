<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query;

readonly class AccountCategoryChangeRequestDetailReadModel
{
    /**
     * @param AccountCategoryChangeRequestIdentityReadModel[] $identities
     * @param AccountDocumentReadModel[] $documents
     */
    public function __construct(
        private AccountCategoryChangeRequestReadModel $request,
        private AccountReadModel $account,
        private array $identities,
        private array $documents,
    ) {
    }

    /** @return array{request: array{ requestIdentifier: string, accountIdentifier: string, currentAccountCategory: string, requestedAccountCategory: string, status: string, requestedAt: string, reviewedBy: ?string, reviewedAt: ?string, rejectionReason: array{code: string, detail: ?string}|null }, account: array{accountIdentifier: string, email: string, type: string, name: string, status: string, accountCategory: string, phone: string|null, address: array{countryCode: string|null, administrativeAreaCode: string|null, postalCode: string|null, locality: string|null, addressLine1: string|null, addressLine2: string|null}|null}, identities: array<array{name: string, email: string}>, documents: array<array{documentType: string, documentPath: string, uploadedAt: string}>} */
    public function toArray(): array
    {
        return [
            'request' => $this->request->toArray(),
            'account' => $this->account->toArray(),
            'identities' => array_map(
                static fn (AccountCategoryChangeRequestIdentityReadModel $identity): array => $identity->toArray(),
                $this->identities,
            ),
            'documents' => array_map(
                static fn (AccountDocumentReadModel $document): array => $document->toArray(),
                $this->documents,
            ),
        ];
    }
}
