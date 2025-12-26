<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\RegisterUser;

use Source\Identity\Domain\ValueObject\PlainPassword;
use Source\Identity\Domain\ValueObject\UserName;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;

interface RegisterUserInputPort
{
    public function userName(): UserName;

    public function email(): Email;

    public function language(): Language;

    public function password(): PlainPassword;

    public function confirmedPassword(): PlainPassword;

    public function base64EncodedImage(): ?string;
}
