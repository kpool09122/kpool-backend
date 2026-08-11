<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\Service;

use Source\Account\Account\Domain\ValueObject\AccountDocumentFileType;
use Source\Account\Account\Domain\ValueObject\DocumentPath;
use Source\Account\Account\Domain\ValueObject\DocumentType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface DocumentStorageServiceInterface
{
    public function storeForAccount(
        AccountIdentifier $accountId,
        DocumentType $documentType,
        AccountDocumentFileType $fileType,
        string $contents,
    ): DocumentPath;

    /**
     * Get a temporary URL for accessing the document.
     *
     * @param DocumentPath $path
     * @param int $expirationMinutes
     * @return string
     */
    public function getTemporaryUrl(DocumentPath $path, int $expirationMinutes = 30): string;

    /**
     * Get the file contents.
     *
     * @param DocumentPath $path
     * @return string|null
     */
    public function get(DocumentPath $path): ?string;

    /**
     * Delete a document file.
     *
     * @param DocumentPath $path
     * @return bool
     */
    public function delete(DocumentPath $path): bool;

    public function deleteAfterCommit(DocumentPath $path): void;

    /**
     * Check if a document exists.
     *
     * @param DocumentPath $path
     * @return bool
     */
    public function exists(DocumentPath $path): bool;
}
