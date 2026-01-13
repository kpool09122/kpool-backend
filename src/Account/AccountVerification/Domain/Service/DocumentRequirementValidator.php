<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Domain\Service;

use Source\Account\AccountVerification\Application\Exception\InvalidDocumentsForVerificationException;
use Source\Account\AccountVerification\Domain\ValueObject\DocumentType;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationType;

class DocumentRequirementValidator implements DocumentRequirementValidatorInterface
{
    private const array TALENT_ID_DOCUMENTS = [
        DocumentType::RESIDENT_REGISTRATION,
        DocumentType::PASSPORT,
        DocumentType::DRIVER_LICENSE,
    ];

    private const array AGENCY_BUSINESS_DOCUMENTS = [
        DocumentType::BUSINESS_REGISTRATION,
        DocumentType::CORPORATE_REGISTRY,
        DocumentType::INCORPORATION_DOCUMENT,
    ];

    /**
     * @param DocumentType[] $providedDocumentTypes
     * @throws InvalidDocumentsForVerificationException
     */
    public function validate(VerificationType $verificationType, array $providedDocumentTypes): void
    {
        if ($verificationType->isTalent()) {
            $this->validateTalentDocuments($providedDocumentTypes);
        } else {
            $this->validateAgencyDocuments($providedDocumentTypes);
        }
    }

    /**
     * @param DocumentType[] $providedDocumentTypes
     * @throws InvalidDocumentsForVerificationException
     */
    private function validateTalentDocuments(array $providedDocumentTypes): void
    {
        if (! $this->hasAny($providedDocumentTypes, self::TALENT_ID_DOCUMENTS)) {
            throw new InvalidDocumentsForVerificationException('ID document is required for talent verification.');
        }

        if (! in_array(DocumentType::SELFIE, $providedDocumentTypes, true)) {
            throw new InvalidDocumentsForVerificationException('Selfie is required for talent verification.');
        }
    }

    /**
     * @param DocumentType[] $providedDocumentTypes
     * @throws InvalidDocumentsForVerificationException
     */
    private function validateAgencyDocuments(array $providedDocumentTypes): void
    {
        if (! $this->hasAny($providedDocumentTypes, self::AGENCY_BUSINESS_DOCUMENTS)) {
            throw new InvalidDocumentsForVerificationException('Business document is required for agency verification.');
        }

        if (! in_array(DocumentType::REPRESENTATIVE_ID, $providedDocumentTypes, true)) {
            throw new InvalidDocumentsForVerificationException('Representative ID is required for agency verification.');
        }
    }

    /**
     * @param DocumentType[] $provided
     * @param DocumentType[] $required
     */
    private function hasAny(array $provided, array $required): bool
    {
        return array_any($required, fn ($type) => in_array($type, $provided, true));

    }
}
