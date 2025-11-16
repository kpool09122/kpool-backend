<?php

declare(strict_types=1);

namespace Source\Auth\Application\UseCase\Command\SendAuthCode;

use Source\Shared\Domain\ValueObject\Email;

interface SendAuthCodeInputPort
{
    public function email(): Email;
}
