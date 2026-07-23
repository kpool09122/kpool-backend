<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\Exception;

use RuntimeException;

class InvalidAccountCategoryForVerificationException extends RuntimeException
{
    public function __construct(string $message = 'Only GENERAL accounts can request verification.')
    {
        parent::__construct($message);
    }
}
