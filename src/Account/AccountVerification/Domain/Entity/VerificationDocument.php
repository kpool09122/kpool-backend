<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Domain\Entity;

use DateTimeImmutable;
use Source\Account\AccountVerification\Domain\ValueObject\DocumentIdentifier;
use Source\Account\AccountVerification\Domain\ValueObject\DocumentPath;
use Source\Account\AccountVerification\Domain\ValueObject\DocumentType;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationIdentifier;

readonly class VerificationDocument
{
    public function __construct(
        private DocumentIdentifier     $documentIdentifier,
        private VerificationIdentifier $verificationIdentifier,
        private DocumentType           $documentType,
        private DocumentPath           $documentPath,
        private string                 $originalFileName,
        private int                    $fileSizeBytes,
        private DateTimeImmutable      $uploadedAt,
    ) {
    }

    public function documentIdentifier(): DocumentIdentifier
    {
        return $this->documentIdentifier;
    }

    public function verificationIdentifier(): VerificationIdentifier
    {
        return $this->verificationIdentifier;
    }

    public function documentType(): DocumentType
    {
        return $this->documentType;
    }

    public function documentPath(): DocumentPath
    {
        return $this->documentPath;
    }

    public function originalFileName(): string
    {
        return $this->originalFileName;
    }

    public function fileSizeBytes(): int
    {
        return $this->fileSizeBytes;
    }

    public function uploadedAt(): DateTimeImmutable
    {
        return $this->uploadedAt;
    }
}
