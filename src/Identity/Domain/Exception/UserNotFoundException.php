<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Exception;

use Exception;

class UserNotFoundException extends Exception
{
    public function __construct(
        string $message = 'User is not found.',
    ) {
        parent::__construct($message, 0);
    }
}
