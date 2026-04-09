<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Exception;

use Exception;

class InvalidDelegationException extends Exception
{
    public function __construct(
        string $message = 'Delegation is not valid.',
    ) {
        parent::__construct($message, 0);
    }
}
