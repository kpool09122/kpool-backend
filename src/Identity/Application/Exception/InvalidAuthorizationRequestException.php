<?php

declare(strict_types=1);

namespace Source\Identity\Application\Exception;

use RuntimeException;
use Throwable;

class InvalidAuthorizationRequestException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Invalid authorization request.', 0, $previous);
    }
}
