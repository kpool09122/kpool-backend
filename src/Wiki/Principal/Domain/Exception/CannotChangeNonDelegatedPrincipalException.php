<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Exception;

use DomainException;
use Throwable;

class CannotChangeNonDelegatedPrincipalException extends DomainException
{
    public function __construct(
        string $message = 'Cannot change enabled status of non-delegated principal.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
