<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\Login;

use Source\Identity\Domain\ValueObject\PlainPassword;
use Source\Shared\Domain\ValueObject\Email;

interface LoginInputPort
{
    public function email(): Email;

    public function password(): PlainPassword;
}
