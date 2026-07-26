<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\Exception;

use RuntimeException;
use Throwable;

class CannotRemoveLastOwnerException extends RuntimeException
{
    public function __construct(
        string $message = 'Cannot remove the last OWNER from the account.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
