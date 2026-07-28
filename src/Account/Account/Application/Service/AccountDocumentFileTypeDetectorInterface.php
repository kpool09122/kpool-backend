<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\Service;

use Source\Account\Account\Application\Exception\InvalidDocumentsForVerificationException;
use Source\Account\Account\Domain\ValueObject\AccountDocumentFileType;

interface AccountDocumentFileTypeDetectorInterface
{
    /**
     * @throws InvalidDocumentsForVerificationException
     */
    public function detect(string $contents): AccountDocumentFileType;
}
