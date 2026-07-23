<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RejectVerification;

use Source\Account\Account\Domain\Entity\AccountVerification;

interface RejectVerificationOutputPort
{
    public function setVerification(AccountVerification $verification): void;
}
