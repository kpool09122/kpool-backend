<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SendAuthCode;

use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;

interface SendAuthCodeInputPort
{
    public function email(): Email;

    public function language(): Language;
}
