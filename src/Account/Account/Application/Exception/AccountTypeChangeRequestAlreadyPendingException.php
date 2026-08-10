<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\Exception;

use RuntimeException;

class AccountTypeChangeRequestAlreadyPendingException extends RuntimeException
{
    public function __construct(string $message = 'Account type change request is already pending.')
    {
        parent::__construct($message);
    }
}
