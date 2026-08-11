<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\Exception;

use RuntimeException;

class AccountTypeChangeRequestNotFoundException extends RuntimeException
{
    public function __construct(string $message = 'Account type change request not found.')
    {
        parent::__construct($message);
    }
}
