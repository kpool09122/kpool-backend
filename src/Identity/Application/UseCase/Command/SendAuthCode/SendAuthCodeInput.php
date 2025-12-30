<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SendAuthCode;

use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;

readonly class SendAuthCodeInput implements SendAuthCodeInputPort
{
    public function __construct(
        private Email $email,
        private Language $language,
    ) {
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function language(): Language
    {
        return $this->language;
    }
}
