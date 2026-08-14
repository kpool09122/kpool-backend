<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Service;

use Source\Account\Account\Application\Exception\InvalidDocumentsForVerificationException;
use Source\Account\Account\Domain\ValueObject\DocumentType;
use Source\Account\Shared\Domain\ValueObject\AccountType;

class AccountDocumentRequirementValidator implements AccountDocumentRequirementValidatorInterface
{
    public function validate(AccountType $accountType, array $documentTypes): void
    {
        $values = array_map(static fn (DocumentType $type) => $type->value, $documentTypes);
        $uniqueValues = array_unique($values);

        if (count($values) !== count($uniqueValues)) {
            throw new InvalidDocumentsForVerificationException('Duplicate account document types are not allowed.');
        }

        match ($accountType) {
            AccountType::CORPORATION => $this->validateCorporate($documentTypes),
            AccountType::INDIVIDUAL => $this->validateIndividual($documentTypes),
        };
    }

    /** @param DocumentType[] $documentTypes */
    private function validateCorporate(array $documentTypes): void
    {
        $this->assertAllowed($documentTypes, [
            DocumentType::BUSINESS_REGISTRATION,
            DocumentType::CORPORATE_REGISTRY,
            DocumentType::INCORPORATION_DOCUMENT,
            DocumentType::REPRESENTATIVE_ID,
        ]);
        $this->assertContainsAny($documentTypes, [
            DocumentType::BUSINESS_REGISTRATION,
            DocumentType::CORPORATE_REGISTRY,
            DocumentType::INCORPORATION_DOCUMENT,
        ]);
        $this->assertContains($documentTypes, DocumentType::REPRESENTATIVE_ID);
    }

    /** @param DocumentType[] $documentTypes */
    private function validateIndividual(array $documentTypes): void
    {
        $this->assertAllowed($documentTypes, [
            DocumentType::RESIDENT_REGISTRATION,
            DocumentType::PASSPORT,
            DocumentType::DRIVER_LICENSE,
            DocumentType::SELFIE,
        ]);
        $this->assertContainsAny($documentTypes, [
            DocumentType::RESIDENT_REGISTRATION,
            DocumentType::PASSPORT,
            DocumentType::DRIVER_LICENSE,
        ]);
        $this->assertContains($documentTypes, DocumentType::SELFIE);
    }

    /**
     * @param DocumentType[] $documentTypes
     * @param DocumentType[] $allowedTypes
     */
    private function assertAllowed(array $documentTypes, array $allowedTypes): void
    {
        foreach ($documentTypes as $documentType) {
            if (! in_array($documentType, $allowedTypes, true)) {
                throw new InvalidDocumentsForVerificationException('Document type is not allowed for this account type.');
            }
        }
    }

    /** @param DocumentType[] $documentTypes */
    private function assertContains(array $documentTypes, DocumentType $required): void
    {
        if (! in_array($required, $documentTypes, true)) {
            throw new InvalidDocumentsForVerificationException('Required account documents are missing.');
        }
    }

    /**
     * @param DocumentType[] $documentTypes
     * @param DocumentType[] $requiredCandidates
     */
    private function assertContainsAny(array $documentTypes, array $requiredCandidates): void
    {
        foreach ($requiredCandidates as $candidate) {
            if (in_array($candidate, $documentTypes, true)) {
                return;
            }
        }

        throw new InvalidDocumentsForVerificationException('Required account documents are missing.');
    }
}
