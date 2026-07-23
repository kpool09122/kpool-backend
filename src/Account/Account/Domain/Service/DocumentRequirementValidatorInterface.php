<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Service;

use Source\Account\Account\Application\Exception\InvalidDocumentsForVerificationException;
use Source\Account\Account\Domain\ValueObject\DocumentType;
use Source\Account\Account\Domain\ValueObject\VerificationType;

interface DocumentRequirementValidatorInterface
{
    /**
     * @param DocumentType[] $providedDocumentTypes
     * @throws InvalidDocumentsForVerificationException
     */
    public function validate(VerificationType $verificationType, array $providedDocumentTypes): void;
}
