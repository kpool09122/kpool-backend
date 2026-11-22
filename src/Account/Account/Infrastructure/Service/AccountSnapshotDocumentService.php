<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Service;

use Illuminate\Support\Str;
use Source\Account\Account\Application\Exception\DocumentStorageFailedException;
use Source\Account\Account\Application\Service\AccountSnapshotDocumentServiceInterface;
use Source\Account\Account\Application\Service\DocumentStorageServiceInterface;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotDocument;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotIdentifier;
use Source\Account\Account\Domain\ValueObject\DocumentPath;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Throwable;

readonly class AccountSnapshotDocumentService implements AccountSnapshotDocumentServiceInterface
{
    public function __construct(private DocumentStorageServiceInterface $documentStorageService)
    {
    }

    public function hashes(array $documents): array
    {
        $hashes = [];
        foreach ($documents as $document) {
            $hashes[$document->documentType()->value] = $this->hash($document->documentPath());
        }
        ksort($hashes);

        return $hashes;
    }

    public function capture(AccountIdentifier $accountIdentifier, AccountSnapshotIdentifier $snapshotIdentifier, array $documents): array
    {
        $copied = [];
        $copiedPaths = [];

        try {
            foreach ($documents as $document) {
                $path = new DocumentPath(sprintf('accounts/%s/snapshots/%s/%s_%s.%s', (string)$accountIdentifier, (string)$snapshotIdentifier, $document->documentType()->value, (string)Str::uuid(), pathinfo((string)$document->documentPath(), PATHINFO_EXTENSION)));
                $copiedPaths[] = $path;
                $this->documentStorageService->copy($document->documentPath(), $path);
                $copied[] = new AccountSnapshotDocument($document->documentType(), $path, $document->uploadedAt(), $this->hash($path));
            }

            return $copied;
        } catch (Throwable $e) {
            foreach ($copiedPaths as $path) {
                $this->documentStorageService->delete($path);
            }

            throw new DocumentStorageFailedException('Failed to capture account snapshot documents.', $e);
        }
    }

    public function delete(array $documents): void
    {
        foreach ($documents as $document) {
            $this->documentStorageService->delete($document->documentPath());
        }
    }

    /** @throws DocumentStorageFailedException */
    private function hash(DocumentPath $path): string
    {
        try {
            $contents = $this->documentStorageService->get($path);
            if ($contents === null) {
                throw new DocumentStorageFailedException('Snapshot source document is missing.');
            }

            return hash('sha256', $contents);
        } catch (Throwable $e) {
            throw new DocumentStorageFailedException('Failed to read snapshot document.', $e);
        }
    }
}
