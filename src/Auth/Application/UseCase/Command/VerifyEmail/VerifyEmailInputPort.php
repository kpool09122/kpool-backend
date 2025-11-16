<?php

declare(strict_types=1);

namespace Source\Auth\Application\UseCase\Command\VerifyEmail;

use Source\Auth\Domain\ValueObject\AuthCode;
use Source\Shared\Domain\ValueObject\Email;

interface VerifyEmailInputPort
{
    public function email(): Email;

    public function authCode(): AuthCode;
}
