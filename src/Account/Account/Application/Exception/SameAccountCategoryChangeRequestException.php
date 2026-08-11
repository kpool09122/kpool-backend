<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\Exception;

use RuntimeException;

class SameAccountCategoryChangeRequestException extends RuntimeException
{
    public function __construct(string $message = 'Requested account category is same as current account category.')
    {
        parent::__construct($message);
    }
}
