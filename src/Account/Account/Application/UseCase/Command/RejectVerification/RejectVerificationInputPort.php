<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RejectVerification;

use Source\Account\Account\Domain\ValueObject\RejectionReason;
use Source\Account\Account\Domain\ValueObject\VerificationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface RejectVerificationInputPort
{
    public function verificationIdentifier(): VerificationIdentifier;

    public function reviewerAccountIdentifier(): AccountIdentifier;

    public function rejectionReason(): RejectionReason;
}
