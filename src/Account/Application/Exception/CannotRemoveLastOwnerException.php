<?php

declare(strict_types=1);

namespace Source\Account\Application\Exception;

use Exception;

class CannotRemoveLastOwnerException extends Exception
{
    public function __construct(
        string $message = 'Cannot remove the last OWNER from the account.',
    ) {
        parent::__construct($message, 0);
    }
}
