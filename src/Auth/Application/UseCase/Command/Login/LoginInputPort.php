<?php

declare(strict_types=1);

namespace Source\Auth\Application\UseCase\Command\Login;

use Source\Auth\Domain\ValueObject\PlainPassword;
use Source\Shared\Domain\ValueObject\Email;

interface LoginInputPort
{
    public function email(): Email;

    public function password(): PlainPassword;
}
