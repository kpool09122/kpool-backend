<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Service;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Service\DocumentStorageServiceInterface;
use Source\Account\Account\Domain\ValueObject\AccountDocumentFileType;
use Source\Account\Account\Domain\ValueObject\DocumentPath;
use Source\Account\Account\Domain\ValueObject\DocumentType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Throwable;

class DocumentStorageService implements DocumentStorageServiceInterface
{
    private const string DISK = 'verification-documents';

    private const string ACCOUNT_BASE_PATH = 'accounts';

    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function storeForAccount(
        AccountIdentifier $accountId,
        DocumentType $documentType,
        AccountDocumentFileType $fileType,
        string $contents,
    ): DocumentPath {
        $path = sprintf(
            '%s/%s/%s_%s.%s',
            self::ACCOUNT_BASE_PATH,
            (string) $accountId,
            $documentType->value,
            (string) Str::uuid(),
            $fileType->extension(),
        );

        Storage::disk(self::DISK)->put($path, $contents);

        return new DocumentPath($path);
    }

    public function getTemporaryUrl(DocumentPath $path, int $expirationMinutes = 30): string
    {
        $disk = Storage::disk(self::DISK);

        // Check if the disk supports temporary URLs (S3, etc.)
        if (method_exists($disk, 'temporaryUrl')) {
            return $disk->temporaryUrl(
                (string) $path,
                now()->addMinutes($expirationMinutes),
            );
        }

        // For local disk, return the path (should be served through a controller)
        return (string) $path;
    }

    public function get(DocumentPath $path): ?string
    {
        $contents = Storage::disk(self::DISK)->get((string) $path);

        return $contents !== false ? $contents : null;
    }

    public function delete(DocumentPath $path): bool
    {
        return Storage::disk(self::DISK)->delete((string) $path);
    }

    public function deleteAfterCommit(DocumentPath $path): void
    {
        $logger = $this->logger;
        $delete = function () use ($path, $logger): void {
            try {
                $this->delete($path);
            } catch (Throwable $e) {
                $logger->warning('Failed to delete document.', [
                    'documentPath' => (string) $path,
                    'exception' => $e,
                ]);
            }
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($delete);

            return;
        }

        $delete();
    }

    public function exists(DocumentPath $path): bool
    {
        return Storage::disk(self::DISK)->exists((string) $path);
    }
}
