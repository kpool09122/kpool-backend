<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Exception;

use DomainException;
use Throwable;

class PrincipalAlreadyExistsException extends DomainException
{
    public function __construct(
        string $message = 'Principal already exists for this identity.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
