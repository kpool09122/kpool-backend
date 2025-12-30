<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\VerifyEmail;

use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Shared\Domain\ValueObject\Email;

interface VerifyEmailInputPort
{
    public function email(): Email;

    public function authCode(): AuthCode;
}
