<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Domain\Service;

use Source\Account\AccountVerification\Application\Exception\InvalidDocumentsForVerificationException;
use Source\Account\AccountVerification\Domain\ValueObject\DocumentType;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationType;

interface DocumentRequirementValidatorInterface
{
    /**
     * @param DocumentType[] $providedDocumentTypes
     * @throws InvalidDocumentsForVerificationException
     */
    public function validate(VerificationType $verificationType, array $providedDocumentTypes): void;
}
