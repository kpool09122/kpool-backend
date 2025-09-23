<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\Exception;

use Exception;

class InvalidStatusException extends Exception
{
    public function __construct(
        string $message = 'Status is invalid.',
    ) {
        parent::__construct($message, 0);
    }
}
