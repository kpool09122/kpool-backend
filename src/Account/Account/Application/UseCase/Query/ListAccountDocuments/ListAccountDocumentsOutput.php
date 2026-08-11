<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\ListAccountDocuments;

use Source\Account\Account\Application\UseCase\Query\AccountDocumentReadModel;

class ListAccountDocumentsOutput implements ListAccountDocumentsOutputPort
{
    /** @var AccountDocumentReadModel[] */
    private array $documents = [];

    /** @param AccountDocumentReadModel[] $documents */
    public function output(array $documents): void
    {
        $this->documents = $documents;
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
