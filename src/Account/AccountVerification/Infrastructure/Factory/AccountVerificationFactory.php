<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Account\AccountVerification\Domain\Entity\AccountVerification;
use Source\Account\AccountVerification\Domain\Factory\AccountVerificationFactoryInterface;
use Source\Account\AccountVerification\Domain\ValueObject\ApplicantInfo;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationIdentifier;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationStatus;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationType;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class AccountVerificationFactory implements AccountVerificationFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function create(
        AccountIdentifier $accountIdentifier,
        VerificationType $verificationType,
        ApplicantInfo $applicantInfo,
    ): AccountVerification {
        return new AccountVerification(
            new VerificationIdentifier($this->uuidGenerator->generate()),
            $accountIdentifier,
            $verificationType,
            VerificationStatus::PENDING,
            $applicantInfo,
            new DateTimeImmutable(),
            null,
            null,
            null,
        );
    }
}
