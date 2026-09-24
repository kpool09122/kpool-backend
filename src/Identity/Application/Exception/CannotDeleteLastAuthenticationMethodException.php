<?php

declare(strict_types=1);

namespace Source\Identity\Application\Exception;

use DomainException;
use Throwable;

class CannotDeleteLastAuthenticationMethodException extends DomainException
{
    public function __construct(
        string $message = 'Cannot delete the last authentication method.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
