<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Exception;

use DomainException;
use Throwable;

class InvalidAuthCodeException extends DomainException
{
    public function __construct(
        string $message = 'Auth code does not match.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
