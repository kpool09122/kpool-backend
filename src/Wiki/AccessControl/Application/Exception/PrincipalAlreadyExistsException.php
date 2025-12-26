<?php

declare(strict_types=1);

namespace Source\Wiki\AccessControl\Application\Exception;

use Exception;

class PrincipalAlreadyExistsException extends Exception
{
    public function __construct(
        string $message = 'Principal already exists for this identity.',
    ) {
        parent::__construct($message, 0);
    }
}
