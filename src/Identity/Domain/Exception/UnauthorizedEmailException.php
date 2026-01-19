<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Exception;

use Exception;

class UnauthorizedEmailException extends Exception
{
    public function __construct(string $message = 'Unauthorized email.', ?Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
