<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Application\UseCase\Command\RequestVerification;

use Source\Account\AccountVerification\Domain\Entity\AccountVerification;

interface RequestVerificationOutputPort
{
    public function setVerification(AccountVerification $verification): void;
}
