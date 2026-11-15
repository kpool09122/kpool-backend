<?php

declare(strict_types=1);

namespace Source\Identity\Application\Exception;

use RuntimeException;
use Throwable;

class AuthorizationRequestUnavailableException extends RuntimeException
{
    public function __construct(public readonly string $reason = 'invalid', ?Throwable $previous = null)
    {
        parent::__construct('Authorization request is unavailable.', 0, $previous);
    }
}
