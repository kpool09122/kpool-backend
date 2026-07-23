<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RequestVerification;

use Source\Account\Account\Domain\ValueObject\DocumentType;

readonly class DocumentData
{
    public function __construct(
        public DocumentType $documentType,
        public string $fileName,
        public string $fileContents,
        public int $fileSizeBytes,
    ) {
    }
}
