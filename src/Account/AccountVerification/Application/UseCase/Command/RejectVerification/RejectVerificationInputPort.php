<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Application\UseCase\Command\RejectVerification;

use Source\Account\AccountVerification\Domain\ValueObject\RejectionReason;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface RejectVerificationInputPort
{
    public function verificationIdentifier(): VerificationIdentifier;

    public function reviewerAccountIdentifier(): AccountIdentifier;

    public function rejectionReason(): RejectionReason;
}
