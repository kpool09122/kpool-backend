<?php

declare(strict_types=1);

namespace Source\Auth\Domain\Exception;

use Exception;

class AlreadyUserExistsException extends Exception
{
    public function __construct(
        string $message = 'User already exists.',
    ) {
        parent::__construct($message, 0);
    }
}
