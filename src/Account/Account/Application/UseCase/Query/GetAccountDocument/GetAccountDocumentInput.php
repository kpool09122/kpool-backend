<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\GetAccountDocument;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class GetAccountDocumentInput implements GetAccountDocumentInputPort
{
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private string $documentType,
        private Principal $principal,
    ) {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function documentType(): string
    {
        return $this->documentType;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }
}
