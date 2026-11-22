<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\ValueObject;

use DateTimeImmutable;

readonly class AccountSnapshotDocument
{
    public function __construct(private DocumentType $documentType, private DocumentPath $documentPath, private DateTimeImmutable $uploadedAt, private string $contentHash)
    {
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

    public function contentHash(): string
    {
        return $this->contentHash;
    }
}
