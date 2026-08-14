<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\Exception;

use RuntimeException;

class CannotRemoveLastWikiAdministratorException extends RuntimeException
{
    public function __construct(string $message = 'Cannot remove the last Wiki Administrator.', int $code = 0, ?RuntimeException $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
