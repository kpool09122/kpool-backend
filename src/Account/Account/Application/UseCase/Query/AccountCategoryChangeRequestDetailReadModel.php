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

    /** @return array{request: array<string, mixed>, account: array<string, string>, identities: array<int, array{name: string, email: string}>, documents: array<int, array{documentType: string, documentPath: string, uploadedAt: string}>} */
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
