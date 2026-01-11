<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\Exception;

use Exception;

class CannotDeleteDefaultPrincipalGroupException extends Exception
{
    public function __construct(
        string $message = 'Cannot delete default PrincipalGroup.',
    ) {
        parent::__construct($message, 0);
    }
}
