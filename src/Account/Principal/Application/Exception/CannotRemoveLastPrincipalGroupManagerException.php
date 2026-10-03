<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\Exception;

use RuntimeException;
use Throwable;

class CannotRemoveLastPrincipalGroupManagerException extends RuntimeException
{
    public function __construct(
        string $message = 'Cannot remove the last principal group manager.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
