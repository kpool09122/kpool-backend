<?php

declare(strict_types=1);

namespace Source\Identity\Application\Exception;

use RuntimeException;
use Throwable;

class InvalidAuthorizationConsentException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Invalid authorization consent.', 0, $previous);
    }
}
