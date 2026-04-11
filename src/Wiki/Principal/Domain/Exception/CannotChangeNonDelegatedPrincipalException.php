<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Exception;

use Exception;

class CannotChangeNonDelegatedPrincipalException extends Exception
{
    public function __construct(
        string $message = 'Cannot change enabled status of non-delegated principal.',
    ) {
        parent::__construct($message, 0);
    }
}
