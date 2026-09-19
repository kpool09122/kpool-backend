<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Exception;

use DomainException;
use Throwable;

class PasskeyNotFoundException extends DomainException
{
    public function __construct(string $message = 'Passkey is not found.', ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
