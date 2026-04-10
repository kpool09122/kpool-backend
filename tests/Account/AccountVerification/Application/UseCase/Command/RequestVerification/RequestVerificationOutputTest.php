<?php

declare(strict_types=1);

namespace Tests\Account\AccountVerification\Application\UseCase\Command\RequestVerification;

use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Source\Account\AccountVerification\Application\UseCase\Command\RequestVerification\RequestVerificationOutput;
use Source\Account\AccountVerification\Domain\Entity\AccountVerification;
use Source\Account\AccountVerification\Domain\Entity\VerificationDocument;
use Source\Account\AccountVerification\Domain\ValueObject\ApplicantInfo;
use Source\Account\AccountVerification\Domain\ValueObject\DocumentIdentifier;
use Source\Account\AccountVerification\Domain\ValueObject\DocumentPath;
use Source\Account\AccountVerification\Domain\ValueObject\DocumentType;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationIdentifier;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationStatus;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;

class RequestVerificationOutputTest extends TestCase
{
    public function testToArrayWithVerification(): void
    {
        $verificationIdentifier = new VerificationIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $requestedAt = new DateTimeImmutable();
        $uploadedAt = new DateTimeImmutable();

        $document = new VerificationDocument(
            new DocumentIdentifier(StrTestHelper::generateUuid()),
            $verificationIdentifier,
            DocumentType::PASSPORT,
            new DocumentPath('/verifications/documents/test-file.jpg'),
            'my-passport.jpg',
            1024000,
            $uploadedAt,
        );

        $verification = new AccountVerification(
            $verificationIdentifier,
            $accountIdentifier,
            VerificationType::TALENT,
            VerificationStatus::PENDING,
            new ApplicantInfo('Taro Yamada'),
            $requestedAt,
            null,
            null,
            null,
            [$document],
        );

        $output = new RequestVerificationOutput();
        $output->setVerification($verification);

        $result = $output->toArray();

        $this->assertSame((string) $verificationIdentifier, $result['verificationIdentifier']);
        $this->assertSame((string) $accountIdentifier, $result['accountIdentifier']);
        $this->assertSame(VerificationType::TALENT->value, $result['verificationType']);
        $this->assertSame(VerificationStatus::PENDING->value, $result['status']);
        $this->assertSame('Taro Yamada', $result['applicantName']);
        $this->assertSame($requestedAt->format(DateTimeInterface::ATOM), $result['requestedAt']);
        $this->assertNull($result['reviewedBy']);
        $this->assertNull($result['reviewedAt']);
        $this->assertNull($result['rejectionReason']);
        $this->assertCount(1, $result['documents']);
        $this->assertSame((string) $document->documentIdentifier(), $result['documents'][0]['documentIdentifier']);
        $this->assertSame(DocumentType::PASSPORT->value, $result['documents'][0]['documentType']);
        $this->assertSame((string) $document->documentPath(), $result['documents'][0]['documentPath']);
        $this->assertSame('my-passport.jpg', $result['documents'][0]['originalFileName']);
        $this->assertSame(1024000, $result['documents'][0]['fileSizeBytes']);
        $this->assertSame($uploadedAt->format(DateTimeInterface::ATOM), $result['documents'][0]['uploadedAt']);
    }

    public function testToArrayWithoutVerification(): void
    {
        $output = new RequestVerificationOutput();
        $this->assertSame([], $output->toArray());
    }
}
