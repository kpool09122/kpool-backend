<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Application\UseCase\Command\RequestVerification;

use Source\Account\AccountVerification\Domain\ValueObject\DocumentType;

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
