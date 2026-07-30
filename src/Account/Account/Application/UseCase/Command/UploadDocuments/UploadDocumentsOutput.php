<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\UploadDocuments;

use Source\Account\Account\Domain\ValueObject\AccountDocument;

class UploadDocumentsOutput implements UploadDocumentsOutputPort
{
    /** @var AccountDocument[] */
    private array $documents = [];

    public function setDocuments(array $documents): void
    {
        $this->documents = $documents;
    }

    /** @return array{documents: array<int, array<string, mixed>>} */
    public function toArray(): array
    {
        return [
            'documents' => array_map(static fn (AccountDocument $document) => [
                'documentType' => $document->documentType()->value,
                'documentPath' => (string) $document->documentPath(),
                'uploadedAt' => $document->uploadedAt()->format(DATE_ATOM),
            ], $this->documents),
        ];
    }
}
