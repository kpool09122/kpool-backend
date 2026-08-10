<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\Exception;

use RuntimeException;

class AccountCategoryChangeRequestAlreadyPendingException extends RuntimeException
{
    public function __construct(string $message = 'Account category change request is already pending.')
    {
        parent::__construct($message);
    }
}
