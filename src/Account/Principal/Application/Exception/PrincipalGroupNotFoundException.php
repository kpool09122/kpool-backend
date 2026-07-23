<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\Exception;

use Exception;

class PrincipalGroupNotFoundException extends Exception
{
    public function __construct(
        string $message = 'PrincipalGroup is not found.',
    ) {
        parent::__construct($message, 0);
    }
}
