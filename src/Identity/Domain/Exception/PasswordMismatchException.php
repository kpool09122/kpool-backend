<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Exception;

use Exception;

class PasswordMismatchException extends Exception
{
    public function __construct(
        string $message = 'Passwords do not match.',
    ) {
        parent::__construct($message, 0);
    }
}
