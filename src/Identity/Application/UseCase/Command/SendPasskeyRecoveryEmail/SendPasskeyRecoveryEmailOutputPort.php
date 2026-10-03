<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SendPasskeyRecoveryEmail;

use Source\Identity\Application\Service\EmailSendingStatus;

interface SendPasskeyRecoveryEmailOutputPort
{
    public function setStatus(EmailSendingStatus $status): void;
}
