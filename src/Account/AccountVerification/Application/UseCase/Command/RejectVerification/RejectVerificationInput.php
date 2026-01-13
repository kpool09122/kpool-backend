<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Application\UseCase\Command\RejectVerification;

use Source\Account\AccountVerification\Domain\ValueObject\RejectionReason;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class RejectVerificationInput implements RejectVerificationInputPort
{
    public function __construct(
        private VerificationIdentifier $verificationIdentifier,
        private AccountIdentifier $reviewerAccountIdentifier,
        private RejectionReason $rejectionReason,
    ) {
    }

    public function verificationIdentifier(): VerificationIdentifier
    {
        return $this->verificationIdentifier;
    }

    public function reviewerAccountIdentifier(): AccountIdentifier
    {
        return $this->reviewerAccountIdentifier;
    }

    public function rejectionReason(): RejectionReason
    {
        return $this->rejectionReason;
    }
}
