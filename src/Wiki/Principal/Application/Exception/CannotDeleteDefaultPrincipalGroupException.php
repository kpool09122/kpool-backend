<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\Exception;

use RuntimeException;
use Throwable;

class CannotDeleteDefaultPrincipalGroupException extends RuntimeException
{
    public function __construct(
        string $message = 'Cannot delete default PrincipalGroup.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
