<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Exception;

use DomainException;
use Throwable;

class IdentityNameConfirmationMismatchException extends DomainException
{
    public function __construct(
        string $message = 'The entered username does not match the current identity name.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
