<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Exception;

use DomainException;
use Throwable;

class AuthCodeExpiredException extends DomainException
{
    public function __construct(
        string $message = 'Auth code has expired.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
