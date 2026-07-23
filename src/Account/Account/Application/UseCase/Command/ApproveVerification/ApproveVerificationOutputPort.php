<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\ApproveVerification;

use Source\Account\Account\Domain\Entity\AccountVerification;

interface ApproveVerificationOutputPort
{
    public function setVerification(AccountVerification $verification): void;
}
