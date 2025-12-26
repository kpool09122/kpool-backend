<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\Login;

use Source\Identity\Domain\ValueObject\PlainPassword;
use Source\Shared\Domain\ValueObject\Email;

readonly class LoginInput implements LoginInputPort
{
    public function __construct(
        private Email         $email,
        private PlainPassword $password,
    ) {
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function password(): PlainPassword
    {
        return $this->password;
    }
}
