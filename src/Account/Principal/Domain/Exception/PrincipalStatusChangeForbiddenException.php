<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\Exception;

use DomainException;
use Throwable;

class PrincipalStatusChangeForbiddenException extends DomainException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Forced stop cannot be changed through principal management.', 0, $previous);
    }
}
