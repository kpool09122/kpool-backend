<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreatePasskeyRegistrationOptions;

use Source\Identity\Domain\ValueObject\SignupSession;
use Source\Shared\Domain\ValueObject\Email;

interface CreatePasskeyRegistrationOptionsInputPort
{
    public function email(): Email;

    public function signupSession(): SignupSession;
}
