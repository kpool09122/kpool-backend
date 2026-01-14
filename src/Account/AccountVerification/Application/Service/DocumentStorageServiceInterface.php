<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Application\Service;

use Source\Account\AccountVerification\Domain\ValueObject\DocumentPath;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationIdentifier;

interface DocumentStorageServiceInterface
{
    /**
     * Store a document file and return the storage path.
     *
     * @param VerificationIdentifier $verificationId
     * @param string $fileName
     * @param string $contents File contents
     * @return DocumentPath
     */
    public function store(
        VerificationIdentifier $verificationId,
        string $fileName,
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

    /**
     * Delete all documents for a verification.
     *
     * @param VerificationIdentifier $verificationId
     * @return bool
     */
    public function deleteByVerificationId(VerificationIdentifier $verificationId): bool;

    /**
     * Check if a document exists.
     *
     * @param DocumentPath $path
     * @return bool
     */
    public function exists(DocumentPath $path): bool;
}
