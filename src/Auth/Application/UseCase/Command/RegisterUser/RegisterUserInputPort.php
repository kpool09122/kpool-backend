<?php

declare(strict_types=1);

namespace Source\Auth\Application\UseCase\Command\RegisterUser;

use Source\Auth\Domain\ValueObject\PlainPassword;
use Source\Auth\Domain\ValueObject\UserName;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Translation;

interface RegisterUserInputPort
{
    public function userName(): UserName;

    public function email(): Email;

    public function translation(): Translation;

    public function password(): PlainPassword;

    public function confirmedPassword(): PlainPassword;

    public function base64EncodedImage(): ?string;
}
