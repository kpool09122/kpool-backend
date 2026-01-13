<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Domain\Factory;

use Source\Account\AccountVerification\Domain\Entity\AccountVerification;
use Source\Account\AccountVerification\Domain\ValueObject\ApplicantInfo;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface AccountVerificationFactoryInterface
{
    public function create(
        AccountIdentifier $accountIdentifier,
        VerificationType $verificationType,
        ApplicantInfo $applicantInfo,
    ): AccountVerification;
}
