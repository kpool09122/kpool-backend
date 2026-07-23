<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Account\Account\Domain\Entity\AccountVerification;
use Source\Account\Account\Domain\Factory\AccountVerificationFactoryInterface;
use Source\Account\Account\Domain\ValueObject\ApplicantInfo;
use Source\Account\Account\Domain\ValueObject\VerificationIdentifier;
use Source\Account\Account\Domain\ValueObject\VerificationStatus;
use Source\Account\Account\Domain\ValueObject\VerificationType;
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
