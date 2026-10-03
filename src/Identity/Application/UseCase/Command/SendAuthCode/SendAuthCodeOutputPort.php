<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SendAuthCode;

use Source\Identity\Application\Service\EmailSendingStatus;

interface SendAuthCodeOutputPort
{
    public function setStatus(EmailSendingStatus $status): void;
}
