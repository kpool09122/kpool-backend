<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\Exception;

use Exception;

class RoleNotFoundException extends Exception
{
    public function __construct(
        string $message = 'Role not found.',
    ) {
        parent::__construct($message, 0);
    }
}
