<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Exception;

use DomainException;
use Throwable;

class InvalidDelegationException extends DomainException
{
    public function __construct(
        string $message = 'Delegation is not valid.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
