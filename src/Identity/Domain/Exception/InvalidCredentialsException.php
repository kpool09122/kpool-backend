<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Exception;

use Exception;

class InvalidCredentialsException extends Exception
{
    public function __construct(
        string $message = 'Invalid credentials.',
    ) {
        parent::__construct($message, 0);
    }
}
