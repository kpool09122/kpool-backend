<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\Exception;

use RuntimeException;
use Throwable;

class CannotDeleteSystemRoleException extends RuntimeException
{
    public function __construct(
        string $message = 'Cannot delete system role.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
