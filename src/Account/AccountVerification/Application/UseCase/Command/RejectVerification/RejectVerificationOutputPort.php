<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Application\UseCase\Command\RejectVerification;

use Source\Account\AccountVerification\Domain\Entity\AccountVerification;

interface RejectVerificationOutputPort
{
    public function setVerification(AccountVerification $verification): void;
}
