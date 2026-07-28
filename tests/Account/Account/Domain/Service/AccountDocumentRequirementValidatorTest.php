<?php

declare(strict_types=1);

namespace Tests\Account\Account\Domain\Service;

use Source\Account\Account\Application\Exception\InvalidDocumentsForVerificationException;
use Source\Account\Account\Domain\Service\AccountDocumentRequirementValidator;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\DocumentType;
use Tests\TestCase;

class AccountDocumentRequirementValidatorTest extends TestCase
{
    public function testValidateCorporateDocuments(): void
    {
        $validator = new AccountDocumentRequirementValidator();

        $validator->validate(AccountType::CORPORATION, [
            DocumentType::BUSINESS_REGISTRATION,
            DocumentType::REPRESENTATIVE_ID,
        ]);

        $this->addToAssertionCount(1);
    }

    public function testValidateIndividualDocuments(): void
    {
        $validator = new AccountDocumentRequirementValidator();

        $validator->validate(AccountType::INDIVIDUAL, [
            DocumentType::PASSPORT,
            DocumentType::SELFIE,
        ]);

        $this->addToAssertionCount(1);
    }

    public function testValidateRejectsMissingRequiredDocuments(): void
    {
        $validator = new AccountDocumentRequirementValidator();

        $this->expectException(InvalidDocumentsForVerificationException::class);

        $validator->validate(AccountType::INDIVIDUAL, [
            DocumentType::PASSPORT,
        ]);
    }

    public function testValidateRejectsDocumentTypesForDifferentAccountType(): void
    {
        $validator = new AccountDocumentRequirementValidator();

        $this->expectException(InvalidDocumentsForVerificationException::class);

        $validator->validate(AccountType::CORPORATION, [
            DocumentType::BUSINESS_REGISTRATION,
            DocumentType::SELFIE,
        ]);
    }
}
