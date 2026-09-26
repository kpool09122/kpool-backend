<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\VerifyPasskeyRecoveryEmail;

use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Shared\Domain\ValueObject\Email;

readonly class VerifyPasskeyRecoveryEmailInput implements VerifyPasskeyRecoveryEmailInputPort
{
    public function __construct(private Email $email, private AuthCode $code)
    {
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function code(): AuthCode
    {
        return $this->code;
    }
}
