<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SendAuthCode;

use Source\Identity\Application\Service\EmailSendingStatus;
use Source\Shared\Application\Exception\OutputNotInitializedException;

class SendAuthCodeOutput implements SendAuthCodeOutputPort
{
    private ?EmailSendingStatus $status = null;

    public function setStatus(EmailSendingStatus $status): void
    {
        $this->status = $status;
    }

    /** @return array{accepted: true, remainingSends: int, retryAfterSeconds: int|null} */
    public function toArray(): array
    {
        $status = $this->status ?? throw new OutputNotInitializedException('Output is not set.');

        return [
            'accepted' => true,
            'remainingSends' => $status->remainingSends,
            'retryAfterSeconds' => $status->retryAfterSeconds,
        ];
    }
}
