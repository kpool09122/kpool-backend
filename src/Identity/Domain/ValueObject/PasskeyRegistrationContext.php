<?php

declare(strict_types=1);

namespace Source\Identity\Domain\ValueObject;

use Source\Shared\Domain\ValueObject\Email;

readonly class PasskeyRegistrationContext
{
    public function __construct(
        private Email $email,
        private SignupSession $signupSession,
    ) {
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function signupSession(): SignupSession
    {
        return $this->signupSession;
    }
}
