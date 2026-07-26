<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\Exception;

use DomainException;
use Throwable;

class InvalidStatusException extends DomainException
{
    public function __construct(
        string $message = 'Status is invalid.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
