<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Exception;

use Exception;

class AuthCodeExpiredException extends Exception
{
    public function __construct(
        string $message = 'Auth code has expired.',
    ) {
        parent::__construct($message, 0);
    }
}
