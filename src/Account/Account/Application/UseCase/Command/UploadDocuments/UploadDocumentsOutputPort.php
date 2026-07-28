<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\UploadDocuments;

use Source\Account\Account\Domain\ValueObject\AccountDocument;

interface UploadDocumentsOutputPort
{
    /** @param AccountDocument[] $documents */
    public function setDocuments(array $documents): void;
}
