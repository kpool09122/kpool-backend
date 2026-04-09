<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Exception;

use Exception;

class InvalidAuthCodeException extends Exception
{
    public function __construct(
        string $message = 'Auth code does not match.',
    ) {
        parent::__construct($message, 0);
    }
}
