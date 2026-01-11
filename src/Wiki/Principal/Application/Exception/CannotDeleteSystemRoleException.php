<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\Exception;

use Exception;

class CannotDeleteSystemRoleException extends Exception
{
    public function __construct(
        string $message = 'Cannot delete system role.',
    ) {
        parent::__construct($message, 0);
    }
}
