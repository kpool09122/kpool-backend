<?php

declare(strict_types=1);

namespace Source\Wiki\AccessControl\Application\Exception;

use RuntimeException;

class UnauthorizedChangingACException extends RuntimeException
{
    public function __construct(
        string $message = 'You are not allowed to change access control.',
    ) {
        parent::__construct($message, 0);
    }
}
