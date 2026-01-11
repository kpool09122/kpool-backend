<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\Exception;

use Exception;

class CannotDeleteSystemPolicyException extends Exception
{
    public function __construct(
        string $message = 'Cannot delete system policy.',
    ) {
        parent::__construct($message, 0);
    }
}
