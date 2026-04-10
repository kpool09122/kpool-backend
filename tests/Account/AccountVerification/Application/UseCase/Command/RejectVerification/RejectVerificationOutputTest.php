<?php

declare(strict_types=1);

namespace Tests\Account\AccountVerification\Application\UseCase\Command\RejectVerification;

use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Source\Account\AccountVerification\Application\UseCase\Command\RejectVerification\RejectVerificationOutput;
use Source\Account\AccountVerification\Domain\Entity\AccountVerification;
use Source\Account\AccountVerification\Domain\ValueObject\ApplicantInfo;
use Source\Account\AccountVerification\Domain\ValueObject\RejectionReason;
use Source\Account\AccountVerification\Domain\ValueObject\RejectionReasonCode;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationIdentifier;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationStatus;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;

class RejectVerificationOutputTest extends TestCase
{
    public function testToArrayWithVerification(): void
    {
        $verificationIdentifier = new VerificationIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $reviewedBy = new AccountIdentifier(StrTestHelper::generateUuid());
        $requestedAt = new DateTimeImmutable();
        $reviewedAt = new DateTimeImmutable();
        $rejectionReason = new RejectionReason(RejectionReasonCode::DOCUMENT_UNCLEAR, 'Image is blurry');

        $verification = new AccountVerification(
            $verificationIdentifier,
            $accountIdentifier,
            VerificationType::TALENT,
            VerificationStatus::REJECTED,
            new ApplicantInfo('Taro Yamada'),
            $requestedAt,
            $reviewedBy,
            $reviewedAt,
            $rejectionReason,
        );

        $output = new RejectVerificationOutput();
        $output->setVerification($verification);

        $result = $output->toArray();

        $this->assertSame((string) $verificationIdentifier, $result['verificationIdentifier']);
        $this->assertSame((string) $accountIdentifier, $result['accountIdentifier']);
        $this->assertSame(VerificationType::TALENT->value, $result['verificationType']);
        $this->assertSame(VerificationStatus::REJECTED->value, $result['status']);
        $this->assertSame('Taro Yamada', $result['applicantName']);
        $this->assertSame($requestedAt->format(DateTimeInterface::ATOM), $result['requestedAt']);
        $this->assertSame((string) $reviewedBy, $result['reviewedBy']);
        $this->assertSame($reviewedAt->format(DateTimeInterface::ATOM), $result['reviewedAt']);
        $this->assertSame(RejectionReasonCode::DOCUMENT_UNCLEAR->value, $result['rejectionReason']['code']);
        $this->assertSame('Image is blurry', $result['rejectionReason']['detail']);
        $this->assertSame([], $result['documents']);
    }

    public function testToArrayWithoutVerification(): void
    {
        $output = new RejectVerificationOutput();
        $this->assertSame([], $output->toArray());
    }
}
