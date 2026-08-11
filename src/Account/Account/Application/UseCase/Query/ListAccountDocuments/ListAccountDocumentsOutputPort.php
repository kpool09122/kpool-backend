<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\ListAccountDocuments;

use Source\Account\Account\Application\UseCase\Query\AccountDocumentReadModel;

interface ListAccountDocumentsOutputPort
{
    /** @param AccountDocumentReadModel[] $documents */
    public function output(array $documents): void;

    /** @return array{documents: array<int, array{documentType: string, documentPath: string, uploadedAt: string}>} */
    public function toArray(): array;
}
