<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\ValueObject;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class AccountDocument
{
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private DocumentType $documentType,
        private DocumentPath $documentPath,
        private DateTimeImmutable $uploadedAt,
    ) {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function documentType(): DocumentType
    {
        return $this->documentType;
    }

    public function documentPath(): DocumentPath
    {
        return $this->documentPath;
    }

    public function uploadedAt(): DateTimeImmutable
    {
        return $this->uploadedAt;
    }
}
