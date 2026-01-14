<?php

declare(strict_types=1);

namespace Tests\Account\AccountVerification\Domain\Service;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Account\AccountVerification\Application\Exception\InvalidDocumentsForVerificationException;
use Source\Account\AccountVerification\Domain\Service\DocumentRequirementValidatorInterface;
use Source\Account\AccountVerification\Domain\ValueObject\DocumentType;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationType;
use Tests\TestCase;

class DocumentRequirementValidatorTest extends TestCase
{
    /**
     * 正常系: TALENTでパスポート + セルフィーがある場合、検証が通ること.
     *
     * @throws BindingResolutionException
     */
    public function testValidateTalentWithPassportAndSelfie(): void
    {
        $validator = $this->app->make(DocumentRequirementValidatorInterface::class);
        $validator->validate(
            VerificationType::TALENT,
            [DocumentType::PASSPORT, DocumentType::SELFIE],
        );

        $this->expectNotToPerformAssertions();
    }

    /**
     * 正常系: TALENTで運転免許証 + セルフィーがある場合、検証が通ること.
     *
     * @throws BindingResolutionException
     */
    public function testValidateTalentWithDriverLicenseAndSelfie(): void
    {
        $validator = $this->app->make(DocumentRequirementValidatorInterface::class);
        $validator->validate(
            VerificationType::TALENT,
            [DocumentType::DRIVER_LICENSE, DocumentType::SELFIE],
        );

        $this->expectNotToPerformAssertions();
    }

    /**
     * 正常系: TALENTで住民登録証 + セルフィーがある場合、検証が通ること.
     *
     * @throws BindingResolutionException
     */
    public function testValidateTalentWithResidentRegistrationAndSelfie(): void
    {
        $validator = $this->app->make(DocumentRequirementValidatorInterface::class);
        $validator->validate(
            VerificationType::TALENT,
            [DocumentType::RESIDENT_REGISTRATION, DocumentType::SELFIE],
        );

        $this->expectNotToPerformAssertions();
    }

    /**
     * 異常系: TALENTでID書類がない場合、例外がスローされること.
     *
     * @throws BindingResolutionException
     */
    public function testThrowsExceptionWhenTalentMissingIdDocument(): void
    {
        $this->expectException(InvalidDocumentsForVerificationException::class);
        $this->expectExceptionMessage('ID document is required for talent verification.');

        $validator = $this->app->make(DocumentRequirementValidatorInterface::class);
        $validator->validate(
            VerificationType::TALENT,
            [DocumentType::SELFIE],
        );
    }

    /**
     * 異常系: TALENTでセルフィーがない場合、例外がスローされること.
     *
     * @throws BindingResolutionException
     */
    public function testThrowsExceptionWhenTalentMissingSelfie(): void
    {
        $this->expectException(InvalidDocumentsForVerificationException::class);
        $this->expectExceptionMessage('Selfie is required for talent verification.');

        $validator = $this->app->make(DocumentRequirementValidatorInterface::class);
        $validator->validate(
            VerificationType::TALENT,
            [DocumentType::PASSPORT],
        );
    }

    /**
     * 正常系: AGENCYで事業者登録証 + 代表者IDがある場合、検証が通ること.
     *
     * @throws BindingResolutionException
     */
    public function testValidateAgencyWithBusinessRegistrationAndRepresentativeId(): void
    {
        $validator = $this->app->make(DocumentRequirementValidatorInterface::class);
        $validator->validate(
            VerificationType::AGENCY,
            [DocumentType::BUSINESS_REGISTRATION, DocumentType::REPRESENTATIVE_ID],
        );

        $this->expectNotToPerformAssertions();
    }

    /**
     * 正常系: AGENCYで登記簿謄本 + 代表者IDがある場合、検証が通ること.
     *
     * @throws BindingResolutionException
     */
    public function testValidateAgencyWithCorporateRegistryAndRepresentativeId(): void
    {
        $validator = $this->app->make(DocumentRequirementValidatorInterface::class);
        $validator->validate(
            VerificationType::AGENCY,
            [DocumentType::CORPORATE_REGISTRY, DocumentType::REPRESENTATIVE_ID],
        );

        $this->expectNotToPerformAssertions();
    }

    /**
     * 正常系: AGENCYで法人登記書類 + 代表者IDがある場合、検証が通ること.
     *
     * @throws BindingResolutionException
     */
    public function testValidateAgencyWithIncorporationDocumentAndRepresentativeId(): void
    {
        $validator = $this->app->make(DocumentRequirementValidatorInterface::class);
        $validator->validate(
            VerificationType::AGENCY,
            [DocumentType::INCORPORATION_DOCUMENT, DocumentType::REPRESENTATIVE_ID],
        );

        $this->expectNotToPerformAssertions();
    }

    /**
     * 異常系: AGENCYで法人書類がない場合、例外がスローされること.
     *
     * @throws BindingResolutionException
     */
    public function testThrowsExceptionWhenAgencyMissingBusinessDocument(): void
    {
        $this->expectException(InvalidDocumentsForVerificationException::class);
        $this->expectExceptionMessage('Business document is required for agency verification.');

        $validator = $this->app->make(DocumentRequirementValidatorInterface::class);
        $validator->validate(
            VerificationType::AGENCY,
            [DocumentType::REPRESENTATIVE_ID],
        );
    }

    /**
     * 異常系: AGENCYで代表者IDがない場合、例外がスローされること.
     *
     * @throws BindingResolutionException
     */
    public function testThrowsExceptionWhenAgencyMissingRepresentativeId(): void
    {
        $this->expectException(InvalidDocumentsForVerificationException::class);
        $this->expectExceptionMessage('Representative ID is required for agency verification.');

        $validator = $this->app->make(DocumentRequirementValidatorInterface::class);
        $validator->validate(
            VerificationType::AGENCY,
            [DocumentType::BUSINESS_REGISTRATION],
        );
    }
}
