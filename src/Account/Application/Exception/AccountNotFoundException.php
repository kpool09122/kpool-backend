<?php

declare(strict_types=1);

namespace Source\Account\Application\Exception;

use Exception;

class AccountNotFoundException extends Exception
{
    public function __construct(
        string $message = 'Account is not found.',
    ) {
        parent::__construct($message, 0);
    }
}
