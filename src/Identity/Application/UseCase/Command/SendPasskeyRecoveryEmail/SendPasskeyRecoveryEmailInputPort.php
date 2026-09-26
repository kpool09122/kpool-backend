<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SendPasskeyRecoveryEmail;

use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;

interface SendPasskeyRecoveryEmailInputPort
{
    public function email(): Email;

    public function language(): Language;
}
