<?php

declare(strict_types=1);

namespace Source\Auth\Domain\Exception;

use Exception;

class AuthCodeSessionNotFoundException extends Exception
{
    public function __construct(
        string $message = 'Session is not found.',
    ) {
        parent::__construct($message, 0);
    }
}
