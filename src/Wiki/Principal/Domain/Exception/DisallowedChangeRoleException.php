<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Exception;

use RuntimeException;

class DisallowedChangeRoleException extends RuntimeException
{
    public function __construct(
        string $message = 'You are not allowed to change access control.',
    ) {
        parent::__construct($message, 0);
    }
}
