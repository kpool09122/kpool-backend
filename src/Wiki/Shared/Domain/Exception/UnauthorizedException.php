<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\Exception;

use Exception;

class UnauthorizedException extends Exception
{
    public function __construct(string $message = 'Unauthorized action.', ?Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
