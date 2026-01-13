<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Application\Exception;

use RuntimeException;

class InvalidAccountCategoryForVerificationException extends RuntimeException
{
    public function __construct(string $message = 'Only GENERAL accounts can request verification.')
    {
        parent::__construct($message);
    }
}
