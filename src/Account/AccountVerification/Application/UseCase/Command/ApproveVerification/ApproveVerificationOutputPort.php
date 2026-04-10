<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Application\UseCase\Command\ApproveVerification;

use Source\Account\AccountVerification\Domain\Entity\AccountVerification;

interface ApproveVerificationOutputPort
{
    public function setVerification(AccountVerification $verification): void;
}
