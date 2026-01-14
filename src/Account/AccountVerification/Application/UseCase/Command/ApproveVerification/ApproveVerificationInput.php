<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Application\UseCase\Command\ApproveVerification;

use Source\Account\AccountVerification\Domain\ValueObject\VerificationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class ApproveVerificationInput implements ApproveVerificationInputPort
{
    public function __construct(
        private VerificationIdentifier $verificationIdentifier,
        private AccountIdentifier $reviewerAccountIdentifier,
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
}
