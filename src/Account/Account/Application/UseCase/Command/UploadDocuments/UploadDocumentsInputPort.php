<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\UploadDocuments;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface UploadDocumentsInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function principal(): Principal;

    /** @return DocumentData[] */
    public function documents(): array;
}
