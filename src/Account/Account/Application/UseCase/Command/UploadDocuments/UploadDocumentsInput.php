<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\UploadDocuments;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class UploadDocumentsInput implements UploadDocumentsInputPort
{
    /** @param DocumentData[] $documents */
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private Principal $principal,
        private array $documents,
    ) {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }

    /** @return DocumentData[] */
    public function documents(): array
    {
        return $this->documents;
    }
}
