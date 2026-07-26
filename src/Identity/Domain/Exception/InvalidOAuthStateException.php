<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Exception;

use DomainException;
use Throwable;

class InvalidOAuthStateException extends DomainException
{
    public function __construct(
        string $message = 'OAuth state is invalid or expired.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
