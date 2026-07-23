<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RequestVerification;

use Source\Account\Account\Domain\Entity\AccountVerification;

interface RequestVerificationOutputPort
{
    public function setVerification(AccountVerification $verification): void;
}
