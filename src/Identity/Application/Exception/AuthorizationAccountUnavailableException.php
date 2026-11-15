<?php

declare(strict_types=1);

namespace Source\Identity\Application\Exception;

use RuntimeException;
use Throwable;

final class AuthorizationAccountUnavailableException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Original account is unavailable.', 0, $previous);
    }
}
