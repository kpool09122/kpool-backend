<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\Exception;

use RuntimeException;

class AccountTypeChangeRequestForbiddenException extends RuntimeException
{
    public function __construct(string $message = 'Account type change request is forbidden.')
    {
        parent::__construct($message);
    }
}
