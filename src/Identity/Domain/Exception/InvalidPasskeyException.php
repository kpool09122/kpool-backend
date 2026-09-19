<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Exception;

use DomainException;
use Throwable;

class InvalidPasskeyException extends DomainException
{
    public function __construct(string $message = 'Invalid passkey data.', ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
