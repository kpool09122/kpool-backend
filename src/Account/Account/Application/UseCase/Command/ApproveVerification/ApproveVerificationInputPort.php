<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\ApproveVerification;

use Source\Account\Account\Domain\ValueObject\VerificationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface ApproveVerificationInputPort
{
    public function verificationIdentifier(): VerificationIdentifier;

    public function reviewerAccountIdentifier(): AccountIdentifier;
}
