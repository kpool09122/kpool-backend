<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\Exception;

use RuntimeException;
use Throwable;

class CannotRemoveLastAdministratorException extends RuntimeException
{
    public function __construct(string $message = 'At least one Administrator must remain in this account.', ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
