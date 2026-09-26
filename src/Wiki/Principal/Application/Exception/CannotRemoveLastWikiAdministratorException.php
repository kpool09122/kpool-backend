<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\Exception;

use RuntimeException;
use Throwable;

class CannotRemoveLastWikiAdministratorException extends RuntimeException
{
    public function __construct(
        string $message = 'Cannot remove the last Wiki Administrator.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
