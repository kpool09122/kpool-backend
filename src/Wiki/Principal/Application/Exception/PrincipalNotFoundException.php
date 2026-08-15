<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\Exception;

use RuntimeException;

class PrincipalNotFoundException extends RuntimeException
{
    public function __construct(string $message = 'Principal not found.', int $code = 0, ?RuntimeException $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
