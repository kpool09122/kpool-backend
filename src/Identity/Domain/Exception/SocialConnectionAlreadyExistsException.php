<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Exception;

use DomainException;
use Throwable;

class SocialConnectionAlreadyExistsException extends DomainException
{
    public function __construct(
        string $message = 'Social connection already exists.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
