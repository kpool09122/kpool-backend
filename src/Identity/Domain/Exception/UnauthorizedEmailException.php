<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Exception;

use DomainException;
use Throwable;

class UnauthorizedEmailException extends DomainException
{
    public function __construct(
        string $message = 'Unauthorized email.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
