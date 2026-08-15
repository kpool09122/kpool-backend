<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\Exception;

use RuntimeException;
use Throwable;

class SystemRoleNotFoundException extends RuntimeException
{
    public function __construct(
        string $roleName,
        ?Throwable $previous = null,
    ) {
        parent::__construct($roleName . ' system role is not found.', 0, $previous);
    }
}
