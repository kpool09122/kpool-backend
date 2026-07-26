<?php

declare(strict_types=1);

namespace Source\Account\DelegationPermission\Application\Exception;

use RuntimeException;
use Throwable;

class DelegationPermissionNotFoundException extends RuntimeException
{
    public function __construct(
        string $message = 'DelegationPermission is not found.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
