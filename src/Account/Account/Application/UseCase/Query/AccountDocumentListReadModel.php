<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query;

readonly class AccountDocumentListReadModel
{
    /** @param AccountDocumentReadModel[] $documents */
    public function __construct(
        private array $documents,
    ) {
    }

    /** @return AccountDocumentReadModel[] */
    public function documents(): array
    {
        return $this->documents;
    }

    /** @return array{documents: array<int, array{documentType: string, documentPath: string, uploadedAt: string}>} */
    public function toArray(): array
    {
        return [
            'documents' => array_map(
                static fn (AccountDocumentReadModel $document): array => $document->toArray(),
                $this->documents,
            ),
        ];
    }
}
