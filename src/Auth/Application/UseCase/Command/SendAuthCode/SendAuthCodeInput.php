<?php

declare(strict_types=1);

namespace Source\Auth\Application\UseCase\Command\SendAuthCode;

use Source\Shared\Domain\ValueObject\Email;

readonly class SendAuthCodeInput implements SendAuthCodeInputPort
{
    public function __construct(
        private Email $email,
    ) {
    }

    public function email(): Email
    {
        return $this->email;
    }
}
