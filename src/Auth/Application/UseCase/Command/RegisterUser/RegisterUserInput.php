<?php

declare(strict_types=1);

namespace Source\Auth\Application\UseCase\Command\RegisterUser;

use Source\Auth\Domain\ValueObject\PlainPassword;
use Source\Auth\Domain\ValueObject\UserName;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Translation;

readonly class RegisterUserInput implements RegisterUserInputPort
{
    public function __construct(
        private UserName $username,
        private Email         $email,
        private Translation $translation,
        private PlainPassword $password,
        private PlainPassword $confirmedPassword,
        private ?string $base64EncodedImage,
    ) {
    }

    public function username(): UserName
    {
        return $this->username;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function translation(): Translation
    {
        return $this->translation;
    }

    public function password(): PlainPassword
    {
        return $this->password;
    }

    public function confirmedPassword(): PlainPassword
    {
        return $this->confirmedPassword;
    }

    public function base64EncodedImage(): ?string
    {
        return $this->base64EncodedImage;
    }
}
