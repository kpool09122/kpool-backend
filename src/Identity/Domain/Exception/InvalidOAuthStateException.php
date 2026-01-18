<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Exception;

use Exception;

class InvalidOAuthStateException extends Exception
{
    public function __construct(
        string $message = 'OAuth state is invalid or expired.',
    ) {
        parent::__construct($message, 0);
    }
}
