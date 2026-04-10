<?php

declare(strict_types=1);

namespace Tests\Account\AccountVerification\Application\UseCase\Command\ApproveVerification;

use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Source\Account\AccountVerification\Application\UseCase\Command\ApproveVerification\ApproveVerificationOutput;
use Source\Account\AccountVerification\Domain\Entity\AccountVerification;
use Source\Account\AccountVerification\Domain\ValueObject\ApplicantInfo;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationIdentifier;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationStatus;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;

class ApproveVerificationOutputTest extends TestCase
{
    public function testToArrayWithVerification(): void
    {
        $verificationIdentifier = new VerificationIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $reviewedBy = new AccountIdentifier(StrTestHelper::generateUuid());
        $requestedAt = new DateTimeImmutable();
        $reviewedAt = new DateTimeImmutable();

        $verification = new AccountVerification(
            $verificationIdentifier,
            $accountIdentifier,
            VerificationType::TALENT,
            VerificationStatus::APPROVED,
            new ApplicantInfo('Taro Yamada'),
            $requestedAt,
            $reviewedBy,
            $reviewedAt,
            null,
        );

        $output = new ApproveVerificationOutput();
        $output->setVerification($verification);

        $result = $output->toArray();

        $this->assertSame((string) $verificationIdentifier, $result['verificationIdentifier']);
        $this->assertSame((string) $accountIdentifier, $result['accountIdentifier']);
        $this->assertSame(VerificationType::TALENT->value, $result['verificationType']);
        $this->assertSame(VerificationStatus::APPROVED->value, $result['status']);
        $this->assertSame('Taro Yamada', $result['applicantName']);
        $this->assertSame($requestedAt->format(DateTimeInterface::ATOM), $result['requestedAt']);
        $this->assertSame((string) $reviewedBy, $result['reviewedBy']);
        $this->assertSame($reviewedAt->format(DateTimeInterface::ATOM), $result['reviewedAt']);
        $this->assertNull($result['rejectionReason']);
        $this->assertSame([], $result['documents']);
    }

    public function testToArrayWithoutVerification(): void
    {
        $output = new ApproveVerificationOutput();
        $this->assertSame([], $output->toArray());
    }
}
