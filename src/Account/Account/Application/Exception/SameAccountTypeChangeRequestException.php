<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\Exception;

use RuntimeException;

class SameAccountTypeChangeRequestException extends RuntimeException
{
    public function __construct(string $message = 'Requested account type is same as current account type.')
    {
        parent::__construct($message);
    }
}
