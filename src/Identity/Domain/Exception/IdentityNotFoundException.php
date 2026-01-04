<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Exception;

use Exception;

class IdentityNotFoundException extends Exception
{
    public function __construct(
        string $message = 'Identity is not found.',
    ) {
        parent::__construct($message, 0);
    }
}
