<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query;

readonly class AccountDocumentReadModel
{
    public function __construct(
        private string $documentType,
        private string $documentPath,
        private string $uploadedAt,
    ) {
    }

    public function documentType(): string
    {
        return $this->documentType;
    }

    public function documentPath(): string
    {
        return $this->documentPath;
    }

    public function uploadedAt(): string
    {
        return $this->uploadedAt;
    }

    /** @return array{documentType: string, documentPath: string, uploadedAt: string} */
    public function toArray(): array
    {
        return [
            'documentType' => $this->documentType,
            'documentPath' => $this->documentPath,
            'uploadedAt' => $this->uploadedAt,
        ];
    }
}
