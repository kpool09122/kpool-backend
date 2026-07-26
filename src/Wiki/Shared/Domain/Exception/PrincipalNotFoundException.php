<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\Exception;

use DomainException;
use Throwable;

class PrincipalNotFoundException extends DomainException
{
    public function __construct(
        string $message = 'Principal is not found.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
