<?php

declare(strict_types=1);

namespace Source\Account\IdentityGroup\Application\Exception;

use Exception;

class IdentityGroupNotFoundException extends Exception
{
    public function __construct(
        string $message = 'IdentityGroup is not found.',
    ) {
        parent::__construct($message, 0);
    }
}
