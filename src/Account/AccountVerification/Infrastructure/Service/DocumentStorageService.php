<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Infrastructure\Service;

use Illuminate\Support\Facades\Storage;
use Source\Account\AccountVerification\Application\Service\DocumentStorageServiceInterface;
use Source\Account\AccountVerification\Domain\ValueObject\DocumentPath;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationIdentifier;

class DocumentStorageService implements DocumentStorageServiceInterface
{
    private const string DISK = 'verification-documents';

    private const string BASE_PATH = 'verifications';

    private const int MAX_FILE_NAME_LENGTH = 200;

    public function store(
        VerificationIdentifier $verificationId,
        string $fileName,
        string $contents,
    ): DocumentPath {
        $sanitizedFileName = $this->sanitizeFileName($fileName);
        $path = sprintf(
            '%s/%s/%s_%s',
            self::BASE_PATH,
            (string) $verificationId,
            time(),
            $sanitizedFileName,
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

    public function deleteByVerificationId(VerificationIdentifier $verificationId): bool
    {
        $directory = sprintf('%s/%s', self::BASE_PATH, (string) $verificationId);

        return Storage::disk(self::DISK)->deleteDirectory($directory);
    }

    public function exists(DocumentPath $path): bool
    {
        return Storage::disk(self::DISK)->exists((string) $path);
    }

    private function sanitizeFileName(string $fileName): string
    {
        $fileName = basename($fileName);

        $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);

        if (strlen($fileName) > self::MAX_FILE_NAME_LENGTH) {
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $name = pathinfo($fileName, PATHINFO_FILENAME);
            $extensionLength = $extension !== '' ? strlen($extension) + 1 : 0;
            $maxNameLength = self::MAX_FILE_NAME_LENGTH - $extensionLength;
            $fileName = substr($name, 0, $maxNameLength) . ($extension !== '' ? '.' . $extension : '');
        }

        return $fileName;
    }
}
