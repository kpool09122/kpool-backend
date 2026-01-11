<?php

declare(strict_types=1);

namespace Source\Account\IdentityGroup\Application\Exception;

use Exception;

class CannotDeleteDefaultIdentityGroupException extends Exception
{
    public function __construct(
        string $message = 'Cannot delete default IdentityGroup.',
    ) {
        parent::__construct($message, 0);
    }
}
