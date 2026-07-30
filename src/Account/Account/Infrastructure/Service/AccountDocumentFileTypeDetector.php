<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Service;

use finfo;
use Source\Account\Account\Application\Exception\InvalidDocumentsForVerificationException;
use Source\Account\Account\Application\Service\AccountDocumentFileTypeDetectorInterface;
use Source\Account\Account\Domain\ValueObject\AccountDocumentFileType;

readonly class AccountDocumentFileTypeDetector implements AccountDocumentFileTypeDetectorInterface
{
    public function detect(string $contents): AccountDocumentFileType
    {
        $mimeType = new finfo(FILEINFO_MIME_TYPE)->buffer($contents);
        $fileType = AccountDocumentFileType::tryFromMimeType((string) $mimeType);

        if ($fileType === null) {
            throw new InvalidDocumentsForVerificationException('Unsupported account document file type.');
        }

        return $fileType;
    }
}
