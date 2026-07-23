<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Factory;

use Source\Account\Account\Domain\Entity\AccountVerification;
use Source\Account\Account\Domain\ValueObject\ApplicantInfo;
use Source\Account\Account\Domain\ValueObject\VerificationType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface AccountVerificationFactoryInterface
{
    public function create(
        AccountIdentifier $accountIdentifier,
        VerificationType $verificationType,
        ApplicantInfo $applicantInfo,
    ): AccountVerification;
}
