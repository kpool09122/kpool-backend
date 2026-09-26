<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreatePasskeyRegistrationOptions;

use Source\Identity\Domain\ValueObject\SignupSession;
use Source\Shared\Domain\ValueObject\Email;

readonly class CreatePasskeyRegistrationOptionsInput implements CreatePasskeyRegistrationOptionsInputPort
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
