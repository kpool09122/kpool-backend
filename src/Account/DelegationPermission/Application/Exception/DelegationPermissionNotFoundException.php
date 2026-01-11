<?php

declare(strict_types=1);

namespace Source\Account\DelegationPermission\Application\Exception;

use Exception;

class DelegationPermissionNotFoundException extends Exception
{
    public function __construct(
        string $message = 'DelegationPermission is not found.',
    ) {
        parent::__construct($message, 0);
    }
}
