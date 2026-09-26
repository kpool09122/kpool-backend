<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\VerifyPasskeyRecoveryEmail;

use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Shared\Domain\ValueObject\Email;

interface VerifyPasskeyRecoveryEmailInputPort
{
    public function email(): Email;

    public function code(): AuthCode;
}
