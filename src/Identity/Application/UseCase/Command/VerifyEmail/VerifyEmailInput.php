<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\VerifyEmail;

use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Shared\Domain\ValueObject\Email;

readonly class VerifyEmailInput implements VerifyEmailInputPort
{
    public function __construct(
        private Email $email,
        private AuthCode $authCode,
    ) {
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function authCode(): AuthCode
    {
        return $this->authCode;
    }
}
